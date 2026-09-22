<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('les réponses portent les protections compatibles avec les iframes internes', function () {
    $this->get('/up')->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Content-Security-Policy', "frame-ancestors 'self'")
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeaderMissing('Strict-Transport-Security');
});

test('HSTS est activé uniquement en production HTTPS lorsque configuré', function () {
    $this->app->instance('env', 'production');
    config(['securite.hsts_max_age' => 31536000]);

    $this->get('http://localhost/up')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/up')->assertOk()
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});

test('le contrôle de santé signale une base indisponible sans révéler ses détails', function () {
    config(['app.debug' => false]);
    DB::shouldReceive('select')->once()->with('SELECT 1')->andThrow(new RuntimeException('detail-confidentiel-test'));

    $this->get('/up')->assertStatus(500)->assertDontSee('detail-confidentiel-test');
});

test('le contrôle de santé signale un cache indisponible sans révéler ses détails', function () {
    config(['app.debug' => false]);
    Cache::shouldReceive('get')->once()->with('oneduc:controle-sante')->andThrow(new RuntimeException('detail-confidentiel-test'));

    $this->get('/up')->assertStatus(500)->assertDontSee('detail-confidentiel-test');
});

test('le contrôle de livraison refuse une configuration de développement', function () {
    config(['app.debug' => true, 'session.secure' => false]);

    $this->artisan('oneduc:verifier-production')
        ->expectsOutput('[ERREUR] APP_DEBUG doit être désactivé')
        ->expectsOutput('[ERREUR] Les cookies de session doivent être sécurisés')
        ->assertFailed();
});

test('le contrôle de livraison accepte une configuration complète et détecte Vite en développement', function () {
    $publicInitial = public_path();
    $publicTemporaire = sys_get_temp_dir().'/oneduc-production-'.bin2hex(random_bytes(6));
    mkdir($publicTemporaire.'/build', 0700, true);
    file_put_contents($publicTemporaire.'/build/manifest.json', '{}');
    $this->app->usePublicPath($publicTemporaire);
    $this->app->instance('env', 'production');
    config([
        'app.debug' => false,
        'app.key' => 'cle-de-test-non-secrete',
        'app.url' => 'https://example.test',
        'session.secure' => true,
        'session.http_only' => true,
        'session.same_site' => 'lax',
        'session.encrypt' => true,
        'session.driver' => 'database',
        'database.default' => 'mysql',
        'cache.default' => 'database',
        'queue.default' => 'database',
        'mail.default' => 'smtp',
    ]);

    try {
        $this->artisan('oneduc:verifier-production')->assertSuccessful();
        file_put_contents($publicTemporaire.'/hot', 'http://localhost:5173');
        $this->artisan('oneduc:verifier-production')
            ->expectsOutput('[ERREUR] Le fichier public/hot doit être absent')
            ->assertFailed();
    } finally {
        $this->app->usePublicPath($publicInitial);
        @unlink($publicTemporaire.'/hot');
        unlink($publicTemporaire.'/build/manifest.json');
        rmdir($publicTemporaire.'/build');
        rmdir($publicTemporaire);
    }
});
