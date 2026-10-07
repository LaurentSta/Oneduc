<?php

use App\Domains\CatalogueFormations\Actions\BasculerGroupesVersionFormation;
use App\Domains\CatalogueFormations\Actions\CreerVersionFormationCatalogue;
use App\Domains\CatalogueFormations\Actions\PublierVersionFormationCatalogue;
use App\Models\Group;
use App\Models\Module;
use App\Models\QuizOption;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
});

function donneesCatalogueVersionne(): array
{
    $admin = User::factory()->create(['role' => 'admin']);
    $formateur = User::factory()->create(['role' => 'formateur']);

    $categorieId = DB::table('categories')->insertGetId([
        'category_name' => 'Catalogue versionné',
        'category_slug' => 'catalogue-versionne-'.Str::random(6),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $sousCategorieId = DB::table('subcategories')->insertGetId([
        'category_id' => $categorieId,
        'subcategory_name' => 'Version initiale',
        'subcategory_slug' => 'version-initiale-'.Str::random(6),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('admin', 'formateur', 'categorieId', 'sousCategorieId');
}

it('crée une version brouillon indépendante avec ses médias quiz et ressources', function () {
    extract(donneesCatalogueVersionne());

    $source = Module::query()->create([
        'category_id' => $categorieId,
        'subcategory_id' => $sousCategorieId,
        'formateur_id' => $formateur->id,
        'created_by' => $admin->id,
        'module_title' => 'Formation officielle',
        'module_name' => 'Formation officielle',
        'module_name_slug' => 'formation-officielle',
        'is_trainer_authored' => false,
        'publication_state' => Module::PUBLICATION_PUBLISHED,
        'status' => true,
        'version_number' => 1,
        'published_at' => now(),
    ]);

    $media = $source->addMedia(UploadedFile::fake()->image('illustration.png', 32, 32))
        ->toMediaCollection('lesson-images');
    $section = $source->sections()->create(['section_title' => 'Chapitre', 'position' => 0]);
    $lecture = $section->lectures()->create([
        'module_id' => $source->id,
        'lecture_title' => 'Leçon riche',
        'content_type' => 'blocks',
        'content_blocks' => [
            ['type' => 'text', 'html' => '<p>Contenu publié</p>'],
            ['type' => 'image', 'media_id' => $media->id, 'caption' => 'Illustration'],
        ],
        'position' => 0,
    ]);
    $question = QuizQuestion::query()->create([
        'lecture_id' => $lecture->id,
        'question_text' => 'Question copiée ?',
        'type' => 'single',
        'is_active' => true,
        'points' => 2,
    ]);
    QuizOption::query()->create([
        'question_id' => $question->id,
        'option_text' => 'Oui',
        'is_correct' => true,
        'position' => 1,
    ]);
    Storage::disk('public')->put('module-resources/module_'.$source->id.'/guide.pdf', 'guide');
    $videoSource = 'modules/videos/modules/module_'.$source->id.'/introduction.mp4';
    Storage::disk('public')->put($videoSource, 'video-source');
    $source->update([
        'module_video' => route('media.storage', ['path' => $videoSource], false),
    ]);
    $lecture->lessonResources()->create([
        'module_id' => $source->id,
        'title' => 'Guide',
        'file_path' => 'module-resources/module_'.$source->id.'/guide.pdf',
        'original_name' => 'guide.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 5,
        'is_visible_to_stagiaire' => true,
        'position' => 1,
    ]);

    $version = app(CreerVersionFormationCatalogue::class)->execute($source, $admin->id);

    expect($version->catalogue_key)->toBe($source->catalogue_key)
        ->and($version->version_number)->toBe(2)
        ->and($version->publication_state)->toBe(Module::PUBLICATION_DRAFT)
        ->and($version->status)->toBeFalse()
        ->and($version->source_module_id)->toBe($source->id)
        ->and($version->groups()->count())->toBe(0);

    $lectureCopie = $version->sections()->firstOrFail()->lectures()->firstOrFail();
    $blocImage = collect($lectureCopie->content_blocks)->firstWhere('type', 'image');
    $videoCopie = Str::after((string) $version->module_video, '/media/storage/');
    expect($blocImage['media_id'])->not->toBe($media->id)
        ->and($version->getMedia('lesson-images')->pluck('id')->all())->toContain($blocImage['media_id'])
        ->and($lectureCopie->quizQuestions()->count())->toBe(1)
        ->and($lectureCopie->quizQuestions()->first()->options()->count())->toBe(1)
        ->and($lectureCopie->lessonResources()->count())->toBe(1)
        ->and(Storage::disk('public')->exists($lectureCopie->lessonResources()->first()->file_path))->toBeTrue()
        ->and($version->module_video)->not->toBe($source->module_video)
        ->and(Str::startsWith($videoCopie, 'modules/videos/modules/module_'.$version->id.'/'))->toBeTrue()
        ->and(Storage::disk('public')->exists($videoCopie))->toBeTrue()
        ->and(Storage::disk('public')->exists($videoSource))->toBeTrue();
});

it('publie une nouvelle version sans déplacer automatiquement les groupes', function () {
    extract(donneesCatalogueVersionne());

    $source = Module::query()->create([
        'category_id' => $categorieId,
        'subcategory_id' => $sousCategorieId,
        'formateur_id' => $formateur->id,
        'created_by' => $admin->id,
        'module_title' => 'Catalogue groupes',
        'module_name' => 'Catalogue groupes',
        'module_name_slug' => 'catalogue-groupes',
        'is_trainer_authored' => false,
        'publication_state' => Module::PUBLICATION_PUBLISHED,
        'status' => true,
        'version_number' => 1,
        'published_at' => now(),
    ]);
    $section = $source->sections()->create(['section_title' => 'Chapitre', 'position' => 0]);
    $section->lectures()->create([
        'module_id' => $source->id,
        'lecture_title' => 'Leçon',
        'content_type' => 'blocks',
        'content_blocks' => [['type' => 'text', 'html' => '<p>Version un</p>']],
        'position' => 0,
    ]);

    $groupe = Group::query()->create([
        'name' => 'Groupe version '.Str::random(5),
        'instructor_id' => $formateur->id,
        'is_active' => true,
    ]);
    $groupe->modules()->attach($source->id, ['position' => 3]);

    $version = app(CreerVersionFormationCatalogue::class)->execute($source, $admin->id);
    $version = app(PublierVersionFormationCatalogue::class)->execute($version);

    expect($source->fresh()->publication_state)->toBe(Module::PUBLICATION_ARCHIVED)
        ->and($source->fresh()->status)->toBeTrue()
        ->and($version->publication_state)->toBe(Module::PUBLICATION_PUBLISHED)
        ->and($groupe->modules()->pluck('modules.id')->all())->toContain($source->id)
        ->and($groupe->modules()->pluck('modules.id')->all())->not->toContain($version->id);

    $total = app(BasculerGroupesVersionFormation::class)->execute($version, [$groupe->id]);

    expect($total)->toBe(1)
        ->and($groupe->modules()->pluck('modules.id')->all())->toContain($version->id)
        ->and($groupe->modules()->pluck('modules.id')->all())->not->toContain($source->id)
        ->and((int) $groupe->modules()->first()->pivot->position)->toBe(3);
});

it('refuse de publier un chapitre vide', function () {
    extract(donneesCatalogueVersionne());

    $brouillon = Module::query()->create([
        'category_id' => $categorieId,
        'subcategory_id' => $sousCategorieId,
        'created_by' => $admin->id,
        'module_title' => 'Brouillon incomplet',
        'module_name' => 'Brouillon incomplet',
        'module_name_slug' => 'brouillon-incomplet',
        'is_trainer_authored' => false,
        'publication_state' => Module::PUBLICATION_DRAFT,
        'status' => false,
    ]);
    $brouillon->sections()->create(['section_title' => 'Chapitre vide', 'position' => 0]);

    expect(fn () => app(PublierVersionFormationCatalogue::class)->execute($brouillon))
        ->toThrow(ValidationException::class);
});

it('refuse toute affectation directe à un brouillon v2', function () {
    extract(donneesCatalogueVersionne());

    $source = Module::query()->create([
        'category_id' => $categorieId,
        'subcategory_id' => $sousCategorieId,
        'created_by' => $admin->id,
        'module_title' => 'Catalogue à migrer',
        'module_name' => 'Catalogue à migrer',
        'module_name_slug' => 'catalogue-a-migrer-'.Str::random(6),
        'is_trainer_authored' => false,
        'publication_state' => Module::PUBLICATION_PUBLISHED,
        'status' => true,
        'published_at' => now(),
    ]);
    $groupe = Group::query()->create([
        'name' => 'Groupe épinglé '.Str::random(6),
        'instructor_id' => $formateur->id,
        'is_active' => true,
    ]);
    $groupe->modules()->attach($source->id, ['position' => 1]);
    $brouillonV2 = app(CreerVersionFormationCatalogue::class)->execute($source, $admin->id);
    $retour = route('admin.formations.constructeur.edit', $brouillonV2);

    $this->actingAs($admin)
        ->from($retour)
        ->put(route('admin.formations.constructeur.groups.sync', $brouillonV2), [
            'group_ids' => [$groupe->id],
        ])
        ->assertRedirect($retour)
        ->assertSessionHasErrors('group_ids');

    expect($brouillonV2->groups()->count())->toBe(0)
        ->and($groupe->modules()->pluck('modules.id')->all())->toContain($source->id)
        ->and($groupe->modules()->pluck('modules.id')->all())->not->toContain($brouillonV2->id);
});

it('affecte une version publiée aux nouveaux groupes et réserve les migrations à la bascule', function () {
    extract(donneesCatalogueVersionne());

    $source = Module::query()->create([
        'category_id' => $categorieId,
        'subcategory_id' => $sousCategorieId,
        'created_by' => $admin->id,
        'module_title' => 'Catalogue affectations',
        'module_name' => 'Catalogue affectations',
        'module_name_slug' => 'catalogue-affectations-'.Str::random(6),
        'is_trainer_authored' => false,
        'publication_state' => Module::PUBLICATION_PUBLISHED,
        'status' => true,
        'published_at' => now(),
    ]);
    $section = $source->sections()->create(['section_title' => 'Chapitre', 'position' => 0]);
    $section->lectures()->create([
        'module_id' => $source->id,
        'lecture_title' => 'Leçon publiable',
        'content_type' => 'blocks',
        'content_blocks' => [['type' => 'divider']],
        'position' => 0,
    ]);

    $groupeAncienneVersion = Group::query()->create([
        'name' => 'Groupe ancienne version '.Str::random(6),
        'instructor_id' => $formateur->id,
        'is_active' => true,
    ]);
    $nouveauGroupe = Group::query()->create([
        'name' => 'Nouveau groupe '.Str::random(6),
        'instructor_id' => $formateur->id,
        'is_active' => true,
    ]);
    $groupeArchiveInterdit = Group::query()->create([
        'name' => 'Groupe archive interdit '.Str::random(6),
        'instructor_id' => $formateur->id,
        'is_active' => true,
    ]);
    $groupeAncienneVersion->modules()->attach($source->id, ['position' => 1]);

    $versionPubliee = app(CreerVersionFormationCatalogue::class)->execute($source, $admin->id);
    $versionPubliee = app(PublierVersionFormationCatalogue::class)->execute($versionPubliee);
    $retour = route('admin.formations.constructeur.edit', $versionPubliee);

    $this->actingAs($admin)
        ->from($retour)
        ->put(route('admin.formations.constructeur.groups.sync', $versionPubliee), [
            'group_ids' => [$nouveauGroupe->id],
        ])
        ->assertRedirect($retour)
        ->assertSessionDoesntHaveErrors();

    $this->actingAs($admin)
        ->from($retour)
        ->put(route('admin.formations.constructeur.groups.sync', $versionPubliee), [
            'group_ids' => [$nouveauGroupe->id, $groupeAncienneVersion->id],
        ])
        ->assertRedirect($retour)
        ->assertSessionHasErrors('group_ids');

    $this->actingAs($admin)
        ->put(route('admin.formations.constructeur.groups.sync', $source), [
            'group_ids' => [$groupeArchiveInterdit->id],
        ])
        ->assertForbidden();

    expect($versionPubliee->groups()->pluck('groups.id')->all())->toBe([$nouveauGroupe->id])
        ->and($groupeAncienneVersion->modules()->pluck('modules.id')->all())->toContain($source->id)
        ->and($groupeAncienneVersion->modules()->pluck('modules.id')->all())->not->toContain($versionPubliee->id)
        ->and($groupeArchiveInterdit->modules()->count())->toBe(0);
});
