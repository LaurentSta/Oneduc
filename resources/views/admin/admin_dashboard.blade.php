<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Administration') — Oneduc</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('backend/assets/vendor/fonts/tabler-icons.css') }}">
    <link rel="icon" href="{{ asset('backend/assets/img/favicon/favicon.ico') }}" type="image/x-icon">

    {{-- DataTables historique. À retirer lorsque les listes seront toutes paginées côté serveur. --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <style>
        :root {
            --admin-header-height: 3.5rem;
            --admin-sidebar-width: 15.5rem;
            --admin-sidebar-collapsed-width: 4.5rem;
        }

        .admin-shell {
            min-height: 100vh;
            padding-top: var(--admin-header-height);
            transition: margin-left 200ms ease;
        }

        .admin-sidebar {
            width: var(--admin-sidebar-width);
            transition: width 200ms ease, transform 200ms ease;
        }

        @media (min-width: 1024px) {
            .admin-shell {
                margin-left: var(--admin-sidebar-width);
            }

            .admin-shell.admin-shell--navigation-reduite {
                margin-left: var(--admin-sidebar-collapsed-width);
            }

            .admin-sidebar.admin-sidebar--collapsed {
                width: var(--admin-sidebar-collapsed-width);
            }

            .admin-sidebar--collapsed .admin-nav-label,
            .admin-sidebar--collapsed .admin-nav-section-label,
            .admin-sidebar--collapsed .admin-sidebar-context {
                display: none;
            }

            .admin-sidebar--collapsed .admin-nav-link {
                justify-content: center;
                padding-inline: 0.5rem;
            }

            .admin-sidebar--collapsed .admin-nav-group {
                padding-inline: 0.5rem;
            }
        }

        @media (max-width: 1023.98px) {
            .admin-shell {
                margin-left: 0;
            }
        }
    </style>

    @stack('styles')
</head>

<body
    x-data="{
        navigationMobileOuverte: false,
        navigationReduite: false,
        largeurEcran: window.innerWidth,
        declencheurNavigationMobile: null,
        initialiserNavigation() {
            try {
                this.navigationReduite = window.localStorage.getItem('admin-navigation-reduite') === '1';
            } catch (error) {
                this.navigationReduite = false;
            }
        },
        estMobile() {
            return this.largeurEcran < 1024;
        },
        basculerNavigation(declencheur = null) {
            if (this.estMobile()) {
                if (this.navigationMobileOuverte) {
                    this.fermerNavigationMobile();
                } else {
                    this.ouvrirNavigationMobile(declencheur);
                }

                return;
            }

            this.navigationReduite = !this.navigationReduite;

            try {
                window.localStorage.setItem('admin-navigation-reduite', this.navigationReduite ? '1' : '0');
            } catch (error) {
                // La navigation reste utilisable lorsque le stockage local est indisponible.
            }
        },
        ouvrirNavigationMobile(declencheur) {
            this.declencheurNavigationMobile = declencheur;
            this.navigationMobileOuverte = true;
            this.$nextTick(() => {
                document.querySelector('#navigation-administrateur a, #navigation-administrateur button')?.focus();
            });
        },
        fermerNavigationMobile(retablirFocus = true) {
            const etaitOuverte = this.navigationMobileOuverte;
            this.navigationMobileOuverte = false;

            if (etaitOuverte && retablirFocus && this.declencheurNavigationMobile) {
                const declencheur = this.declencheurNavigationMobile;
                this.$nextTick(() => declencheur?.focus());
            }

            this.declencheurNavigationMobile = null;
        },
        gererTabulationNavigation(event) {
            if (!this.estMobile() || !this.navigationMobileOuverte) {
                return;
            }

            const navigation = document.getElementById('navigation-administrateur');
            const elements = [...navigation.querySelectorAll('a, button, [tabindex]:not([tabindex=\'-1\'])')]
                .filter(element => !element.hasAttribute('disabled') && element.offsetParent !== null);

            if (elements.length === 0) {
                event.preventDefault();
                return;
            }

            const premier = elements[0];
            const dernier = elements[elements.length - 1];

            if (event.shiftKey && document.activeElement === premier) {
                event.preventDefault();
                dernier.focus();
            } else if (!event.shiftKey && document.activeElement === dernier) {
                event.preventDefault();
                premier.focus();
            }
        },
        redimensionnerNavigation() {
            this.largeurEcran = window.innerWidth;

            if (!this.estMobile()) {
                this.fermerNavigationMobile(false);
            }
        }
    }"
    x-init="initialiserNavigation()"
    x-effect="document.documentElement.classList.toggle('overflow-hidden', navigationMobileOuverte && estMobile())"
    x-on:resize.window="redimensionnerNavigation()"
    x-on:keydown.escape.window="fermerNavigationMobile()"
    class="admin-interface min-h-screen bg-slate-100 font-sans text-slate-900 antialiased"
>
    <a
        href="#contenu-principal"
        class="fixed left-3 top-3 z-[70] -translate-y-20 rounded-md bg-white px-4 py-2 text-sm font-semibold text-bleuone transition focus:translate-y-0 focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-orangeone"
    >
        Aller au contenu principal
    </a>

    @include('admin.body.header')
    @include('admin.body.sidebar')

    <div
        class="admin-shell flex flex-col"
        :class="{ 'admin-shell--navigation-reduite': navigationReduite }"
    >
        <main id="contenu-principal" class="flex-1 px-4 py-4 sm:px-5 lg:px-6" tabindex="-1">
            @php
                $flashType = null;
                $flashMessage = null;

                foreach (['error', 'warning', 'success', 'message', 'status'] as $type) {
                    if (session()->has($type)) {
                        $flashType = $type;
                        $flashMessage = session($type);
                        break;
                    }
                }

                $flashConfig = [
                    'error' => [
                        'classes' => 'border-red-200 bg-red-50 text-red-900',
                        'iconClasses' => 'bg-red-100 text-red-700',
                        'icon' => 'ti-alert-circle',
                        'role' => 'alert',
                    ],
                    'warning' => [
                        'classes' => 'border-amber-200 bg-amber-50 text-amber-900',
                        'iconClasses' => 'bg-amber-100 text-amber-700',
                        'icon' => 'ti-alert-triangle',
                        'role' => 'alert',
                    ],
                    'success' => [
                        'classes' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
                        'iconClasses' => 'bg-emerald-100 text-emerald-700',
                        'icon' => 'ti-circle-check',
                        'role' => 'status',
                    ],
                    'message' => [
                        'classes' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
                        'iconClasses' => 'bg-emerald-100 text-emerald-700',
                        'icon' => 'ti-circle-check',
                        'role' => 'status',
                    ],
                    'status' => [
                        'classes' => 'border-sky-200 bg-sky-50 text-sky-900',
                        'iconClasses' => 'bg-sky-100 text-sky-700',
                        'icon' => 'ti-info-circle',
                        'role' => 'status',
                    ],
                ];
            @endphp

            @if ($flashMessage)
                @php($configurationFlash = $flashConfig[$flashType])
                <div
                    x-data="{ visible: true }"
                    x-show="visible"
                    x-transition.opacity.duration.150ms
                    role="{{ $configurationFlash['role'] }}"
                    aria-live="{{ $configurationFlash['role'] === 'alert' ? 'assertive' : 'polite' }}"
                    class="mb-4 flex items-start gap-3 rounded-lg border px-3 py-2.5 text-sm {{ $configurationFlash['classes'] }}"
                >
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md {{ $configurationFlash['iconClasses'] }}" aria-hidden="true">
                        <i class="ti {{ $configurationFlash['icon'] }} text-base"></i>
                    </span>
                    <p class="min-w-0 flex-1 pt-1 font-medium">{{ $flashMessage }}</p>
                    <button
                        type="button"
                        x-on:click="visible = false"
                        class="rounded-md p-1 opacity-70 transition hover:bg-black/5 hover:opacity-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-current"
                        aria-label="Fermer le message"
                    >
                        <i class="ti ti-x text-base" aria-hidden="true"></i>
                    </button>
                </div>
            @endif

            <div id="page-transition">
                @yield('admin')
            </div>
        </main>
    </div>

    @stack('scripts')
    @include('partials.a11y-scripts')
</body>
</html>
