{{-- /home/laurents/Oneduc_Dev/resources/views/formateur/formations/index.blade.php --}}
@extends('formateur.dashboard')

@section('formateur')

<div
  class="max-w-[1285px] mx-auto px-8"
  x-data="{
    tab: '{{ in_array(request('tab'), ['parcours', 'creations']) ? request('tab') : 'catalogue' }}',
    filtersOpen: {{ request()->filled('search') || request()->filled('categorie') ? 'true' : 'false' }},
    setTab(value) {
      this.tab = value;
      const url = new URL(window.location.href);
      if (value === 'parcours' || value === 'creations') {
        url.searchParams.set('tab', value);
      } else {
        url.searchParams.delete('tab');
      }
      window.history.replaceState({}, '', url);
    }
  }"
>

  @php
    $formationsTabLabel = match(request('tab')) {
      'parcours' => 'Parcours',
      'creations' => 'Créations',
      default => 'Catalogue',
    };
  @endphp
  <x-formateur.page-header
    :breadcrumb="[['label' => 'Accueil', 'url' => route('formateur.dashboard')], ['label' => $formationsTabLabel]]"
    title="Mes formations"
    subtitle="Suivez les formations que vous utilisez dans vos groupes."
    :image="asset('images/svg/header/Formations.svg')"
    imageAlt="Illustration des formations"
  >
    <x-slot:badges>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-bleuone/15 bg-bleuone/5 px-3 py-1 text-bleuone">
        {{ $modules->total() }} formations
      </span>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-orangeone/20 bg-orangeone/10 px-3 py-1 text-orangeone">
        {{ $mesParcours->count() }} parcours
      </span>
    </x-slot:badges>
  </x-formateur.page-header>

  {{-- ONGLETS --}}
  <div class="mb-4 rounded-t-[14px] border border-gray-200 border-b-2 border-b-bleuone/15 bg-white px-3 pt-3 shadow-sm">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div role="tablist" aria-label="Vue des formations" class="flex items-end gap-1">
        <div class="relative inline-flex group">
          <button
            type="button"
            role="tab"
            @click="setTab('catalogue')"
            :aria-selected="tab === 'catalogue'"
            :class="tab === 'catalogue' ? 'border-[#E94D2A] text-[#E94D2A]' : 'border-transparent text-gray-500 hover:text-bleuone'"
            class="-mb-[2px] inline-flex items-center gap-2 border-b-2 px-4 pb-3 pt-2 text-sm font-semibold transition"
          >
            Catalogue
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $modules->total() }}</span>
          </button>
          <span class="pointer-events-none absolute -top-9 left-1/2 z-20 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-lg group-hover:block">
            Formations disponibles pour vos groupes
          </span>
        </div>
        <div class="relative inline-flex group">
          <button
            type="button"
            role="tab"
            @click="setTab('creations')"
            :aria-selected="tab === 'creations'"
            :class="tab === 'creations' ? 'border-[#E94D2A] text-[#E94D2A]' : 'border-transparent text-gray-500 hover:text-bleuone'"
            class="-mb-[2px] inline-flex items-center gap-2 border-b-2 px-4 pb-3 pt-2 text-sm font-semibold transition"
          >
            Créations
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $mesCreations->count() }}</span>
          </button>
          <span class="pointer-events-none absolute -top-9 left-1/2 z-20 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-lg group-hover:block">
            Créez vos propres formations
          </span>
        </div>
        <div class="relative inline-flex group">
          <button
            type="button"
            role="tab"
            @click="setTab('parcours')"
            :aria-selected="tab === 'parcours'"
            :class="tab === 'parcours' ? 'border-[#E94D2A] text-[#E94D2A]' : 'border-transparent text-gray-500 hover:text-bleuone'"
            class="-mb-[2px] inline-flex items-center gap-2 border-b-2 px-4 pb-3 pt-2 text-sm font-semibold transition"
          >
            Parcours
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $mesParcours->count() }}</span>
          </button>
          <span class="pointer-events-none absolute -top-9 left-1/2 z-20 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-lg group-hover:block">
            Ordonnez les formations suivies par un groupe
          </span>
        </div>
      </div>

      <button
        type="button"
        x-show="tab === 'catalogue'"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click="filtersOpen = !filtersOpen"
        :aria-expanded="filtersOpen"
        class="mb-2 inline-flex h-9 items-center gap-2 rounded-md border border-gray-200 bg-white px-3 text-sm font-medium text-gray-600 transition hover:border-orangeone/50 hover:text-orangeone"
      >
        <x-icons.filter-iconify class="h-4 w-4" />
        Filtrer
      </button>
    </div>
  </div>

  {{-- Barre de recherche --}}
  <form method="GET" x-show="tab === 'catalogue' && filtersOpen" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="mb-6">
    <div class="flex flex-wrap items-end gap-3 rounded-[10px] border border-gray-200 bg-white p-3 shadow-sm">
      <div class="w-full md:flex-1">
        <label for="search" class="sr-only">Recherche</label>
        <input type="text"
               id="search"
               name="search"
               value="{{ $search ?? request('search') }}"
               placeholder="Rechercher un titre de formation"
               class="h-10 w-full rounded-md border border-gray-300 px-4 text-sm font-lisible shadow-sm focus:border-orangeone focus:ring-orangeone">
      </div>

      <div class="w-full md:w-64">
        <label for="categorie" class="sr-only">Catégorie</label>
        <select id="categorie"
                name="categorie"
                class="h-10 w-full rounded-md border border-gray-300 px-3 text-sm font-lisible shadow-sm focus:border-orangeone focus:ring-orangeone">
          <option value="">Toutes les catégories</option>
          @foreach($categories as $categorie)
            <option value="{{ $categorie->id }}" @selected((int) $categorieId === $categorie->id)>
              {{ $categorie->category_name }}
            </option>
          @endforeach
        </select>
      </div>

      <button type="submit" class="btn-oneduc h-10 !text-sm">
        Rechercher
      </button>

      @if(request()->filled('search') || request()->filled('categorie'))
        <a href="{{ route('formateur.formations.index') }}"
           class="btn-oneduc-outline h-10 !text-sm">
          Réinitialiser
        </a>
      @endif
    </div>

    @if(request('search') || $categorieId > 0)
      <p class="pt-2 text-sm text-gray-600 font-varela">
        Filtres actifs :
        @if(request('search'))
          <span class="text-orangeone font-semibold">{{ request('search') }}</span>
        @endif
        @if($categorieId > 0)
          <span class="text-orangeone font-semibold">
            {{ $categories->firstWhere('id', $categorieId)?->category_name ?? 'Catégorie inconnue' }}
          </span>
        @endif
      </p>
    @endif
  </form>

  {{-- ── CATALOGUE ─────────────────────────────────────────────────────────── --}}
  <section x-show="tab === 'catalogue'" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95">
    <div class="space-y-8">
      {{-- Tableau des modules --}}
      <div class="overflow-x-auto overflow-y-visible bg-white rounded-md border-2 border-bleuone/20">
        <table class="min-w-full bg-white text-sm text-left text-gray-800 font-lisible">
          <thead class="bg-bleuone uppercase text-xs text-white font-varela sticky top-0 z-10">
            <tr>
              <th class="px-6 py-3">#</th>
              <th class="px-6 py-3">Titre</th>
              <th class="px-6 py-3">Date de création</th>
              <th class="w-[180px] px-6 py-3">Statut</th>
              <th class="px-6 py-3 text-center">Groupes associés</th>
              <th class="px-6 py-3 text-center">Stagiaires total</th>
              <th class="w-[210px] px-6 py-3">Actions</th>
            </tr>
          </thead>

          <tbody>
            @forelse ($modules as $index => $module)
              @php
                $groupes = $module->groups ?? collect();
                $stagiaires = $groupes
                  ->flatMap(fn ($g) => ($g->users ?? collect())->where('role', 'stagiaire'))
                  ->unique('id');

                if ($groupes->count() > 0) {
                  $statut = 'utilise';
                } elseif ((int)($module->status ?? 0) === 1) {
                  $statut = 'non_utilise';
                } else {
                  $statut = 'indisponible';
                }

                $titre = $module->module_title ?? $module->module_name ?? $module->name ?? 'Sans titre';
                $firstSection = ($module->sections ?? collect())->sortBy('id')->first();
                $officialUrl = $firstSection
                  ? route('formateur.formations.detail', ['module' => $module->id])
                  : null;
              @endphp

              <tr class="border-t {{ $index % 2 === 0 ? 'bg-white' : 'bg-orangeone/8' }} hover:bg-orangeone/15 transition-colors">
                <td class="px-6 py-4 font-medium">{{ $modules->firstItem() + $index }}</td>

                <td class="px-6 py-4">
                  <p class="font-semibold text-gray-900">{{ $titre }}</p>
                  <p class="mt-1 text-xs text-gray-500">
                    Catégorie : {{ $module->category?->category_name ?? 'Non renseignée' }}
                  </p>
                </td>

                <td class="px-6 py-4 text-gray-700">
                  {{ optional($module->created_at)->format('d/m/Y') ?? '—' }}
                </td>

                <td class="w-[180px] px-6 py-4">
                  @if($statut === 'utilise')
                    <span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 text-green-800 bg-green-100 rounded-full text-xs font-semibold border border-green-200">
                      <span class="h-1.5 w-1.5 rounded-full bg-green-600 mr-2" aria-hidden="true"></span>
                      Utilisé
                    </span>
                  @elseif($statut === 'non_utilise')
                    <span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 text-blue-800 bg-blue-100 rounded-full text-xs font-semibold border border-blue-200">
                      <span class="h-1.5 w-1.5 rounded-full bg-blue-600 mr-2" aria-hidden="true"></span>
                      Non utilisé
                    </span>
                  @else
                    <span class="inline-flex items-center whitespace-nowrap px-2.5 py-1 text-red-800 bg-red-100 rounded-full text-xs font-semibold border border-red-200">
                      <span class="h-1.5 w-1.5 rounded-full bg-red-600 mr-2" aria-hidden="true"></span>
                      Indisponible
                    </span>
                  @endif
                </td>

                <td class="px-6 py-4 text-center font-semibold text-gray-900">
                  {{ $groupes->count() }}
                </td>

                <td class="px-6 py-4 text-center font-semibold text-gray-900">
                  {{ $stagiaires->count() }}
                </td>

                <td class="w-[210px] px-6 py-4">
                  <div class="flex flex-wrap items-center gap-2">
                    @if($statut === 'indisponible')
                      <span class="whitespace-nowrap text-sm text-gray-400">Accès indisponible</span>
                    @elseif($officialUrl)
                      <div class="relative inline-flex group">
                        <a href="{{ $officialUrl }}"
                           class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-orangeone/20 bg-orangeone/10 text-orangeone transition hover:border-orangeone hover:bg-orangeone hover:text-white"
                           aria-label="Voir la formation {{ $titre }}">
                          <x-icons.eye-iconify class="h-4 w-4" />
                        </a>
                        <span class="pointer-events-none absolute -top-9 left-1/2 z-20 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-lg group-hover:block">
                          Voir la formation
                        </span>
                      </div>
                    @else
                      <span class="whitespace-nowrap text-sm text-gray-400">Accès indisponible</span>
                    @endif

                    @if(!$module->is_trainer_authored)
                      <div class="relative inline-flex group">
                        <button type="button" x-data x-on:click="$dispatch('open-modal', 'duplicate-module-{{ $module->id }}')"
                                aria-label="Dupliquer la formation {{ $titre }}"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-emerald-300 bg-emerald-50 text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-600 hover:text-white">
                          <x-icons.copy-iconify class="h-4 w-4" />
                        </button>
                        <span class="pointer-events-none absolute -top-9 left-1/2 z-20 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-lg group-hover:block">
                          Dupliquer
                        </span>
                      </div>
                      <x-confirm-modal
                        name="duplicate-module-{{ $module->id }}"
                        title="Dupliquer cette formation ?"
                        message="Elle sera copiée dans 'Mes créations' pour que vous puissiez la modifier librement."
                        :action="route('formateur.modules.builder.duplicate', $module)"
                        method="POST"
                        confirm-label="Dupliquer"
                      />
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">Aucune formation trouvée.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <div class="inline-flex items-center gap-3 rounded-full border border-bleuone/20 bg-white px-4 py-2 text-sm font-varela text-gray-700">
          <span class="flex h-8 w-8 items-center justify-center rounded-full bg-bleuone/10 text-bleuone">
            <x-icons.module-iconify class="h-4 w-4" />
          </span>
          <span>Nombre total de formations :</span>
          <span class="font-bold text-bleuone">{{ $modules->total() }}</span>
        </div>
      </div>

      <div>{{ $modules->links('pagination::tailwind') }}</div>

    </div>
  </section>

  {{-- ── MES CRÉATIONS ─────────────────────────────────────────────────────── --}}
  <section x-show="tab === 'creations'" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95">

    @if ($mesCreations->isNotEmpty())
      <div class="flex justify-end mb-4">
        <a href="{{ route('formateur.modules.builder.create') }}"
           class="inline-flex items-center gap-2 px-5 py-3 rounded-[10px] bg-[#E94D2A] text-white font-medium hover:bg-[#cf4121] transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Créer une formation
        </a>
      </div>
    @endif

    @if (session('success'))
      <div class="mb-4 px-4 py-3 rounded-[10px] bg-green-50 text-green-800 border border-green-200 text-sm">
        {{ session('success') }}
      </div>
    @endif

    @if ($mesCreations->isEmpty())
      <div class="bg-white rounded-[20px] shadow-md px-8 py-16 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
        <p class="text-gray-500 mb-6">Vous n'avez pas encore créé de formation.</p>
        <a href="{{ route('formateur.modules.builder.create') }}"
           class="inline-flex items-center gap-2 px-5 py-3 rounded-[10px] bg-[#E94D2A] text-white font-medium hover:bg-[#cf4121] transition">
          Créer ma première formation
        </a>
      </div>
    @else
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach ($mesCreations as $creation)
          <div class="bg-white rounded-[20px] shadow-md flex flex-col overflow-hidden">
            <div class="px-6 pt-5 pb-4 flex-1">
              <h2 class="text-base font-semibold text-gray-900 mb-1 line-clamp-2">{{ $creation->module_title }}</h2>
              @if ($creation->description)
                <p class="text-sm text-gray-500 line-clamp-3 mb-3">{{ $creation->description }}</p>
              @endif
              <div class="flex items-center gap-2 text-xs text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                {{ $creation->sections_count }} chapitre{{ $creation->sections_count > 1 ? 's' : '' }}
                · {{ $creation->groups_count }} groupe{{ $creation->groups_count > 1 ? 's' : '' }} assigné{{ $creation->groups_count > 1 ? 's' : '' }}
              </div>
            </div>
            <div class="border-t border-gray-100 px-6 py-3 flex justify-between items-center bg-gray-50">
              <span class="text-xs text-gray-400">{{ $creation->updated_at->diffForHumans() }}</span>
              <div class="flex gap-2">
                <a href="{{ route('formateur.formations.preview', $creation) }}" target="_blank" rel="noopener"
                   class="text-xs px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-700 hover:border-[#E94D2A] hover:text-[#E94D2A] transition">
                  Aperçu
                </a>
                <a href="{{ route('formateur.modules.builder.edit', $creation) }}"
                   class="text-xs px-3 py-1.5 rounded-lg bg-[#E94D2A] text-white hover:bg-[#cf4121] transition">
                  Modifier
                </a>
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'delete-creation-{{ $creation->id }}')"
                        class="text-xs px-3 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:border-red-300 hover:text-red-600 transition">
                  Supprimer
                </button>
                <x-confirm-modal
                  name="delete-creation-{{ $creation->id }}"
                  title="Supprimer cette formation ?"
                  message="Cette action est irréversible."
                  :action="route('formateur.modules.builder.destroy', $creation)"
                  method="DELETE"
                  confirm-label="Supprimer"
                />
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

  </section>

  {{-- ── MES PARCOURS ──────────────────────────────────────────────────────── --}}
  <section x-show="tab === 'parcours'" x-cloak
           x-transition:enter="transition ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="transition ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95">

    @if ($mesParcours->isNotEmpty())
      <div class="flex justify-end mb-4">
        <a href="{{ route('formateur.mes-parcours.create') }}"
           class="inline-flex items-center gap-2 px-5 py-3 rounded-[10px] bg-[#E94D2A] text-white font-medium hover:bg-[#cf4121] transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Créer un parcours
        </a>
      </div>
    @endif

    @if (session('success'))
      <div class="mb-4 px-4 py-3 rounded-[10px] bg-green-50 text-green-800 border border-green-200 text-sm">
        {{ session('success') }}
      </div>
    @endif

    @if ($mesParcours->isEmpty())
      <div class="bg-white rounded-[20px] shadow-md px-8 py-16 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
        </svg>
        <p class="text-gray-500 mb-6">Vous n'avez pas encore créé de parcours.</p>
        <a href="{{ route('formateur.mes-parcours.create') }}"
           class="inline-flex items-center gap-2 px-5 py-3 rounded-[10px] bg-[#E94D2A] text-white font-medium hover:bg-[#cf4121] transition">
          Créer mon premier parcours
        </a>
      </div>
    @else
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach ($mesParcours as $item)
          <div class="bg-white rounded-[20px] shadow-md flex flex-col overflow-hidden">
            <div class="px-6 pt-5 pb-4 flex-1">
              <h2 class="text-base font-semibold text-gray-900 mb-1 line-clamp-2">{{ $item->title }}</h2>
              @if ($item->description)
                <p class="text-sm text-gray-500 line-clamp-3 mb-3">{{ $item->description }}</p>
              @endif
              <div class="flex items-center gap-2 text-xs text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                {{ $item->items_count }} étape{{ $item->items_count > 1 ? 's' : '' }}
              </div>
            </div>
            <div class="border-t border-gray-100 px-6 py-3 flex justify-between items-center bg-gray-50">
              <span class="text-xs text-gray-400">{{ $item->created_at->diffForHumans() }}</span>
              <div class="flex gap-2">
                <a href="{{ route('formateur.mes-parcours.show', $item) }}"
                   class="text-xs px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-700 hover:border-[#E94D2A] hover:text-[#E94D2A] transition">
                  Voir
                </a>
                <a href="{{ route('formateur.mes-parcours.edit', $item) }}"
                   class="text-xs px-3 py-1.5 rounded-lg bg-[#E94D2A] text-white hover:bg-[#cf4121] transition">
                  Modifier
                </a>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

  </section>

</div>
@endsection
