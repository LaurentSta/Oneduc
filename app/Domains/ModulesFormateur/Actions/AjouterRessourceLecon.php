<?php

namespace App\Domains\ModulesFormateur\Actions;

use App\Models\LessonResource;
use App\Models\ModuleLecture;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AjouterRessourceLecon
{
    public function execute(ModuleLecture $lecon, Request $requete): LessonResource
    {
        $donnees = $requete->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'resource_file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,avif,pdf,doc,docx,odt,txt,rtf,xls,xlsx,ods,ppt,pptx,odp,csv', 'max:51200'],
            'is_visible_to_stagiaire' => ['nullable', 'boolean'],
        ]);
        $fichier = $requete->file('resource_file');
        $nom = Str::slug(pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'resource';
        $chemin = $fichier->storeAs(
            'module-resources/module_'.$lecon->module_id,
            now()->format('Ymd_His').'_'.Str::random(8).'_'.$nom.'.'.strtolower($fichier->getClientOriginalExtension()),
            'public',
        );

        return $lecon->module->moduleResources()->create([
            'lecture_id' => $lecon->id,
            'title' => trim((string) ($donnees['title'] ?? '')) ?: pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME),
            'file_path' => $chemin,
            'original_name' => $fichier->getClientOriginalName(),
            'mime_type' => $fichier->getMimeType(),
            'file_size' => $fichier->getSize(),
            'is_visible_to_stagiaire' => $requete->boolean('is_visible_to_stagiaire'),
            'position' => ((int) $lecon->module->moduleResources()->max('position')) + 1,
        ]);
    }
}
