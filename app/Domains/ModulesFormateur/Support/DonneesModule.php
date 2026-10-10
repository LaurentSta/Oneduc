<?php

namespace App\Domains\ModulesFormateur\Support;

use App\Models\Competency;
use App\Models\ComponentFinderActivity;
use App\Models\ModuleLecture;
use App\Models\ModuleSection;
use App\Models\ScormPackageVersion;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class DonneesModule
{
    public function conception(ModuleLecture $lecture): array
    {
        $lecture->load(['section', 'objectives.competencies', 'scormPackage.activeVersion', 'scormPackageVersion']);
        $module = $lecture->module;
        $module->load(['sections.lectures' => fn ($query) => $query->orderBy('position')->orderBy('id')]);

        return [
            'module' => $module,
            'section' => $lecture->section,
            'lecture' => $lecture,
            'initialBlocks' => $this->resolvedContentBlocks($lecture),
            'outilsLecon' => OutilsLecon::pourEditeur(),
            'zonesDeClic' => $this->zonesDeClic(),
            'competences' => Competency::query()->orderBy('label')->get(),
            'nombreQuestionsActives' => $lecture->quizQuestions()->where('is_active', true)->count(),
            'ressources' => $module->moduleResources()->get(),
        ];
    }

    public function apercu(ModuleLecture $lecture): array
    {
        $lecture->load(['section', 'objectives', 'quizQuestions.options']);
        $module = $lecture->module;
        $module->load(['sections.lectures' => fn ($query) => $query->orderBy('position')->orderBy('id')]);
        $slides = [];
        if ($lecture->content_type === 'slides' && $lecture->slides_status === 'ready' && $lecture->slides_path) {
            $slides = collect(Storage::disk('public')->files($lecture->slides_path))
                ->filter(fn ($file) => preg_match('/^slide[-_]\d+\.jpg$/i', basename($file)))
                ->sortBy(fn ($file) => (int) preg_replace('/\D/', '', basename($file)))
                ->map(fn ($file) => route('media.storage', ['path' => $file], false))->values()->all();
        }

        return [
            'module' => $module, 'section' => $lecture->section, 'lecture' => $lecture,
            'initialBlocks' => $this->resolvedContentBlocks($lecture),
            'slides' => $slides,
            'ressources' => $module->moduleResources()->where('is_visible_to_stagiaire', true)->get(),
        ];
    }

    public function urlScormApercu(ModuleLecture $lecture, ?string $cle): string
    {
        if ($cle !== null) {
            $bloc = collect($lecture->content_blocks ?? [])
                ->first(fn ($bloc) => ($bloc['type'] ?? null) === 'scorm' && ($bloc['content_block_key'] ?? null) === $cle);
            abort_unless($bloc, 404);
            $url = ScormPackageVersion::find($bloc['scorm_package_version_id'] ?? null)?->asset_url;
        } else {
            abort_unless($lecture->content_type === 'scorm', 404);
            $url = $lecture->scorm_asset_url;
        }
        abort_unless($url, 404);

        return $url;
    }

    public function section(ModuleSection $section): array
    {
        return [
            'id' => $section->id,
            'section_title' => $section->section_title,
            'position' => $section->position,
        ];
    }

    public function lecture(ModuleLecture $lecture): array
    {
        return [
            'id' => $lecture->id,
            'section_id' => $lecture->section_id,
            'module_id' => $lecture->module_id,
            'lecture_title' => $lecture->lecture_title,
            'content_type' => $lecture->content_type,
            'content_blocks' => $this->resolvedContentBlocks($lecture),
            'position' => $lecture->position,
        ];
    }

    /**
     * Content blocks with each image block's URL resolved fresh from its media_id,
     * since the URL is never persisted (only the media_id is).
     */
    public function resolvedContentBlocks(ModuleLecture $lecture): array
    {
        $mediaById = $lecture->module->getMedia('lesson-images')->keyBy('id');
        $audioById = $lecture->module->getMedia('lesson-audios')->keyBy('id');

        return collect($lecture->content_blocks ?? [])
            ->map(function (array $block) use ($mediaById, $audioById) {
                if (($block['type'] ?? null) === 'image') {
                    $media = $mediaById->get($block['media_id'] ?? null);
                    $block['url'] = $media?->getUrl('display');
                }

                if (($block['type'] ?? null) === 'audio') {
                    $media = $audioById->get($block['media_id'] ?? null);
                    $block['url'] = $media?->getUrl();
                }

                if (($block['type'] ?? null) === 'scorm') {
                    $version = ScormPackageVersion::find($block['scorm_package_version_id'] ?? null);
                    $block['preview_url'] = $version?->asset_url;
                }

                if (($block['type'] ?? null) === 'outil' && OutilsLecon::lie((string) ($block['outil'] ?? ''))) {
                    // L'éditeur n'a que l'identifiant : on lui joint de quoi présenter l'activité liée.
                    $activite = OutilsLecon::activite($block);
                    $block['activite_id'] = $activite?->id;
                    $block['activite'] = $activite ? $this->zoneDeClic($activite) : null;
                    unset($block['configuration']);
                }

                return $block;
            })
            ->all();
    }

    /**
     * Zones de clic du concepteur connecté, qu'un bloc outil peut pointer.
     * La bibliothèque n'existe que côté formateur.
     *
     * @return array{liste: array<int, array<string, mixed>>, url_creation: ?string}
     */
    private function zonesDeClic(): array
    {
        $utilisateur = auth()->user();

        return [
            'liste' => ComponentFinderActivity::query()
                ->where('formateur_id', $utilisateur?->id)
                ->latest()
                ->get()
                ->map(fn (ComponentFinderActivity $zone): array => $this->zoneDeClic($zone))
                ->all(),
            'url_creation' => $this->bibliothequeAccessible() ? route('formateur.composants.create') : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function zoneDeClic(ComponentFinderActivity $zone): array
    {
        $aMoi = (int) $zone->formateur_id === (int) auth()->id();

        return [
            'id' => $zone->id,
            'titre' => $zone->title,
            'image_url' => OutilsLecon::urlImage($zone->image_path),
            'nombre' => count($zone->zones ?? []),
            'auteur' => $aMoi ? null : $zone->formateur?->name,
            'url_modification' => $aMoi && $this->bibliothequeAccessible()
                ? route('formateur.composants.activities.edit', $zone)
                : null,
        ];
    }

    private function bibliothequeAccessible(): bool
    {
        return auth()->user()?->role === 'formateur' && Route::has('formateur.composants.index');
    }
}
