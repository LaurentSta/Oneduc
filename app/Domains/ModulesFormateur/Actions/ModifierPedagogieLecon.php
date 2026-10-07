<?php

namespace App\Domains\ModulesFormateur\Actions;

use App\Models\ModuleLecture;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ModifierPedagogieLecon
{
    public function execute(ModuleLecture $lecon, array $donnees): void
    {
        $quizActif = filter_var($donnees['quiz_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $nombreQuestions = $lecon->quizQuestions()->where('is_active', true)->count();
        $validation = Validator::make($donnees, [
            'duration' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'quiz_enabled' => ['required', 'boolean'],
            'quiz_questions_per_attempt' => [
                Rule::requiredIf($quizActif), 'nullable', 'integer',
                $quizActif ? 'min:1' : 'min:0',
            ],
            'objectives_present' => ['required', 'accepted'],
            'objectives' => ['nullable', 'array', 'max:30'],
            'objectives.*.id' => [
                'nullable', 'integer', 'distinct',
                Rule::exists('lecture_objectives', 'id')->where('lecture_id', $lecon->id),
            ],
            'objectives.*.title' => ['required', 'string', 'max:255'],
            'objectives.*.description' => ['nullable', 'string', 'max:3000'],
            'objectives.*.competency_ids' => ['nullable', 'array', 'max:40'],
            'objectives.*.competency_ids.*' => ['integer', 'distinct', 'exists:competencies,id'],
        ], [
            'objectives.*.title.required' => 'Renseignez chaque objectif ou supprimez sa ligne vide.',
            'objectives.*.id.exists' => 'Cet objectif ne fait pas partie de cette leçon.',
        ]);

        $validation->after(function ($validation) use ($donnees, $quizActif, $nombreQuestions) {
            if ($quizActif && ($nombreQuestions === 0 || (int) ($donnees['quiz_questions_per_attempt'] ?? 0) > $nombreQuestions)) {
                $validation->errors()->add('quiz_questions_per_attempt', 'Ajoutez et activez suffisamment de questions dans la banque avant de configurer le quiz.');
            }
        });
        $valide = $validation->validate();

        DB::transaction(function () use ($lecon, $valide, $quizActif) {
            $lecon->update([
                'duration' => $valide['duration'] ?? null,
                'quiz_enabled' => $quizActif,
                'quiz_questions_per_attempt' => $quizActif ? (int) $valide['quiz_questions_per_attempt'] : 0,
            ]);

            $objectifsConserves = [];
            foreach ($valide['objectives'] ?? [] as $position => $donneesObjectif) {
                $objectif = empty($donneesObjectif['id'])
                    ? $lecon->objectives()->make()
                    : $lecon->objectives()->findOrFail($donneesObjectif['id']);
                $objectif->fill([
                    'title' => trim($donneesObjectif['title']),
                    'description' => $donneesObjectif['description'] ?? null,
                    'position' => $position + 1,
                ])->save();
                $objectif->competencies()->sync(
                    collect($donneesObjectif['competency_ids'] ?? [])
                        ->mapWithKeys(fn ($id, $ordre) => [$id => ['position' => $ordre + 1]])
                        ->all()
                );
                $objectifsConserves[] = $objectif->id;
            }

            foreach ($lecon->objectives()->whereNotIn('id', $objectifsConserves)->get() as $objectif) {
                $objectif->competencies()->detach();
                $objectif->delete();
            }
        });
    }
}
