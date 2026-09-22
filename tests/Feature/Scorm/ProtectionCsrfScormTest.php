<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

beforeEach(function () {
    // Laravel désactive normalement la vérification CSRF pendant les tests.
    $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app['encrypter']) extends PreventRequestForgery
    {
        protected function runningUnitTests()
        {
            return false;
        }
    });
    $this->actingAs(User::factory()->create(['role' => 'stagiaire', 'status' => true]));
});

test('refuse les écritures SCORM sans jeton depuis une origine tierce', function (string $route) {
    $this->withSession(['_token' => 'jeton-session'])
        ->postJson($route, [], ['Sec-Fetch-Site' => 'cross-site'])
        ->assertStatus(419);
})->with(['/scorm/save-progress', '/scorm/progress', '/scorm/save-block-progress', '/scorm/evaluation-progress']);

test('refuse un ancien jeton SCORM après renouvellement de session', function (string $route) {
    $this->withSession(['_token' => 'nouveau-jeton'])
        ->postJson($route, [], ['X-CSRF-TOKEN' => 'ancien-jeton'])
        ->assertStatus(419);
})->with(['/scorm/save-progress', '/scorm/progress', '/scorm/save-block-progress', '/scorm/evaluation-progress']);

test('accepte le jeton SCORM et atteint la validation du contrôleur', function (string $route) {
    $this->withSession(['_token' => 'jeton-session'])
        ->postJson($route, [], ['X-CSRF-TOKEN' => 'jeton-session'])
        ->assertStatus(400);
})->with(['/scorm/save-progress', '/scorm/progress', '/scorm/save-block-progress', '/scorm/evaluation-progress']);

test('le wrapper de bloc SCORM expose le jeton à son iframe', function () {
    $this->withSession(['_token' => 'jeton-wrapper']);

    $this->view('shared.scorm_block_wrapper', [
        'lectureId' => 1,
        'contentBlockKey' => 'bloc-1',
        'isAlreadyDone' => false,
        'scormUrl' => '/modules/exemple/index.html',
    ])->assertSee('name="csrf-token" content="jeton-wrapper"', false);
});
