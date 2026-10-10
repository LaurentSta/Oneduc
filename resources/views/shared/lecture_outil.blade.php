{{-- resources/views/shared/lecture_outil.blade.php --}}
{{-- Cadre commun d'une activité « outil » posée dans une leçon ; le corps propre
     à chaque outil vit dans shared/lecture_outils/ et appelle terminer(). --}}
@php
    $outil = (string) $block['outil'];
    $configuration = \App\Domains\ModulesFormateur\Support\OutilsLecon::configuration($block);
    $elements = \App\Domains\ModulesFormateur\Support\OutilsLecon::elements($block);
    $libelleOutil = \App\Domains\ModulesFormateur\Support\OutilsLecon::libelle($outil);
    $consigne = ($configuration['consigne'] ?? '') !== ''
        ? $configuration['consigne']
        : \App\Domains\ModulesFormateur\Support\OutilsLecon::consigneParDefaut($outil);
    $obligatoire = (bool) ($block['obligatoire'] ?? false);
    // Gabarit épuré des outils liés à une bibliothèque : ni nom d'outil ni titre
    // (celui de l'activité sert au formateur à la retrouver, pas au stagiaire),
    // et la consigne passe derrière un « ? » que le corps de l'outil place lui-même.
    $epure = \App\Domains\ModulesFormateur\Support\OutilsLecon::lie($outil);
@endphp

<div role="group" aria-label="Activité : {{ $libelleOutil }}"
     class="mb-6 rounded-2xl border border-gray-200 bg-white p-6"
     x-data="{
         obligatoire: @js($obligatoire),
         termine: false,
         terminer() {
             if (this.termine) return;
             this.termine = true;
             if (this.obligatoire) this.$dispatch('outil-termine');
         },
     }">
    @unless($epure)
        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-orangeone">{{ $libelleOutil }}</p>
        @if(($configuration['titre'] ?? '') !== '')
            <p class="mb-2 text-lg leading-relaxed text-gray-900">{{ $configuration['titre'] }}</p>
        @endif
        <p class="mb-4 whitespace-pre-line text-sm text-gray-600">{{ $consigne }}</p>
    @endunless

    @include('shared.lecture_outils.'.$outil, ['elements' => $elements, 'aide' => $consigne])

    @if($obligatoire)
        <p x-show="!termine" class="mt-4 text-xs text-gray-500">Terminez cette activité pour continuer la leçon.</p>
    @endif
</div>
