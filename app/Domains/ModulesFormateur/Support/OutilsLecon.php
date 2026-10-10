<?php

namespace App\Domains\ModulesFormateur\Support;

use App\Models\ComponentFinderActivity;
use App\Models\ModuleLecture;
use App\Support\Outils\EtatsOutils;
use Illuminate\Support\Str;

/**
 * Outils d'animation intégrables dans une leçon comme activité autonome,
 * sans session de groupe ni code d'accès. Deux façons de porter le contenu :
 * - outil « lié » (Zone de clic) : le bloc ne garde que `activite_id` et pointe
 *   vers la bibliothèque du formateur — modifier l'activité dans l'outil la
 *   modifie dans toutes les leçons ;
 * - sinon, le contenu est saisi dans le bloc (`configuration`).
 */
class OutilsLecon
{
    private const MAX_CARTES = 100;

    private const MAX_AFFIRMATIONS = 50;

    /**
     * Clé du bloc (la même que dans le registre des parcours) => clé EtatsOutils.
     */
    private const OUTILS = [
        'cartes-retourner' => [
            'etat' => 'cartes_retourner',
            'description' => 'Le stagiaire retourne chaque carte pour découvrir la réponse.',
            'consigne' => 'Cliquez sur une carte pour la retourner.',
        ],
        'vrai-faux' => [
            'etat' => 'vraifaux',
            'description' => 'Le stagiaire répond à chaque affirmation, puis lit l’explication.',
            'consigne' => 'Pour chaque affirmation, choisissez Vrai ou Faux.',
        ],
        'composants' => [
            'etat' => 'composants',
            'description' => 'Le stagiaire clique sur l’image pour retrouver chaque élément demandé.',
            'consigne' => 'Cliquez sur l’image, à l’endroit de l’élément demandé.',
            'lie' => true,
        ],
    ];

    public static function reconnait(string $cle): bool
    {
        return isset(self::OUTILS[$cle]);
    }

    public static function lie(string $cle): bool
    {
        return (bool) (self::OUTILS[$cle]['lie'] ?? false);
    }

    public static function actif(string $cle): bool
    {
        return self::reconnait($cle) && EtatsOutils::actif(self::OUTILS[$cle]['etat']);
    }

    public static function libelle(string $cle): string
    {
        return EtatsOutils::LIBELLES[self::OUTILS[$cle]['etat'] ?? ''] ?? $cle;
    }

    /**
     * Affichée au stagiaire quand le formateur n'a pas rédigé la sienne.
     */
    public static function consigneParDefaut(string $cle): string
    {
        return self::OUTILS[$cle]['consigne'] ?? '';
    }

    /**
     * Les outils désactivés restent décrits pour que leurs blocs déjà posés
     * demeurent modifiables dans l'éditeur.
     *
     * @return array<int, array{cle: string, libelle: string, description: string, actif: bool}>
     */
    public static function pourEditeur(): array
    {
        return collect(self::OUTILS)
            ->map(fn (array $outil, string $cle): array => [
                'cle' => $cle,
                'libelle' => self::libelle($cle),
                'description' => $outil['description'],
                'actif' => self::actif($cle),
                'lie' => self::lie($cle),
            ])
            ->values()
            ->all();
    }

