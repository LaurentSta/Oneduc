<?php

use App\Http\Middleware\EnsureAssociationMembership;
use App\Jobs\ConvertLectureSlides;
use App\Models\Module;
use App\Models\ModuleLecture;
use App\Models\ModuleSection;
use App\Models\OutilEtat;
use App\Models\User;
use App\Support\Outils\EtatsOutils;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutMiddleware([
        EnsureAssociationMembership::class,
        ValidateCsrfToken::class,
    ]);
    Queue::fake();
    Storage::fake('local');
    Storage::fake('public');

    // Désactivé par défaut (bug d'affichage historique) : activé ici pour
    // tester le fonctionnement réel de l'outil, indépendamment de son statut.
    OutilEtat::create(['cle' => 'powerpoint_module', 'actif' => true]);
    EtatsOutils::viderCache();
});

function createPowerPointToolTrainer(string $suffix = ''): User
{
    $suffix = $suffix ?: Str::lower(Str::random(6));

    return User::query()->create([
        'prenom' => 'Camille',
        'name' => 'Formatrice',
        'username' => 'camille.powerpoint.'.$suffix,
        'email' => 'camille.powerpoint.'.$suffix.'@example.test',
        'password' => Hash::make('password'),
        'role' => 'formateur',
        'status' => true,
    ]);
}

/**
 * @return array{category_id: int, subcategory_id: int}
 */
