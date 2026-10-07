<?php

namespace App\Domains\ModulesFormateur\Actions;

use App\Jobs\ConvertLectureSlides;
use App\Models\ModuleLecture;
use App\Services\Scorm\ScormImporter;
use App\Support\LearningAssetPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ImporterSupportLecon
{
    public function __construct(private readonly ScormImporter $importeur) {}

    public function execute(ModuleLecture $lecon, Request $requete): void
    {
        $type = $requete->input('support_type');
        $requete->validate([
            'support_type' => ['required', Rule::in(['scorm', 'slides'])],
            'support_file' => [
                'required', 'file',
                $type === 'scorm' ? 'mimes:zip' : 'mimes:ppt,pptx,pdf',
                $type === 'scorm' ? 'max:512000' : 'max:51200',
            ],
        ]);

        if ($lecon->content_type === 'blocks' && ! empty($lecon->content_blocks)) {
            throw ValidationException::withMessages([
                'support_file' => 'Créez une nouvelle leçon pour importer ce support : cette leçon contient déjà des blocs.',
            ]);
        }
        if (in_array($lecon->slides_status, ['pending', 'processing'], true)) {
            throw ValidationException::withMessages(['support_file' => 'Attendez la fin de la conversion en cours avant de remplacer ce support.']);
        }

        $fichier = $requete->file('support_file');
        if ($type === 'scorm') {
            // Un nouvel emplacement évite de modifier le paquet partagé avec une copie
            // ou une version publiée. Aucun répertoire historique n'est nettoyé.
            try {
                $resultat = $this->importeur->importToFolder(
                    $fichier,
                    LearningAssetPath::lessonImportFolder((int) $lecon->id).'/import_'.Str::uuid(),
                );
            } catch (\RuntimeException $erreur) {
                throw ValidationException::withMessages(['support_file' => 'Le fichier SCORM ne peut pas être importé. Vérifiez son manifeste et son contenu.']);
            }
            $lecon->update([
                'content_type' => 'scorm',
                'scorm_path' => $resultat->relative_index_path,
                'scorm_package_id' => $resultat->package_id,
                'scorm_package_version_id' => $resultat->version_id,
                'use_active_scorm_version' => false,
            ]);

            return;
        }

        $chemin = $fichier->storeAs(
            'slides/sources/lecture_'.$lecon->id,
            'lecture_'.$lecon->id.'_'.Str::uuid().'.'.$fichier->getClientOriginalExtension(),
            'local',
        );
        $lecon->update([
            'content_type' => 'slides',
            'slides_status' => 'pending',
            'slides_error' => null,
            'slides_source_path' => $chemin,
        ]);
        ConvertLectureSlides::dispatch($lecon->id, $chemin)->afterResponse();
    }

    public function relancer(ModuleLecture $lecon): void
    {
        if ($lecon->content_type !== 'slides' || $lecon->slides_status !== 'failed'
            || ! $lecon->slides_source_path || ! Storage::disk('local')->exists($lecon->slides_source_path)) {
            throw ValidationException::withMessages(['support_file' => 'Réimportez votre présentation pour relancer sa conversion.']);
        }
        $lecon->update(['slides_status' => 'pending', 'slides_error' => null]);
        ConvertLectureSlides::dispatch($lecon->id, $lecon->slides_source_path)->afterResponse();
    }
}