    /**
     * Les éléments incomplets sont conservés (le formateur est peut-être en
     * train de les saisir) : c'est l'affichage qui les écarte, via elements().
     *
     * @param  array<string, mixed>  $bloc
     * @return array<string, mixed>|null
     */
    public static function nettoyer(array $bloc, int $moduleId): ?array
    {
        $outil = $bloc['outil'] ?? null;
        if (is_string($outil) && self::lie($outil)) {
            $activiteId = self::activiteAutorisee($bloc, $moduleId);

            return $activiteId === null ? null : [
                'type' => 'outil',
                'outil' => $outil,
                'activite_id' => $activiteId,
                'obligatoire' => filter_var($bloc['obligatoire'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $brut = is_array($bloc['configuration'] ?? null) ? $bloc['configuration'] : [];
        [$cleElements, $elements] = match ($outil) {
            'cartes-retourner' => ['cartes', self::cartes($brut['cartes'] ?? null)],
            'vrai-faux' => ['affirmations', self::affirmations($brut['affirmations'] ?? null)],
            default => [null, []],
        };
        if ($cleElements === null) {
            return null;
        }

        $titre = self::texte($brut['titre'] ?? null, 255);
        $consigne = self::texte($brut['consigne'] ?? null, 2000);
        if ($titre === '' && $consigne === '' && $elements === []) {
            return null;
        }

        return [
            'type' => 'outil',
            'outil' => $outil,
            'configuration' => ['titre' => $titre, 'consigne' => $consigne, $cleElements => $elements],
            'obligatoire' => filter_var($bloc['obligatoire'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * Ce que la leçon affiche : le contenu du bloc, ou celui de l'activité liée
     * tel qu'il est aujourd'hui dans la bibliothèque.
     *
     * @param  array<string, mixed>  $bloc
     * @return array<string, mixed>
     */
    public static function configuration(array $bloc): array
    {
        if (! self::lie((string) ($bloc['outil'] ?? ''))) {
            return is_array($bloc['configuration'] ?? null) ? $bloc['configuration'] : [];
        }

        $activite = self::activite($bloc);

        return $activite === null ? [] : [
            'titre' => $activite->title,
            'consigne' => '',
            'image' => $activite->image_path,
            'zones' => $activite->zones ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $bloc
     */
    public static function activite(array $bloc): ?ComponentFinderActivity
    {
        $id = self::activiteId($bloc);

        return $id === null ? null : ComponentFinderActivity::query()->find($id);
    }

    /**
     * Leçons (de formations non supprimées) qui utilisent chaque activité d'un outil lié.
     *
     * @return array<int, array<int, array{lecon: string, formation: string}>>
     */
    public static function usages(string $outil): array
    {
        $usages = [];

        ModuleLecture::query()
            ->whereHas('module')
            ->whereJsonContains('content_blocks', ['outil' => $outil])
            ->with('module:id,module_title')
            ->get(['id', 'module_id', 'lecture_title', 'content_blocks'])
            ->each(function (ModuleLecture $lecon) use (&$usages, $outil): void {
                foreach ($lecon->content_blocks ?? [] as $bloc) {
                    if (($bloc['type'] ?? null) === 'outil' && ($bloc['outil'] ?? null) === $outil && ($id = self::activiteId($bloc)) !== null) {
                        $usages[$id][$lecon->id] = [
                            'lecon' => (string) $lecon->lecture_title,
                            'formation' => (string) $lecon->module?->module_title,
                        ];
                    }
                }
            });

        return array_map('array_values', $usages);
    }

    /**
     * Éléments complets, donc présentables au stagiaire.
     *
     * @param  array<string, mixed>  $bloc
     * @return array<int, array<string, mixed>>
     */
    public static function elements(array $bloc): array
    {
        $configuration = self::configuration($bloc);

        return match ($bloc['outil'] ?? null) {
            'cartes-retourner' => collect($configuration['cartes'] ?? [])
                ->filter(fn ($carte) => is_array($carte) && ($carte['recto'] ?? '') !== '' && ($carte['verso'] ?? '') !== '')
                ->values()
                ->all(),
            'vrai-faux' => collect($configuration['affirmations'] ?? [])
                ->filter(fn ($affirmation) => is_array($affirmation) && ($affirmation['texte'] ?? '') !== '')
                ->values()
                ->all(),
            'composants' => ($configuration['image'] ?? '') === '' ? [] : collect($configuration['zones'] ?? [])
                ->filter(fn ($zone) => is_array($zone) && ($zone['label'] ?? '') !== '')
                ->values()
                ->all(),
            default => [],
        };
    }

    /**
     * Les images passent par la route media.storage, comme le reste des leçons.
     */
    public static function urlImage(string $chemin): string
    {
        return route('media.storage', ['path' => $chemin], false);
    }

    /**
     * @param  array<string, mixed>  $bloc
     */
    public static function affichable(array $bloc): bool
    {
        return ($bloc['type'] ?? null) === 'outil'
            && is_string($bloc['outil'] ?? null)
            && self::actif($bloc['outil'])
            && self::elements($bloc) !== [];
    }

    /**
     * Seules les activités réellement affichées peuvent retenir le stagiaire :
     * un outil désactivé ou vide ne doit jamais bloquer la suite de la leçon.
     *
     * @param  array<int, mixed>  $blocs
     */
    public static function nombreObligatoires(array $blocs): int
    {
        return collect($blocs)
            ->filter(fn ($bloc) => is_array($bloc) && ($bloc['obligatoire'] ?? false) && self::affichable($bloc))
            ->count();
    }

    /**
     * @return array<int, array{recto: string, verso: string}>
     */
    private static function cartes(mixed $cartes): array
    {
        return collect(is_array($cartes) ? $cartes : [])
            ->filter(fn ($carte) => is_array($carte))
            ->map(fn (array $carte): array => [
                'recto' => self::texte($carte['recto'] ?? null, 2000),
                'verso' => self::texte($carte['verso'] ?? null, 2000),
            ])
            ->filter(fn (array $carte): bool => $carte['recto'] !== '' || $carte['verso'] !== '')
            ->take(self::MAX_CARTES)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{texte: string, reponse: bool, explication: string}>
     */
    private static function affirmations(mixed $affirmations): array
    {
        return collect(is_array($affirmations) ? $affirmations : [])
            ->filter(fn ($affirmation) => is_array($affirmation))
            ->map(fn (array $affirmation): array => [
                'texte' => self::texte($affirmation['texte'] ?? null, 1000),
                'reponse' => filter_var($affirmation['reponse'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'explication' => self::texte($affirmation['explication'] ?? null, 1000),
            ])
            ->filter(fn (array $affirmation): bool => $affirmation['texte'] !== '' || $affirmation['explication'] !== '')
            ->take(self::MAX_AFFIRMATIONS)
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $bloc
     */
    private static function activiteId(array $bloc): ?int
    {
        if (is_numeric($bloc['activite_id'] ?? null) && (int) $bloc['activite_id'] > 0) {
            return (int) $bloc['activite_id'];
        }

        // Blocs posés le 10 octobre 2026, quand la zone était encore copiée dans
        // la leçon : l'activité d'origine se retrouve par le nom de son image.
        $image = $bloc['configuration']['image'] ?? null;
        if (is_string($image) && $image !== '') {
            return ComponentFinderActivity::query()->where('image_path', 'component_finder/'.basename($image))->value('id');
        }

        return null;
    }

    /**
     * Un concepteur ne pose que ses propres activités. Une référence déjà
     * présente dans la formation (reprise d'un collègue) est conservée telle quelle.
     *
     * @param  array<string, mixed>  $bloc
     */
    private static function activiteAutorisee(array $bloc, int $moduleId): ?int
    {
        $activite = self::activite($bloc);
        if ($activite === null) {
            return null;
        }
        if ((int) $activite->formateur_id === (int) auth()->id()) {
            return $activite->id;
        }

        $dejaPresente = collect(self::usagesDansModule((string) $bloc['outil'], $moduleId))->contains($activite->id);

        return $dejaPresente ? $activite->id : null;
    }

    /**
     * @return array<int, int>
     */
    private static function usagesDansModule(string $outil, int $moduleId): array
    {
        return ModuleLecture::query()
            ->where('module_id', $moduleId)
            ->whereJsonContains('content_blocks', ['outil' => $outil])
            ->pluck('content_blocks')
            ->flatMap(fn ($blocs) => collect($blocs)
                ->filter(fn ($bloc) => is_array($bloc) && ($bloc['outil'] ?? null) === $outil)
                ->map(fn (array $bloc) => self::activiteId($bloc)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Texte brut : pas de strip_tags (« <b> » ou « 3 < 5 » sont des contenus
     * légitimes), l'échappement est fait à l'affichage.
     */
    private static function texte(mixed $valeur, int $longueurMax): string
    {
        return Str::limit(trim(is_scalar($valeur) ? (string) $valeur : ''), $longueurMax, '');
    }
}
