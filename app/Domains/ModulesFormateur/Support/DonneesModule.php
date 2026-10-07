<?php

namespace App\Domains\ModulesFormateur\Support;

use App\Models\ModuleLecture;
use App\Models\ModuleSection;
use App\Models\Competency;
use App\Models\ScormPackageVersion;
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

                return $block;
            })
            ->all();
    }
}
