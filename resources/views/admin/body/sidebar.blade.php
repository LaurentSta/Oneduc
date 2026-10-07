@php
    $groupesNavigation = [
        [
            'label' => 'Utilisateurs',
            'items' => [
                [
                    'label' => 'Tous les utilisateurs',
                    'href' => route('admin.utilisateurs.index'),
                    'icon' => 'ti-users',
                    'active' => request()->routeIs('admin.utilisateurs.index') && ! request()->query('role'),
                ],
                [
                    'label' => 'Formateurs',
                    'href' => route('admin.utilisateurs.index', ['role' => 'formateur']),
                    'icon' => 'ti-user-star',
                    'active' => request()->routeIs('admin.formateurs*')
                        || (request()->routeIs('admin.utilisateurs.index') && request()->query('role') === 'formateur'),
                ],
                [
                    'label' => 'Stagiaires',
                    'href' => route('admin.utilisateurs.index', ['role' => 'stagiaire']),
                    'icon' => 'ti-school',
                    'active' => request()->routeIs('admin.stagiaires.*')
                        || (request()->routeIs('admin.utilisateurs.index') && request()->query('role') === 'stagiaire'),
                ],
                [
                    'label' => 'Observateurs',
                    'href' => route('admin.observateurs.index'),
                    'icon' => 'ti-eye',
                    'active' => request()->routeIs('admin.observateurs.*'),
                ],
                [
                    'label' => 'Groupes',
                    'href' => route('admin.groupes'),
                    'icon' => 'ti-users-group',
                    'active' => request()->routeIs('admin.groupes*'),
                ],
            ],
        ],
        [
            'label' => 'Pédagogie',
            'items' => [
                [
                    'label' => 'Catégories',
                    'href' => route('admin.categories.all'),
                    'icon' => 'ti-category-2',
                    'active' => request()->routeIs('admin.categories.*') || request()->routeIs('admin.subcategories.*'),
                ],
                [
                    'label' => 'Modules',
                    'href' => route('admin.modules'),
                    'icon' => 'ti-books',
                    'active' => request()->routeIs('admin.modules*')
                        || request()->routeIs('admin.lectures.*')
                        || request()->routeIs('admin.sections.*')
                        || request()->routeIs('admin.quiz.*'),
                ],
                [
                    'label' => 'Constructeur',
                    'href' => route('admin.formations.constructeur.index'),
                    'icon' => 'ti-tool',
                    'active' => request()->routeIs('admin.formations.constructeur.*'),
                ],
                [
                    'label' => 'Évaluations',
                    'href' => route('admin.evaluations.index'),
                    'icon' => 'ti-clipboard-check',
                    'active' => request()->routeIs('admin.evaluations.*'),
                ],
                [
                    'label' => 'Référentiels',
                    'href' => route('admin.referentiels.index'),
                    'icon' => 'ti-hierarchy-2',
                    'active' => request()->routeIs('admin.referentiels.*'),
                ],
                [
                    'label' => 'Compétences',
                    'href' => route('admin.competencies.index'),
                    'icon' => 'ti-target-arrow',
                    'active' => request()->routeIs('admin.competencies.*'),
                ],
                [
                    'label' => 'Badges',
                    'href' => route('admin.badges.index'),
                    'icon' => 'ti-rosette-discount-check',
                    'active' => request()->routeIs('admin.badges.*'),
                ],
            ],
        ],
        [
            'label' => 'Pilotage',
            'items' => [
                [
                    'label' => 'Projets et tâches',
                    'href' => route('admin.pilotage.index'),
                    'icon' => 'ti-layout-kanban',
                    'active' => request()->routeIs('admin.pilotage.index')
                        || request()->routeIs('admin.pilotage.tasks.*')
                        || request()->routeIs('admin.pilotage.projects.*'),
                ],
                [
                    'label' => 'Qualité des parcours',
                    'href' => route('admin.pilotage.qualite-parcours-formateur'),
                    'icon' => 'ti-chart-dots-3',
                    'active' => request()->routeIs('admin.pilotage.qualite-parcours-formateur'),
                ],
                [
                    'label' => 'Consommation IA',
                    'href' => route('admin.pilotage.consommation-ia'),
                    'icon' => 'ti-sparkles',
                    'active' => request()->routeIs('admin.pilotage.consommation-ia'),
                ],
                [
                    'label' => 'Notifications',
                    'href' => route('admin.pilotage.notifications.index'),
                    'icon' => 'ti-bell',
                    'active' => request()->routeIs('admin.pilotage.notifications.*'),
                ],
                [
                    'label' => 'Journal d’activité',
                    'href' => route('admin.pilotage.journal'),
                    'icon' => 'ti-history',
                    'active' => request()->routeIs('admin.pilotage.journal'),
                ],
                [
                    'label' => 'Retours stagiaires',
                    'href' => route('admin.retours.index'),
                    'icon' => 'ti-message-report',
                    'active' => request()->routeIs('admin.retours.*'),
                ],
            ],
        ],
        [
            'label' => 'Outils',
            'items' => [
                [
                    'label' => 'Nuage de mots',
                    'href' => route('admin.nuage.index'),
                    'icon' => 'ti-abc',
                    'active' => request()->routeIs('admin.nuage.*'),
                ],
                [
                    'label' => 'État des outils',
                    'href' => route('admin.outils.index'),
                    'icon' => 'ti-toggle-right',
                    'active' => request()->routeIs('admin.outils.*'),
                ],
            ],
        ],
    ];
