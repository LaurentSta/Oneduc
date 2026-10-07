<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class VerifierProduction extends Command
{
    protected $signature = 'oneduc:verifier-production';

    protected $description = 'Vérifier la configuration de livraison sans afficher les secrets ni modifier les données';

    public function handle(): int
    {
        $controles = [
            'APP_ENV doit être production' => app()->environment('production'),
            'APP_DEBUG doit être désactivé' => config('app.debug') === false,
            'APP_KEY doit être renseignée' => filled(config('app.key')),
            'APP_URL doit utiliser HTTPS' => parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https',
            'Les cookies de session doivent être sécurisés' => config('session.secure') === true,
            'Les cookies de session doivent être HttpOnly' => config('session.http_only') === true,
            'SameSite doit être lax ou strict' => in_array(config('session.same_site'), ['lax', 'strict'], true),
            'Les sessions doivent être chiffrées' => config('session.encrypt') === true,
            'Les sessions doivent être persistantes côté serveur' => in_array(config('session.driver'), ['database', 'redis', 'file', 'memcached'], true),
            'La base doit utiliser MySQL ou MariaDB' => in_array(config('database.default'), ['mysql', 'mariadb'], true),
            'Le cache doit être persistant' => in_array(config('cache.default'), ['database', 'redis', 'file', 'memcached'], true),
            'La file de tâches doit être asynchrone' => in_array(config('queue.default'), ['database', 'redis', 'sqs', 'beanstalkd'], true),
            'Le transport mail doit envoyer réellement les messages' => ! in_array(config('mail.default'), [null, 'log', 'array'], true),
            'Le manifeste Vite doit être présent' => is_file(public_path('build/manifest.json')),
            'Le fichier public/hot doit être absent' => ! file_exists(public_path('hot')),
            'storage doit être accessible en écriture' => is_writable(storage_path()),
            'bootstrap/cache doit être accessible en écriture' => is_writable(base_path('bootstrap/cache')),
        ];

        foreach ($controles as $libelle => $valide) {
            $this->line(($valide ? '[OK] ' : '[ERREUR] ').$libelle);
        }

        $this->comment('Contrôle local uniquement : valider aussi sauvegarde/restauration, /up, workers et parcours en préproduction.');

        return in_array(false, $controles, true) ? self::FAILURE : self::SUCCESS;
    }
}
