@php
    $isDashboardActive = request()->routeIs('formateur.dashboard');

    $isGroupesActive = request()->routeIs('formateur.groupes.*');

    $isStagiairesActive = request()->routeIs('formateur.stagiaires.*');

    $isQuestionsFormationActive = request()->routeIs('formateur.outils.quiz-questions.*')
        || request()->routeIs('formateur.modules.builder.*');

    $isFormationsActive = request()->routeIs('formateur.formations.*')
        || request()->routeIs('formateur.objectifs.*')
        || request()->routeIs('formateur.quiz.*')
        || request()->routeIs('formateur.lesson.quiz.*')
        || request()->routeIs('formateur.mes-parcours.*')
        || $isQuestionsFormationActive;

    $isOutilsActive = (request()->routeIs('formateur.outils.*') && ! $isQuestionsFormationActive)
        || request()->routeIs('formateur.nuages.*')
        || request()->routeIs('formateur.sondages.*')
        || request()->routeIs('formateur.pages-collaboratives.*')
        || request()->routeIs('formateur.live-quiz.*')
        || request()->routeIs('formateur.groupes.whiteboard.*');

    $isProgressionActive = request()->routeIs('formateur.progressions.*')
        || request()->routeIs('formateur.progression.*');

    $isDocumentationActive = request()->routeIs('formateur.documentation');
    $hasParcoursRoute = \Illuminate\Support\Facades\Route::has('formateur.parcours.index');
    $isParcoursActive = $hasParcoursRoute && request()->routeIs('formateur.parcours.*');

    $navBaseClasses = 'flex flex-col items-center rounded-xl px-4 py-2 transition-all duration-200';
    $navIdleClasses = 'text-white/90 hover:bg-[#0A5B80] hover:text-white';
    $navActiveClasses = 'bg-[#0A5B80] text-white shadow-lg shadow-black/10';
@endphp

<aside
  id="formateur-sidebar"
  :class="{
    'translate-x-0': sidebarOpen,
    '-translate-x-full': !sidebarOpen,
    'lg:translate-x-0': !sidebarCollapsed,
    'lg:-translate-x-full': sidebarCollapsed,
  }"
  class="fixed left-0 top-0 z-40 h-screen w-48 transition-transform duration-300 flex flex-col bg-[#004461] text-white shadow-lg"
  aria-label="Navigation formateur">
    <div class="flex-1 overflow-y-auto">
        <a
            href="{{ route('formateur.dashboard') }}"
            class="flex min-h-20 flex-col items-center justify-center gap-1 bg-[#00374F] px-4 text-center transition hover:bg-[#0a4f70]"
            aria-current="{{ $isDashboardActive ? 'page' : 'false' }}"
        >
            <span class="text-[11px] font-bold uppercase tracking-[0.28em] text-white/70">Formateur</span>
            <span class="text-lg font-bold text-white">Tableau de bord</span>
        </a>

        <nav class="px-3 py-3 text-center" aria-label="Espaces du formateur">
            <div class="space-y-1.5">
                <a
                    href="{{ route('formateur.groupes.index') }}"
                    class="{{ $navBaseClasses }} {{ $isGroupesActive ? $navActiveClasses : $navIdleClasses }}"
                    aria-current="{{ $isGroupesActive ? 'page' : 'false' }}"
                >
                    <img src="{{ asset('images/svg/IconeMenu/MenuGroupe.svg') }}" alt="" aria-hidden="true" class="mb-0 h-20 w-20 object-contain">
                    <span class="text-[17px] font-medium">Groupes</span>
                </a>

                <a
                    href="{{ route('formateur.stagiaires.index') }}"
                    class="{{ $navBaseClasses }} {{ $isStagiairesActive ? $navActiveClasses : $navIdleClasses }}"
                    aria-current="{{ $isStagiairesActive ? 'page' : 'false' }}"
                >
                    <img src="{{ asset('images/svg/IconeMenu/MenuStagiaire.svg') }}" alt="" aria-hidden="true" width="46" height="46" class="mb-1">
                    <span class="text-[17px] font-medium">Stagiaires</span>
                </a>

                <a
                    href="{{ route('formateur.formations.index') }}"
                    class="{{ $navBaseClasses }} {{ $isFormationsActive ? $navActiveClasses : $navIdleClasses }}"
                    aria-current="{{ $isFormationsActive ? 'page' : 'false' }}"
                >
                    <img src="{{ asset('images/svg/IconeMenu/MenuFormation.svg') }}" alt="" aria-hidden="true" class="mb-0 h-20 w-20 object-contain">
                    <span class="text-[17px] font-medium">Formations</span>
                </a>

                <a
                    href="{{ route('formateur.outils.index') }}"
                    class="{{ $navBaseClasses }} {{ $isOutilsActive ? $navActiveClasses : $navIdleClasses }}"
                    aria-current="{{ $isOutilsActive ? 'page' : 'false' }}"
                >
                    <img src="{{ asset('images/svg/IconeMenu/MenuOutils.svg') }}" alt="" aria-hidden="true" class="mb-0 h-20 w-20 object-contain">
                    <span class="text-[17px] font-medium">Outils</span>
                </a>

                <a
                    href="{{ route('formateur.progressions.groupes') }}"
                    class="{{ $navBaseClasses }} {{ $isProgressionActive ? $navActiveClasses : $navIdleClasses }}"
                    aria-current="{{ $isProgressionActive ? 'page' : 'false' }}"
                >
                    <img src="{{ asset('images/svg/IconeMenu/MenuProgression.svg') }}" alt="" aria-hidden="true" class="mb-0 h-20 w-20 object-contain">
                    <span class="text-[17px] font-medium">Progression</span>
                </a>
            </div>
        </nav>
    </div>

    <div class="border-t border-white/20 px-3 py-2 text-center text-xs">
        <a href="{{ route('contact') }}" class="block rounded-lg px-4 py-1.5 transition hover:bg-white/10 hover:text-orange-100">Support</a>
        @if ($hasParcoursRoute)
            <a
                href="{{ route('formateur.parcours.index') }}"
                class="mt-0.5 block rounded-lg px-4 py-1.5 transition {{ $isParcoursActive ? 'bg-white/15 text-white' : 'hover:bg-white/10 hover:text-orange-100' }}"
                aria-current="{{ $isParcoursActive ? 'page' : 'false' }}"
            >
                Formation formateur
            </a>
        @endif
        <a
            href="{{ route('formateur.documentation') }}"
            class="mt-0.5 block rounded-lg px-4 py-1.5 transition {{ $isDocumentationActive ? 'bg-white/15 text-white' : 'hover:bg-white/10 hover:text-orange-100' }}"
            aria-current="{{ $isDocumentationActive ? 'page' : 'false' }}"
        >
            Documentation
        </a>
    </div>
</aside>
