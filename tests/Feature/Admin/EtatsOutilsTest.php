<?php

use App\Models\User;
use App\Support\Outils\EtatsOutils;

it('laisse un admin désactiver puis réactiver un outil, effet immédiat', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    expect(EtatsOutils::actif('buzzer'))->toBeTrue();

    $this->actingAs($admin)
        ->post(route('admin.outils.toggle', 'buzzer'))
        ->assertRedirect();

    expect(EtatsOutils::actif('buzzer'))->toBeFalse();

    $this->post(route('admin.outils.toggle', 'buzzer'))->assertRedirect();

    expect(EtatsOutils::actif('buzzer'))->toBeTrue();
});

it('refuse de basculer un outil inconnu', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->post(route('admin.outils.toggle', 'inexistant'))
        ->assertNotFound();
});

it('interdit l’accès à un formateur', function () {
    $formateur = User::factory()->create(['role' => 'formateur']);

    $this->actingAs($formateur)->get(route('admin.outils.index'))->assertRedirect();
});
