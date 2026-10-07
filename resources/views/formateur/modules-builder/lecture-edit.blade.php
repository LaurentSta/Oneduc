{{-- Atelier de conception partagé entre administrateurs et formateurs. --}}
@php
  $constructeurAdmin = (bool) ($constructeurAdmin ?? false);
  $layoutConstructeur = $constructeurAdmin ? 'admin.admin_dashboard' : 'formateur.dashboard';
  $sectionConstructeur = $constructeurAdmin ? 'admin' : 'formateur';
  $nomRoutesConstructeur = $constructeurAdmin ? 'admin.formations.constructeur' : 'formateur.modules.builder';
  $lectureSeule = $constructeurAdmin && in_array($module->publication_state, ['published', 'archived'], true);
  $urlApercuLecon = route($nomRoutesConstructeur.'.preview', ['module' => $module, 'section' => $section->id, 'lecture' => $lecture->id]);
  $supportImporte = in_array($lecture->content_type, ['scorm', 'slides'], true);
@endphp
@extends($layoutConstructeur)
@section($sectionConstructeur)
<div class="w-full px-4 py-5 lg:px-6">
  <header class="mb-5 flex flex-wrap items-center justify-between gap-4">
    <div class="min-w-0">
      <nav aria-label="Fil d’ariane" class="text-sm text-gray-500">
        <a href="{{ route($nomRoutesConstructeur.'.index') }}" class="font-semibold text-orangeone hover:underline">{{ $constructeurAdmin ? 'Catalogue Oneduc' : 'Mes créations' }}</a>
        <span class="mx-1">/</span>
        <a href="{{ route($nomRoutesConstructeur.'.edit', $module) }}" class="hover:underline">{{ $module->module_title }}</a>
      </nav>
      <h1 class="mt-2 font-raleway text-2xl font-bold text-bleuone">Concevoir une leçon</h1>
      <p class="mt-1 text-sm text-gray-600">Reliez vos objectifs, votre contenu et la vérification des acquis.</p>
    </div>
    <div class="flex flex-wrap gap-3">
      <a href="{{ route($nomRoutesConstructeur.'.edit', $module) }}" class="btn-oneduc-outline !px-4 !py-2 !text-sm">Plan de la formation</a>
      <a href="{!! $urlApercuLecon !!}" target="_blank" rel="noopener" class="btn-oneduc !px-4 !py-2 !text-sm">Aperçu apprenant</a>
    </div>
  </header>
  @foreach(['success' => 'green', 'error' => 'red'] as $type => $couleur)
    @if(session($type))
      <p role="status" class="mb-4 rounded-[12px] border px-4 py-3 text-sm {{ $type === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800' }}">{{ session($type) }}</p>
    @endif
  @endforeach
  @if($errors->any())
    <div role="alert" class="mb-4 rounded-[12px] border border-red-200 bg-red-50 p-4 text-sm text-red-800">
      <p class="font-semibold">Vérifiez les informations suivantes :</p>
      <ul class="mt-2 list-inside list-disc">@foreach($errors->all() as $erreur)<li>{{ $erreur }}</li>@endforeach</ul>
    </div>
  @endif
  @if($lectureSeule)
    <p role="status" class="mb-4 rounded-[12px] border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">Cette version est en lecture seule. Créez une nouvelle version depuis le plan pour la modifier.</p>
  @endif
  <div class="grid items-start gap-5 xl:grid-cols-[220px_minmax(0,1fr)]">
    <aside class="rounded-[20px] bg-white p-4 shadow-md xl:sticky xl:top-5" aria-label="Plan de la formation">
      <details open>
        <summary class="cursor-pointer font-varela font-bold text-bleuone">Plan de la formation</summary>
        <div class="mt-4 space-y-4">
          @foreach($module->sections as $chapitre)
            <section>
              <h2 class="px-2 text-xs font-semibold text-gray-500">{{ $chapitre->section_title }}</h2>
              <ul class="mt-2 space-y-1">
                @foreach($chapitre->lectures as $leconPlan)
                  <li>
                    <a href="{{ route($nomRoutesConstructeur.'.lectures.edit', $leconPlan) }}"
                       @if($leconPlan->id === $lecture->id) aria-current="page" @endif
                       class="block rounded-[10px] px-3 py-2 text-sm {{ $leconPlan->id === $lecture->id ? 'bg-bleuone font-semibold text-white' : 'text-gray-700 hover:bg-gray-100' }}">{{ $leconPlan->lecture_title }}</a>
                  </li>
                @endforeach
              </ul>
            </section>
          @endforeach
        </div>
      </details>
    </aside>
    <div class="grid min-w-0 items-start gap-5 2xl:grid-cols-[minmax(0,1fr)_320px]">
      <main class="min-w-0">
        <div class="rounded-[20px] bg-white p-5 shadow-md sm:p-7">
          <p class="mb-4 text-xs font-semibold text-gray-500">{{ $section->section_title }}</p>
          @if($supportImporte || $lectureSeule)
            @if($lectureSeule)
              <h2 class="mb-5 font-raleway text-xl font-bold text-bleuone">{{ $lecture->lecture_title }}</h2>
            @else
              <form method="POST" action="{{ route($nomRoutesConstructeur.'.lectures.update', $lecture) }}" class="mb-5 flex flex-wrap gap-2">
                @csrf @method('PUT')
                <label for="titre-lecon" class="sr-only">Titre de la leçon</label>
                <input id="titre-lecon" name="lecture_title" value="{{ old('lecture_title', $lecture->lecture_title) }}" required maxlength="255" class="min-w-0 flex-1 rounded-[10px] border-gray-300 text-sm font-semibold">
                <button type="submit" class="btn-oneduc-outline !px-3 !py-2 !text-sm">Enregistrer le titre</button>
              </form>
            @endif
            @if($supportImporte)
              @include('shared.formations-constructeur.support-lecon')
            @else
              @foreach($initialBlocks as $block)
                @include('shared.lecture_block_single', ['block' => $block, 'lecture' => $lecture, 'apercu' => true])
              @endforeach
            @endif
          @else
            <p class="mb-4 text-xs text-gray-500">Le contenu est enregistré automatiquement. Les réglages pédagogiques disposent de leur bouton d’enregistrement.</p>
            <div data-block-editor
                 data-lecture-id="{{ $lecture->id }}"
                 data-update-url="{{ route($nomRoutesConstructeur.'.lectures.update', $lecture) }}"
                 data-upload-url="{{ route($nomRoutesConstructeur.'.images.store', $module) }}"
                 data-video-upload-url="{{ route($nomRoutesConstructeur.'.videos.store', $module) }}"
                 data-audio-upload-url="{{ route($nomRoutesConstructeur.'.audios.store', $module) }}"
                 data-audio-generate-url="{{ route($nomRoutesConstructeur.'.lectures.generate-audio', $lecture) }}"
                 data-scorm-upload-url="{{ route($nomRoutesConstructeur.'.scorm.store', $module) }}"
                 data-initial-title="{{ $lecture->lecture_title }}"
                 data-initial-blocks="{{ json_encode($initialBlocks) }}"></div>
            @if(empty($initialBlocks))
              <details class="mt-6" data-import-support>
                <summary class="cursor-pointer text-sm font-semibold text-bleuone">Ou importer une présentation / un contenu SCORM</summary>
                <div class="mt-3">@include('shared.formations-constructeur.support-lecon')</div>
              </details>
            @endif
          @endif
        </div>
        @include('shared.formations-constructeur.ressources-lecon')
      </main>
      @include('shared.formations-constructeur.pedagogie-lecon')
    </div>
  </div>
</div>
@endsection
