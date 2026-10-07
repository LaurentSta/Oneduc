@extends('formateur.dashboard')

@section('formateur')
<div class="w-full px-6 lg:px-8"
     x-data="{
        selectedTool: null,
        filtres: { categories: [], modalites: [], temporalite: [], contexte: [] },
        outilVisible(groupes) {
            return Object.entries(this.filtres).every(([cle, actifs]) =>
                !actifs.length || (groupes[cle] || []).some(v => actifs.includes(v))
            );
        },
        reinitialiserFiltres() {
            this.filtres = { categories: [], modalites: [], temporalite: [], contexte: [] };
        },
        get visibleCount() {
            return [...document.querySelectorAll('[data-outil-filtres]')]
                .filter(el => this.outilVisible(JSON.parse(el.dataset.outilFiltres)))
                .length;
        },
     }"
     @keydown.escape.window="selectedTool = null">

  <x-formateur.page-header
    :breadcrumb="[['label' => 'Accueil', 'url' => route('formateur.dashboard')], ['label' => 'Outils numériques']]"
    title="Outils numériques"
    subtitle="Animez vos sessions en présentiel ou à distance."
    :image="asset('images/svg/header/Outils.svg')"
    imageAlt="Outils numériques"
  >
    <x-slot:badges>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-bleuone/15 bg-bleuone/5 px-3 py-1 text-bleuone">
        {{ $groups->count() }} groupes
      </span>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-orangeone/20 bg-orangeone/10 px-3 py-1 text-orangeone">
        {{ $groups->sum('students_count') }} stagiaires
      </span>
    </x-slot:badges>
  </x-formateur.page-header>

  {{-- Filtres + grille des outils --}}
  <div class="flex flex-col gap-6 lg:flex-row lg:items-start mb-8">

    {{-- Panneau de filtres --}}
    <aside class="w-full lg:w-64 lg:shrink-0" x-data="{ panelOuvert: false }">
      <div class="rounded-2xl border border-gray-100 bg-white shadow-md lg:sticky lg:top-8">

        <button type="button"
                class="flex w-full items-center justify-between px-5 py-4 text-left lg:hidden"
                @click="panelOuvert = !panelOuvert">
          <span class="text-sm font-bold text-gray-800">Filtres</span>
          <svg class="h-4 w-4 text-gray-400 transition-transform" :class="panelOuvert ? 'rotate-180' : ''"
               xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>

        <div class="space-y-5 px-5 pb-5 lg:block lg:pt-5" :class="panelOuvert ? 'block' : 'hidden'">

          <div class="flex items-center justify-between">
            <span class="hidden text-sm font-bold text-gray-800 lg:inline">Filtres</span>
            <button type="button" @click="reinitialiserFiltres()" class="text-xs font-semibold text-bleuone hover:underline">
              Réinitialiser
            </button>
          </div>

          <p class="text-xs text-gray-400" x-text="visibleCount + ' outil(s) affiché(s)'"></p>

          {{-- Groupe : Catégorie --}}
          <fieldset>
            <legend class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400">Catégorie</legend>
            <div class="space-y-1.5">
              @foreach(['interaction' => 'Interaction & Feedback', 'collaboration' => 'Collaboration', 'animation' => 'Animation de session', 'creation' => 'Création de contenu'] as $valeur => $libelle)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                  <input type="checkbox" value="{{ $valeur }}" x-model="filtres.categories"
                         class="rounded border-gray-300 text-bleuone focus:ring-bleuone">
                  {{ $libelle }}
                </label>
              @endforeach
            </div>
          </fieldset>

          {{-- Groupe : Modalité --}}
          <fieldset>
            <legend class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400">Modalité</legend>
            <div class="space-y-1.5">
              @foreach(['presentiel' => 'Présentiel', 'distanciel' => 'Distanciel'] as $valeur => $libelle)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                  <input type="checkbox" value="{{ $valeur }}" x-model="filtres.modalites"
                         class="rounded border-gray-300 text-bleuone focus:ring-bleuone">
                  {{ $libelle }}
                </label>
              @endforeach
            </div>
          </fieldset>

          {{-- Groupe : Temporalité --}}
          <fieldset>
            <legend class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400">Temporalité</legend>
            <div class="space-y-1.5">
              @foreach(['synchrone' => 'Synchrone', 'asynchrone' => 'Asynchrone'] as $valeur => $libelle)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                  <input type="checkbox" value="{{ $valeur }}" x-model="filtres.temporalite"
                         class="rounded border-gray-300 text-bleuone focus:ring-bleuone">
                  {{ $libelle }}
                </label>
              @endforeach
            </div>
          </fieldset>

          {{-- Groupe : Contexte d'intégration --}}
          <fieldset>
            <legend class="mb-2 text-xs font-bold uppercase tracking-wider text-gray-400">Contexte d'intégration</legend>
            <div class="space-y-1.5">
              @foreach(['lecon' => 'Dans une leçon', 'parcours' => 'Dans un parcours', 'libre' => 'Usage libre'] as $valeur => $libelle)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                  <input type="checkbox" value="{{ $valeur }}" x-model="filtres.contexte"
                         class="rounded border-gray-300 text-bleuone focus:ring-bleuone">
                  {{ $libelle }}
                </label>
              @endforeach
            </div>
          </fieldset>

        </div>
      </div>
    </aside>

    {{-- Grille des outils : tuiles compactes, détail au clic --}}
    <div class="min-w-0 flex-1">
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6">

    {{-- ── POWERPOINT VERS MODULE ────────────────────────────────────── --}}
    {{-- Tuile masquée temporairement : affichage incorrect signalé, correctif pas encore déployé. --}}
    {{--
    <div x-show="filtre === 'all' || filtre === 'creation'" class="flex flex-col bg-white rounded-[20px] shadow-md overflow-hidden">
      <div class="bg-violet-600 px-6 py-5 flex items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/20">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3h8a2 2 0 012 2v14a2 2 0 01-2 2H8a2 2 0 01-2-2V5a2 2 0 012-2zm1 4h6m-6 4h6m-6 4h4"/>
          </svg>
        </div>
        <h2 class="text-lg font-bold text-white">PowerPoint vers module</h2>
      </div>
      <div class="flex-1 px-6 py-5 space-y-3">
        <p class="text-sm text-gray-600 leading-relaxed">
          Importez un fichier PowerPoint ou PDF. Onéduc crée automatiquement un module et transforme chaque diapositive en support navigable, façon SlideShare.
        </p>
        <div class="flex flex-wrap gap-2 text-[11px]">
          <span class="rounded-full bg-violet-100 px-2.5 py-0.5 font-semibold text-violet-700">Création</span>
          <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
          <span class="rounded-full bg-amber-100 px-2.5 py-0.5 font-semibold text-amber-700">Asynchrone</span>
        </div>
      </div>

      @if($recentPowerPointModules->isNotEmpty())
        <div class="border-t border-gray-100 px-6 py-4">
          <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">Modules récents</p>
          <div class="space-y-2">
            @foreach($recentPowerPointModules as $powerPointModule)
              @php
                $powerPointLecture = $powerPointModule->lectures->first();
                $powerPointReady = ($powerPointLecture?->slides_status ?? null) === 'ready';
              @endphp
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">{{ $powerPointModule->module_title }}</p>
                  <p class="text-[10px] {{ $powerPointReady ? 'text-green-600' : 'text-gray-400' }}">
                    {{ $powerPointReady ? ($powerPointLecture->slide_count . ' diapositives') : 'Conversion ' . ($powerPointLecture?->slides_status ?? 'en attente') }}
                  </p>
                </div>
                <a href="{{ route('formateur.outils.powerpoint.show', $powerPointModule) }}"
                   class="shrink-0 rounded-[6px] bg-violet-100 px-2 py-1 text-[10px] font-bold text-violet-700 hover:bg-violet-200 transition">
                  Ouvrir
                </a>
              </div>
            @endforeach
          </div>
        </div>
      @endif

      <div class="border-t border-gray-100 px-6 py-4 mt-auto">
        <a href="{{ route('formateur.outils.powerpoint.index') }}"
           class="w-full inline-flex items-center justify-center gap-2 rounded-[10px] bg-violet-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-violet-700 transition">
          Créer depuis PowerPoint
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </a>
      </div>
    </div>
    --}}

    {{-- ── NUAGE DE MOTS ──────────────────────────────────────────────── --}}
    <x-oneduc.outil-tile
      tool-id="nuage-de-mots"
      :categories="['interaction']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone', 'asynchrone']"
      :contexte="['parcours', 'libre']"
      title="Nuage de mots"
      icon-bg="bg-amber-500"
      :badge-count="$recentWordclouds->count()"
      cta-route="{{ route('formateur.nuages.index') }}"
      cta-label="Gérer les nuages de mots"
      cta-bg="bg-amber-500 hover:bg-amber-600"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Posez une question ouverte. Les stagiaires soumettent leurs mots et vous voyez le nuage se construire en direct. Fonctionne en autonomie (parcours) ou en session live avec un code.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-amber-100 px-2.5 py-0.5 font-semibold text-amber-700">Asynchrone</span>
      </x-slot:badges>
      @if($recentWordclouds->isNotEmpty())
        <x-slot:body>
          <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Sessions récentes</p>
          <div class="space-y-2">
            @foreach($recentWordclouds as $wc)
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">{{ $wc->title }}</p>
                  <p class="text-[10px] text-gray-400">{{ $wc->group?->name }}</p>
                </div>
                <a href="{{ route('formateur.nuages.live', $wc) }}"
                   class="shrink-0 rounded-[6px] bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-700 hover:bg-amber-200 transition">
                  Voir
                </a>
              </div>
            @endforeach
          </div>
        </x-slot:body>
      @endif
    </x-oneduc.outil-tile>

    {{-- ── QUIZ EN DIRECT ─────────────────────────────────────────────── --}}
    <x-oneduc.outil-tile
      tool-id="quiz"
      :categories="['interaction']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Quiz en direct"
      icon-bg="bg-[#004461]"
      cta-route="{{ route('formateur.outils.quiz.index') }}"
      cta-label="Lancer un quiz en direct"
      cta-bg="bg-[#004461] hover:bg-[#005577]"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Animez une session synchronisée avec votre groupe à partir des questions déjà préparées dans une formation. Les résultats restent distincts du quiz de validation réalisé en autonomie.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-semibold text-gray-500">Synchrone</span>
      </x-slot:badges>
    </x-oneduc.outil-tile>

    {{-- ── BANQUE DE QUESTIONS DE QUIZ ──────────────────────────────────── --}}
    <x-oneduc.outil-tile
      tool-id="quiz-questions"
      :categories="['interaction']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['asynchrone']"
      :contexte="['lecon']"
      title="Banque de questions"
      icon-bg="bg-orangeone"
      cta-route="{{ route('formateur.outils.quiz-questions.index') }}"
      cta-label="Gérer les questions de quiz"
      cta-bg="bg-orangeone hover:bg-orangeone-hover"
      :badge-count="$recentModules->count() ?: null"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Créez, modifiez et importez (CSV) les questions du quiz de fin de leçon de vos formations. Ce quiz est celui proposé au stagiaire directement après la leçon.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-semibold text-gray-500">Asynchrone</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Autonomie stagiaire</span>
      </x-slot:badges>
    </x-oneduc.outil-tile>

    {{-- ── TABLEAU BLANC ──────────────────────────────────────────────── --}}
    <x-oneduc.outil-tile
      tool-id="tableau-blanc"
      :categories="['collaboration']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Tableau blanc"
      icon-bg="bg-[#E94D2A]"
      :badge-count="$groups->count()"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Un espace collaboratif partagé avec votre groupe. Dessinez, annotez, organisez des idées ensemble en temps réel. Idéal pour le brainstorming et la co-construction.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-semibold text-gray-500">Synchrone</span>
      </x-slot:badges>
      <x-slot:body>
        @if($groups->isNotEmpty())
          <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Mes groupes</p>
          <div class="space-y-2">
            @foreach($groups->take(5) as $group)
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">{{ $group->name }}</p>
                  <p class="text-[10px] text-gray-400">{{ $group->students_count }} stagiaire{{ $group->students_count > 1 ? 's' : '' }}</p>
                </div>
                <a href="{{ route('formateur.groupes.whiteboard.show', $group) }}"
                   class="shrink-0 rounded-[6px] bg-red-100 px-2 py-1 text-[10px] font-bold text-[#E94D2A] hover:bg-red-200 transition">
                  Ouvrir
                </a>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-xs text-gray-500">
            Créez un <a href="{{ route('formateur.groupes.create') }}" class="text-[#E94D2A] hover:underline font-semibold">groupe</a> pour accéder à son tableau blanc.
          </p>
        @endif
      </x-slot:body>
    </x-oneduc.outil-tile>

    {{-- ── MUR DE QUESTIONS ANONYME ─────────────────────────────────── --}}
    <x-oneduc.outil-tile
      tool-id="mur-questions"
      :categories="['interaction']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Mur de questions"
      icon-bg="bg-indigo-600"
      :badge-count="$recentQuestionWalls->count()"
      cta-route="{{ route('formateur.questions.index') }}"
      cta-label="Gérer les murs de questions"
      cta-bg="bg-indigo-600 hover:bg-indigo-700"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h8m-8 4h5m-9 5h16a2 2 0 002-2V7a2 2 0 00-2-2h-3.586a1 1 0 01-.707-.293l-1.414-1.414A1 1 0 0013.586 3h-3.172a1 1 0 00-.707.293L8.293 4.707A1 1 0 017.586 5H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Les stagiaires posent leurs questions avec ou sans anonymat. Le groupe vote pour les blocages prioritaires et vous qualifiez chaque question en direct.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 font-semibold text-indigo-700">Priorisation</span>
      </x-slot:badges>
      @if($recentQuestionWalls->isNotEmpty())
        <x-slot:body>
          <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Murs récents</p>
          <div class="space-y-2">
            @foreach($recentQuestionWalls as $wall)
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">{{ $wall->title }}</p>
                  <p class="text-[10px] text-gray-400">
                    {{ $wall->questions_count }} question{{ $wall->questions_count > 1 ? 's' : '' }} · {{ $wall->group?->name }}
                  </p>
                </div>
                <a href="{{ route('formateur.questions.show', $wall) }}"
                   class="shrink-0 rounded-[6px] bg-indigo-100 px-2 py-1 text-[10px] font-bold text-indigo-700 hover:bg-indigo-200 transition">
                  Ouvrir
                </a>
              </div>
            @endforeach
          </div>
        </x-slot:body>
      @endif
    </x-oneduc.outil-tile>

    {{-- ── SONDAGE ─────────────────────────────────────────────────────── --}}
    <x-oneduc.outil-tile
      tool-id="sondage"
      :categories="['interaction']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Sondage"
      icon-bg="bg-teal-600"
      :badge-count="$recentPolls->count()"
      cta-route="{{ route('formateur.sondages.index') }}"
      cta-label="Gérer les sondages"
      cta-bg="bg-teal-600 hover:bg-teal-700"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Créez des questions à choix multiples dans vos parcours formateur. Les stagiaires répondent pendant l'activité et vous animez la session autour de leurs réponses.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-teal-100 px-2.5 py-0.5 font-semibold text-teal-700">Interaction</span>
      </x-slot:badges>
      @if($recentPolls->isNotEmpty())
        <x-slot:body>
          <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Sondages récents</p>
          <div class="space-y-2">
            @foreach($recentPolls as $poll)
              @php $firstQuestion = collect($poll->questions ?? [])->first(); @endphp
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">
                    {{ $firstQuestion['question'] ?? 'Sondage sans question' }}
                  </p>
                  <p class="text-[10px] text-gray-400 truncate">
                    {{ $poll->group?->name }} · {{ $poll->responses_count }} réponse{{ $poll->responses_count > 1 ? 's' : '' }}
                  </p>
                </div>
                <a href="{{ route('formateur.sondages.show', $poll) }}"
                   class="shrink-0 rounded-[6px] bg-teal-100 px-2 py-1 text-[10px] font-bold text-teal-700 hover:bg-teal-200 transition">
                  Ouvrir
                </a>
              </div>
            @endforeach
          </div>
        </x-slot:body>
      @endif
    </x-oneduc.outil-tile>

    {{-- ── VRAI OU FAUX ───────────────────────────────────────────────── --}}
    @if(config('outils.vraifaux.enabled'))
    <x-oneduc.outil-tile
      tool-id="vrai-faux"
      :categories="['interaction']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Vrai ou Faux"
      icon-bg="bg-orangeone"
      :badge-count="$recentTrueFalseSessions->count()"
      cta-route="{{ route('formateur.vraifaux.index') }}"
      cta-label="Gérer les Vrai/Faux"
      cta-bg="bg-orangeone hover:bg-orangeone-hover"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Affichez des affirmations courtes, les stagiaires répondent Vrai ou Faux, puis vous commentez les résultats et les explications en direct.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-orange-100 px-2.5 py-0.5 font-semibold text-orange-700">Vérification</span>
      </x-slot:badges>
      @if($recentTrueFalseSessions->isNotEmpty())
        <x-slot:body>
          <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Vrai/Faux récents</p>
          <div class="space-y-2">
            @foreach($recentTrueFalseSessions as $session)
              @php $firstStatement = collect($session->questions ?? [])->first(); @endphp
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">
                    {{ $firstStatement['statement'] ?? $session->title }}
                  </p>
                  <p class="text-[10px] text-gray-400 truncate">
                    {{ $session->group?->name }} · {{ $session->responses_count }} réponse{{ $session->responses_count > 1 ? 's' : '' }}
                  </p>
                </div>
                <a href="{{ route('formateur.vraifaux.show', $session) }}"
                   class="shrink-0 rounded-[6px] bg-orange-100 px-2 py-1 text-[10px] font-bold text-orange-700 hover:bg-orange-200 transition">
                  Ouvrir
                </a>
              </div>
            @endforeach
          </div>
        </x-slot:body>
      @endif
    </x-oneduc.outil-tile>
    @endif

    {{-- ── BUZZER QUIZ ───────────────────────────────────────────────── --}}
    @if(config('outils.buzzer.enabled'))
    <x-oneduc.outil-tile
      tool-id="buzzer"
      :categories="['interaction']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Buzzer Quiz"
      icon-bg="bg-red-600"
      :badge-count="$recentBuzzerSessions->count()"
      cta-route="{{ route('formateur.buzzer.index') }}"
      cta-label="Gérer les buzzers"
      cta-bg="bg-red-600 hover:bg-red-700"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Lancez un quiz rythmé : les stagiaires buzzent, le plus rapide répond, puis vous attribuez les points en direct.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-red-100 px-2.5 py-0.5 font-semibold text-red-700">Compétition</span>
      </x-slot:badges>
      @if($recentBuzzerSessions->isNotEmpty())
        <x-slot:body>
          <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Buzzers récents</p>
          <div class="space-y-2">
            @foreach($recentBuzzerSessions as $session)
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">{{ $session->title }}</p>
                  <p class="text-[10px] text-gray-400 truncate">
                    {{ $session->group?->name }} · {{ $session->participants_count }} joueur{{ $session->participants_count > 1 ? 's' : '' }}
                  </p>
                </div>
                <a href="{{ route('formateur.buzzer.show', $session) }}"
                   class="shrink-0 rounded-[6px] bg-red-100 px-2 py-1 text-[10px] font-bold text-red-700 hover:bg-red-200 transition">
                  Ouvrir
                </a>
              </div>
            @endforeach
          </div>
        </x-slot:body>
      @endif
    </x-oneduc.outil-tile>
    @endif

    {{-- ── ÉCHELLE DE POSITIONNEMENT ──────────────────────────────────── --}}
    @if(config('outils.echelle.enabled'))
    <x-oneduc.outil-tile
      tool-id="echelle"
      :categories="['interaction']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Échelle de positionnement"
      icon-bg="bg-indigo-600"
      :badge-count="$recentScales->count()"
      cta-route="{{ route('formateur.echelle.index') }}"
      cta-label="Gérer les échelles"
      cta-bg="bg-indigo-600 hover:bg-indigo-700"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 6h18M3 12h18M3 18h18"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Un curseur de 1 à 10 pour mesurer instantanément la perception ou le ressenti de chaque stagiaire. Visualisez la moyenne et la distribution en temps réel.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-semibold text-gray-500">Synchrone</span>
      </x-slot:badges>
      @if($recentScales->isNotEmpty())
        <x-slot:body>
          <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Échelles récentes</p>
          <div class="space-y-2">
            @foreach($recentScales as $scale)
              @php $firstQ = collect($scale->questions ?? [])->first(); @endphp
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">{{ $firstQ['question'] ?? 'Échelle sans question' }}</p>
                  <p class="text-[10px] text-gray-400 truncate">
                    {{ $scale->group?->name }} · {{ $scale->responses_count }} réponse{{ $scale->responses_count > 1 ? 's' : '' }}
                  </p>
                </div>
                <a href="{{ route('formateur.echelle.show', $scale) }}"
                   class="shrink-0 rounded-[6px] bg-indigo-100 px-2 py-1 text-[10px] font-bold text-indigo-700 hover:bg-indigo-200 transition">
                  Ouvrir
                </a>
              </div>
            @endforeach
          </div>
        </x-slot:body>
      @endif
    </x-oneduc.outil-tile>
    @endif

    {{-- ── ZONE DE CLIC ───────────────────────────────────────── --}}
    @if(config('outils.composants.enabled'))
    <x-oneduc.outil-tile
      tool-id="composants"
      :categories="['interaction']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Zone de clic"
      icon-bg="bg-orangeone"
      :badge-count="$recentComponentFinderSessions->count()"
      cta-route="{{ route('formateur.composants.index') }}"
      cta-label="Gérer les composants"
      cta-bg="bg-orangeone hover:bg-orangeone-hover"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Chargez une image, définissez les zones à retrouver, puis demandez aux stagiaires de cliquer sur les bons composants.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-orange-100 px-2.5 py-0.5 font-semibold text-orange-700">Repérage</span>
      </x-slot:badges>
      @if($recentComponentFinderSessions->isNotEmpty())
        <x-slot:body>
          <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Jeux récents</p>
          <div class="space-y-2">
            @foreach($recentComponentFinderSessions as $session)
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">{{ $session->title }}</p>
                  <p class="text-[10px] text-gray-400 truncate">
                    {{ $session->group?->name }} · {{ $session->attempts_count }} participation{{ $session->attempts_count > 1 ? 's' : '' }}
                  </p>
                </div>
                <a href="{{ route('formateur.composants.show', $session) }}"
                   class="shrink-0 rounded-[6px] bg-orange-100 px-2 py-1 text-[10px] font-bold text-orange-700 hover:bg-orange-200 transition">
                  Ouvrir
                </a>
              </div>
            @endforeach
          </div>
        </x-slot:body>
      @endif
    </x-oneduc.outil-tile>
    @endif

    {{-- ── ROUE ALÉATOIRE ─────────────────────────────────────────────── --}}
    <x-oneduc.outil-tile
      tool-id="roue"
      :categories="['animation']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Roue aléatoire"
      icon-bg="bg-violet-600"
      cta-route="{{ route('formateur.roue.index') }}"
      cta-label="Gérer les roues"
      cta-bg="bg-violet-600 hover:bg-violet-700"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Désignez un stagiaire au hasard d'un simple clic. La roue tourne en direct sur l'écran de chaque participant. Idéal pour les interrogations orales, les rapporteurs ou les brise-glace.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-semibold text-gray-500">Synchrone</span>
      </x-slot:badges>
    </x-oneduc.outil-tile>

    {{-- ── MINUTEUR COLLABORATIF ──────────────────────────────────────── --}}
    @if(config('outils.minuteur.enabled'))
    <x-oneduc.outil-tile
      tool-id="minuteur"
      :categories="['animation']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Minuteur"
      icon-bg="bg-rose-600"
      :badge-count="$groups->count()"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Lancez un compte à rebours visible en temps réel par tous les stagiaires du groupe. Présets Pomodoro, pause, exercice — avec contrôle total pour le formateur.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-rose-100 px-2.5 py-0.5 font-semibold text-rose-700">Synchrone</span>
      </x-slot:badges>
      <x-slot:body>
        @if($groups->isNotEmpty())
          <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Mes groupes</p>
          <div class="space-y-2">
            @foreach($groups->take(4) as $group)
              <div class="flex items-center justify-between gap-2">
                <p class="text-xs font-semibold text-gray-800 truncate">{{ $group->name }}</p>
                <a href="{{ route('formateur.groupes.timer.show', $group) }}"
                   class="shrink-0 rounded-[6px] bg-rose-100 px-2 py-1 text-[10px] font-bold text-rose-700 hover:bg-rose-200 transition">
                  Ouvrir
                </a>
              </div>
            @endforeach
          </div>
        @else
          <p class="text-xs text-gray-500">
            Créez un <a href="{{ route('formateur.groupes.create') }}" class="text-rose-600 hover:underline font-semibold">groupe</a> pour accéder au minuteur.
          </p>
        @endif
      </x-slot:body>
    </x-oneduc.outil-tile>
    @endif

    {{-- ── ÉMARGEMENT (FEUILLE DE PRÉSENCE) ─────────────────────────────── --}}
    <x-oneduc.outil-tile
      tool-id="emargement"
      :categories="[]"
      :modalites="['presentiel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Émargement"
      icon-bg="bg-slate-600"
      :badge-count="$openSeancesCount"
      cta-route="{{ route('formateur.emargement.index') }}"
      cta-label="Gérer l'émargement"
      cta-bg="bg-slate-600 hover:bg-slate-700"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6.586-6.586a2 2 0 112.828 2.828L11.828 13.828a4 4 0 01-1.414.94l-3.536 1.178a.5.5 0 01-.632-.632l1.178-3.536a4 4 0 01.94-1.414z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 21h14"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Feuille de présence par séance datée, avec signature graphique de chaque stagiaire. Document administratif conforme aux exigences d'audit Qualiopi/OPCO, exportable en PDF.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 font-semibold text-gray-500">Administratif</span>
      </x-slot:badges>
    </x-oneduc.outil-tile>

    {{-- ── MES MODULES (MODULE BUILDER) ───────────────────────────────── --}}
    <x-oneduc.outil-tile
      tool-id="modules"
      :categories="['creation']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['asynchrone']"
      :contexte="['parcours']"
      title="Mes créations"
      icon-bg="bg-emerald-600"
      :badge-count="$recentModules->count()"
      cta-route="{{ route('formateur.formations.index', ['tab' => 'creations']) }}"
      cta-label="Gérer mes créations"
      cta-bg="bg-emerald-600 hover:bg-emerald-700"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s4.832.477 6 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Créez vos propres formations (chapitres et leçons en texte riche) et assignez-les à vos groupes, sans passer par le catalogue admin.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-emerald-100 px-2.5 py-0.5 font-semibold text-emerald-700">Asynchrone</span>
      </x-slot:badges>
      @if($recentModules->isNotEmpty())
        <x-slot:body>
          <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-gray-400">Formations récentes</p>
          <div class="space-y-2">
            @foreach($recentModules as $module)
              <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                  <p class="text-xs font-semibold text-gray-800 truncate">{{ $module->module_title }}</p>
                  <p class="text-[10px] text-gray-400">
                    {{ $module->sections_count }} chapitre(s) · {{ $module->groups_count }} groupe(s)
                  </p>
                </div>
                <a href="{{ route('formateur.modules.builder.edit', $module) }}"
                   class="shrink-0 rounded-[6px] bg-emerald-100 px-2 py-1 text-[10px] font-bold text-emerald-700 hover:bg-emerald-200 transition">
                  Ouvrir
                </a>
              </div>
            @endforeach
          </div>
        </x-slot:body>
      @endif
    </x-oneduc.outil-tile>

    {{-- ── PAGE COLLABORATIVE (HEDGEDOC) ─────────────────────────────── --}}
    <x-oneduc.outil-tile
      tool-id="page-collaborative"
      :categories="['collaboration']"
      :modalites="['presentiel', 'distanciel']"
      :temporalite="['synchrone']"
      :contexte="['libre']"
      title="Page collaborative"
      icon-bg="bg-cyan-600"
      cta-route="{{ route('formateur.pages-collaboratives.index') }}"
      cta-label="Ouvrir l'outil"
      cta-bg="bg-cyan-600 hover:bg-cyan-700"
    >
      <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </x-slot:icon>
      <x-slot:description>
        Éditez un document Markdown à plusieurs en temps réel (texte, tableaux, images, listes, etc.), avec partage d'un lien d'édition et d'un lien lecture seule.
      </x-slot:description>
      <x-slot:badges>
        <span class="rounded-full bg-green-100 px-2.5 py-0.5 font-semibold text-green-700">Présentiel</span>
        <span class="rounded-full bg-blue-100 px-2.5 py-0.5 font-semibold text-blue-700">Distanciel</span>
        <span class="rounded-full bg-cyan-100 px-2.5 py-0.5 font-semibold text-cyan-700">Markdown</span>
      </x-slot:badges>
    </x-oneduc.outil-tile>

      {{-- Point d'extension pour les outils autonomes enregistrés par leur provider. --}}
      @foreach($outilsAutonomes ?? [] as $outilAutonome)
        @include($outilAutonome['vue'], $outilAutonome['donnees'] ?? [])
      @endforeach

      </div>
    </div>

  </div>

</div>
@endsection
