@php
  $constructeurAdmin = (bool) ($constructeurAdmin ?? false);
  $nomRoutesConstructeur = $constructeurAdmin ? 'admin.formations.constructeur' : 'formateur.modules.builder';
  $layoutConstructeur = $constructeurAdmin ? 'admin.admin_dashboard' : 'formateur.dashboard';
  $sectionConstructeur = $constructeurAdmin ? 'admin' : 'formateur';
  $apercu = true;
@endphp
@extends($layoutConstructeur)
@section($sectionConstructeur)
<div class="w-full px-4 py-5 lg:px-6">
  <header class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-[20px] bg-white p-5 shadow-md">
    <div>
      <p class="text-sm font-semibold text-orangeone">Aperçu apprenant</p>
      <h1 class="mt-2 font-raleway text-2xl font-bold text-bleuone">{{ $module->module_title }}</h1>
      <p class="mt-2 text-sm text-gray-600">Essayez le contenu et les activités. Aucune progression ni aucun résultat n’est enregistré.</p>
    </div>
    <a href="{{ route($nomRoutesConstructeur.'.lectures.edit', $lecture) }}" class="btn-oneduc-outline !px-4 !py-2 !text-sm">Retour à la conception</a>
  </header>
  <div class="grid items-start gap-5 lg:grid-cols-[250px_minmax(0,1fr)]">
    <aside class="rounded-[20px] bg-white p-4 shadow-md" aria-label="Plan de la formation">
      @foreach($module->sections as $chapitre)
        <h2 class="mb-2 mt-3 text-sm font-semibold text-bleuone">{{ $chapitre->section_title }}</h2>
        <ul class="space-y-1">
          @foreach($chapitre->lectures as $lecon)
            <li><a href="{{ route($nomRoutesConstructeur.'.preview', ['module' => $module, 'section' => $chapitre->id, 'lecture' => $lecon->id]) }}"
                   @if($lecon->id === $lecture->id) aria-current="page" @endif
                   class="block rounded-[10px] px-3 py-2 text-sm {{ $lecon->id === $lecture->id ? 'bg-bleuone font-semibold text-white' : 'text-gray-700 hover:bg-gray-100' }}">{{ $lecon->lecture_title }}</a></li>
          @endforeach
        </ul>
      @endforeach
    </aside>
    <main class="min-w-0 rounded-[20px] bg-white p-5 shadow-md sm:p-8">
      <p class="text-sm text-gray-500">{{ $section->section_title }}</p>
      <h2 class="mt-2 font-raleway text-2xl font-bold text-bleuone">{{ $lecture->lecture_title }}</h2>
      @if($lecture->objectives->isNotEmpty())
        <div class="my-5 rounded-[12px] bg-gray-50 p-4">
          <p class="font-semibold text-bleuone">À la fin de cette leçon, vous saurez :</p>
          <ul class="mt-2 list-inside list-disc text-sm">@foreach($lecture->objectives as $objectif)<li>{{ $objectif->title }}</li>@endforeach</ul>
        </div>
      @endif
      <div class="mt-6 font-lisible">
        @if($lecture->content_type === 'scorm')
          @if($lecture->scorm_index_path)
            <iframe src="{{ route($nomRoutesConstructeur.'.lectures.apercu-scorm', $lecture) }}"
                    title="Aperçu du contenu SCORM" class="h-[70vh] w-full rounded-[12px] border border-gray-200" allowfullscreen></iframe>
          @else
            <p class="text-sm text-gray-500">Importez un contenu SCORM pour le prévisualiser.</p>
          @endif
        @elseif($lecture->content_type === 'slides')
          @if(count($slides))
            <div x-data="{ slides: @js($slides), position: 0 }">
              <img :src="slides[position]" :alt="'Diapositive ' + (position + 1) + ' sur ' + slides.length" class="w-full rounded-[12px] border border-gray-200">
              <div class="mt-4 flex items-center justify-between gap-3">
                <button type="button" @click="position--" :disabled="position === 0" class="btn-oneduc-outline !px-4 !py-2 !text-sm disabled:opacity-40">Précédente</button>
                <span class="text-sm" aria-live="polite" x-text="(position + 1) + ' / ' + slides.length"></span>
                <button type="button" @click="position++" :disabled="position === slides.length - 1" class="btn-oneduc-outline !px-4 !py-2 !text-sm disabled:opacity-40">Suivante</button>
              </div>
            </div>
          @else
            <p class="text-sm text-gray-500">La présentation n’est pas encore prête. Attendez sa conversion avant de vérifier le rendu.</p>
          @endif
        @elseif($lecture->content_type === 'html')
          {!! app(\App\Domains\ModulesFormateur\Support\NettoyeurBlocsModule::class)->sanitizeHtmlFragment($lecture->html_content) !!}
        @else
          @include('shared.lecture_blocks', ['blocks' => $initialBlocks, 'lecture' => $lecture, 'interactif' => true])
        @endif
      </div>
      @if($lecture->quiz_enabled)
        <p class="mt-6 rounded-[12px] border border-gray-200 p-4 text-sm text-bleuone">Après la lecture : quiz de validation de {{ $lecture->quiz_questions_per_attempt }} question(s), avec le seuil de réussite actuel de 50 %.</p>
      @endif
      @if($ressources->isNotEmpty())
        <details class="mt-6 border-t border-gray-200 pt-4">
          <summary class="cursor-pointer font-semibold text-bleuone">Documents complémentaires</summary>
          <ul class="mt-3 space-y-2">@foreach($ressources as $ressource)<li><a href="{{ $ressource->public_url }}" target="_blank" rel="noopener" class="text-sm text-orangeone hover:underline">{{ $ressource->title }}</a></li>@endforeach</ul>
        </details>
      @endif
    </main>
  </div>
</div>
@endsection