function createPowerPointToolTaxonomy(): array
{
    $suffix = Str::lower(Str::random(6));
    $categoryId = DB::table('categories')->insertGetId([
        'category_name' => 'Bureautique',
        'category_slug' => 'bureautique-'.$suffix,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $subcategoryId = DB::table('subcategories')->insertGetId([
        'category_id' => $categoryId,
        'subcategory_name' => 'Présentations',
        'subcategory_slug' => 'presentations-'.$suffix,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [
        'category_id' => $categoryId,
        'subcategory_id' => $subcategoryId,
    ];
}

/**
 * @return array{module: Module, lecture: ModuleLecture}
 */
function createPowerPointToolModule(User $trainer, string $status = 'ready'): array
{
    ['category_id' => $categoryId, 'subcategory_id' => $subcategoryId] = createPowerPointToolTaxonomy();

    $module = Module::query()->create([
        'category_id' => $categoryId,
        'subcategory_id' => $subcategoryId,
        'formateur_id' => $trainer->id,
        'module_title' => 'Présentation test',
        'module_name' => 'Présentation test',
        'module_name_slug' => 'presentation-test-'.Str::lower(Str::random(4)),
        'certificat' => false,
        'is_trainer_authored' => true,
        'status' => false,
    ]);
    $section = ModuleSection::query()->create([
        'module_id' => $module->id,
        'section_title' => 'Présentation',
    ]);
    $lecture = ModuleLecture::query()->create([
        'module_id' => $module->id,
        'section_id' => $section->id,
        'lecture_title' => 'Présentation test',
        'position' => 1,
        'content_type' => 'slides',
        'slides_status' => $status,
        'slides_path' => $status === 'ready' ? 'slides/lecture_test_'.$module->id : null,
        'slides_source_path' => 'slides/sources/lecture_test_'.$module->id.'/source.pdf',
        'slide_count' => $status === 'ready' ? 2 : 0,
    ]);

    return compact('module', 'lecture');
}

it('exposes the PowerPoint creator from the digital tools area', function () {
    $trainer = createPowerPointToolTrainer();
    createPowerPointToolTaxonomy();

    $toolsPage = $this
        ->actingAs($trainer)
        ->get(route('formateur.outils.index'));

    $toolsPage->assertOk();
    $toolsPage->assertSee('PowerPoint vers module');
    $toolsPage->assertSee(route('formateur.outils.powerpoint.index'), false);

    $creatorPage = $this
        ->actingAs($trainer)
        ->get(route('formateur.outils.powerpoint.index'));

    $creatorPage->assertOk();
    $creatorPage->assertSee('Créer un module depuis PowerPoint');
    $creatorPage->assertSee('Créer le module');
});

it('creates a draft module and starts slide conversion from an uploaded presentation', function () {
    $trainer = createPowerPointToolTrainer();
    ['category_id' => $categoryId, 'subcategory_id' => $subcategoryId] = createPowerPointToolTaxonomy();

    $response = $this
        ->actingAs($trainer)
        ->post(route('formateur.outils.powerpoint.store'), [
            'title' => 'Sécurité alimentaire',
            'description' => 'Un module créé depuis une présentation.',
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'section_title' => 'Les fondamentaux',
            'lecture_title' => 'Diaporama principal',
            'duration' => 25,
            'slides_file' => UploadedFile::fake()->create('securite.pdf', 100, 'application/pdf'),
        ]);

    $module = Module::query()->where('formateur_id', $trainer->id)->firstOrFail();
    $lecture = $module->lectures()->firstOrFail();

    $response->assertRedirect(route('formateur.outils.powerpoint.show', $module));

    expect($module->status)->toBeFalse();
    expect($module->is_trainer_authored)->toBeTrue();
    expect($module->sections()->firstOrFail()->section_title)->toBe('Les fondamentaux');
    expect($lecture->lecture_title)->toBe('Diaporama principal');
    expect($lecture->content_type)->toBe('slides');
    expect($lecture->slides_status)->toBe('pending');
    expect($lecture->duration)->toBe(25);
    expect($lecture->slides_source_path)->not->toBeNull();

    Storage::disk('local')->assertExists($lecture->slides_source_path);
    Queue::assertPushed(
        ConvertLectureSlides::class,
        fn (ConvertLectureSlides $job): bool => $job->lectureId === $lecture->id
            && $job->uploadedFilePath === $lecture->slides_source_path
    );
});

it('shows a SlideShare-style viewer and lets the owner publish a ready module', function () {
    $trainer = createPowerPointToolTrainer();
    ['module' => $module, 'lecture' => $lecture] = createPowerPointToolModule($trainer);

    Storage::disk('public')->put($lecture->slides_path.'/slide_001.jpg', 'slide-one');
    Storage::disk('public')->put($lecture->slides_path.'/slide_002.jpg', 'slide-two');

    $page = $this
        ->actingAs($trainer)
        ->get(route('formateur.outils.powerpoint.show', $module));

    $page->assertOk();
    $page->assertSee('Lecteur de présentation');
    $page->assertSee('Miniature');
    $page->assertSee('slide_001.jpg');

    $status = $this
        ->actingAs($trainer)
        ->getJson(route('formateur.outils.powerpoint.status', $module));

    $status->assertOk()->assertJson([
        'status' => 'ready',
        'slide_count' => 2,
        'published' => false,
        'ready' => true,
    ]);

    $publish = $this
        ->actingAs($trainer)
        ->post(route('formateur.outils.powerpoint.publish', $module), ['published' => true]);

    $publish->assertRedirect();
    expect($module->refresh()->status)->toBeTrue();
});

it('prevents another trainer from managing a PowerPoint module', function () {
    $owner = createPowerPointToolTrainer('owner');
    $otherTrainer = createPowerPointToolTrainer('other');
    ['module' => $module] = createPowerPointToolModule($owner);

    $this->actingAs($otherTrainer)
        ->get(route('formateur.outils.powerpoint.show', $module))
        ->assertForbidden();

    $this->actingAs($otherTrainer)
        ->post(route('formateur.outils.powerpoint.publish', $module), ['published' => true])
        ->assertForbidden();
});

it('empêche le référent de gérer une présentation du catalogue officiel', function () {
    $referent = createPowerPointToolTrainer('referent');
    ['module' => $module] = createPowerPointToolModule($referent);
    $module->forceFill([
        'is_trainer_authored' => false,
        'publication_state' => Module::PUBLICATION_PUBLISHED,
        'published_at' => now(),
        'status' => true,
    ])->save();

    $this->actingAs($referent)
        ->post(route('formateur.outils.powerpoint.retry', $module))
        ->assertForbidden();

    $this->actingAs($referent)
        ->post(route('formateur.outils.powerpoint.publish', $module), ['published' => false])
        ->assertForbidden();

    expect($module->fresh()->status)->toBeTrue()
        ->and($module->fresh()->publication_state)->toBe(Module::PUBLICATION_PUBLISHED);
});
