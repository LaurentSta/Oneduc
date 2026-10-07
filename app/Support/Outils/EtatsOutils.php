<?php

namespace App\Support\Outils;

use App\Models\OutilEtat;
use Illuminate\Support\Facades\Cache;

/**
 * Point unique de vérité pour savoir si un outil est activé, modifiable
 * depuis l'administration (table outil_etats) plutôt que via le .env.
 */
class EtatsOutils
{
    private const CLE_CACHE = 'outils.etats_actifs';

    /**
     * Valeur par défaut d'un outil tant qu'aucune ligne n'existe en base.
     * PowerPoint vers module reste désactivé par défaut (bug d'affichage
     * connu, cf. historique de la tuile dans outils/index.blade.php).
     */
    public const DEFAUTS = [
        'minuteur' => true,
        'vraifaux' => true,
        'echelle' => true,
        'buzzer' => true,
        'composants' => true,
        'carrousel' => true,
        'memoire' => true,
        'tri_cartes' => true,
        'pendu' => true,
        'cartes_retourner' => true,
        'nuage_mots' => true,
        'quiz_direct' => true,
        'tableau_blanc' => true,
        'mur_questions' => true,
        'sondage' => true,
        'roue_aleatoire' => true,
        'emargement' => true,
        'powerpoint_module' => false,
    ];

    public const LIBELLES = [
        'nuage_mots' => 'Nuage de mots',
        'quiz_direct' => 'Quiz en direct',
        'tableau_blanc' => 'Tableau blanc',
        'mur_questions' => 'Mur de questions',
        'sondage' => 'Sondage',
        'vraifaux' => 'Vrai ou Faux',
        'buzzer' => 'Buzzer',
        'roue_aleatoire' => 'Roue aléatoire',
        'minuteur' => 'Minuteur',
        'echelle' => 'Échelle de positionnement',
        'composants' => 'Trouve le composant',
        'emargement' => 'Émargement',
        'powerpoint_module' => 'PowerPoint vers module',
        'carrousel' => 'Carrousel',
        'memoire' => 'Mémoire',
        'pendu' => 'Pendu',
        'cartes_retourner' => 'Cartes à retourner',
        'tri_cartes' => 'Tri de cartes',
    ];

    public static function actif(string $cle): bool
    {
        return self::tout()[$cle] ?? self::DEFAUTS[$cle] ?? true;
    }

    /**
     * @return array<string, bool>
     */
    public static function tout(): array
    {
        return Cache::rememberForever(self::CLE_CACHE, function (): array {
            return array_merge(self::DEFAUTS, OutilEtat::query()->pluck('actif', 'cle')->all());
        });
    }

    public static function viderCache(): void
    {
        Cache::forget(self::CLE_CACHE);
    }
}