@endphp

<div
    x-cloak
    x-show="estMobile() && navigationMobileOuverte"
    x-transition.opacity.duration.150ms
    x-on:click="fermerNavigationMobile()"
    class="fixed inset-x-0 bottom-0 top-14 z-30 bg-slate-950/45 backdrop-blur-[1px] lg:hidden"
    aria-hidden="true"
></div>

<aside
    id="navigation-administrateur"
    x-cloak
    x-show="!estMobile() || navigationMobileOuverte"
    x-transition:enter="transition-transform duration-200 ease-out"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition-transform duration-150 ease-in"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    x-on:keydown.tab="gererTabulationNavigation($event)"
    :class="{ 'admin-sidebar--collapsed': navigationReduite }"
    :role="estMobile() ? 'dialog' : null"
    :aria-modal="estMobile() ? 'true' : null"
    class="admin-sidebar fixed bottom-0 left-0 top-14 z-40 flex flex-col border-r border-white/10 bg-bleuone text-white"
    aria-label="Navigation administrateur"
>
    <div class="flex h-12 shrink-0 items-center gap-2 border-b border-white/10 px-3">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-white/10 text-orangeone" aria-hidden="true">
            <i class="ti ti-shield-check text-lg"></i>
        </span>
        <div class="admin-sidebar-context min-w-0 flex-1">
            <p class="truncate text-xs font-semibold text-white">Console administrateur</p>
            <p class="truncate text-[11px] text-slate-300">Gestion de la plateforme</p>
        </div>
        <button
            type="button"
            x-on:click="fermerNavigationMobile()"
            class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-300 transition hover:bg-white/10 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orangeone lg:hidden"
            aria-label="Fermer la navigation"
        >
            <i class="ti ti-x text-lg" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto overscroll-contain px-2 py-3" aria-label="Menu principal">
        <a
            href="{{ route('admin.dashboard') }}"
            x-on:click="fermerNavigationMobile(false)"
            title="Vue d’ensemble"
            class="admin-nav-link group flex min-h-10 items-center gap-3 rounded-md px-3 py-2 text-[13px] font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orangeone {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-slate-200 hover:bg-white/5 hover:text-white' }}"
            @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
        >
            <i class="ti ti-layout-dashboard shrink-0 text-lg {{ request()->routeIs('admin.dashboard') ? 'text-orangeone' : 'text-slate-300 group-hover:text-white' }}" aria-hidden="true"></i>
            <span class="admin-nav-label truncate">Vue d’ensemble</span>
        </a>

        <div class="mt-3 space-y-4">
            @foreach ($groupesNavigation as $groupe)
                <section class="admin-nav-group border-t border-white/10 px-1 pt-3 first:border-t-0 first:pt-0" aria-labelledby="admin-nav-{{ str($groupe['label'])->slug() }}">
                    <h2
                        id="admin-nav-{{ str($groupe['label'])->slug() }}"
                        class="admin-nav-section-label mb-1.5 px-2 text-[10px] font-bold uppercase tracking-[0.13em] text-slate-400"
                    >
                        {{ $groupe['label'] }}
                    </h2>

                    <div class="space-y-0.5">
                        @foreach ($groupe['items'] as $element)
                            <a
                                href="{{ $element['href'] }}"
                                x-on:click="fermerNavigationMobile(false)"
                                title="{{ $element['label'] }}"
                                class="admin-nav-link group flex min-h-9 items-center gap-3 rounded-md px-3 py-1.5 text-[13px] font-medium transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orangeone {{ $element['active'] ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}"
                                @if ($element['active']) aria-current="page" @endif
                            >
                                <i class="ti {{ $element['icon'] }} shrink-0 text-[17px] {{ $element['active'] ? 'text-orangeone' : 'text-slate-400 group-hover:text-white' }}" aria-hidden="true"></i>
                                <span class="admin-nav-label truncate">{{ $element['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </nav>

    <div class="shrink-0 border-t border-white/10 p-2">
        <a
            href="{{ route('admin.profile') }}"
            x-on:click="fermerNavigationMobile(false)"
            title="Mon compte"
            class="admin-nav-link group flex min-h-9 items-center gap-3 rounded-md px-3 py-1.5 text-[13px] font-medium text-slate-300 transition hover:bg-white/5 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orangeone"
        >
            <i class="ti ti-user-circle shrink-0 text-[17px] text-slate-400 group-hover:text-white" aria-hidden="true"></i>
            <span class="admin-nav-label truncate">Mon compte</span>
        </a>
    </div>
</aside>
