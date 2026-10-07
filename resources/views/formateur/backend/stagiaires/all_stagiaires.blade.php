<!-- /home/laurents/Oneduc_Dev/resources/views/formateur/backend/stagiaires/all_stagiaires.blade.php -->
@extends('formateur.dashboard')

@section('formateur')

{{-- Wrapper unique --}}
<div class="max-w-[1285px] mx-auto px-8">

  <x-formateur.page-header
    :breadcrumb="[['label' => 'Accueil', 'url' => route('formateur.dashboard')], ['label' => 'Mes stagiaires']]"
    title="Mes stagiaires"
    subtitle="Gérer l’ensemble de vos apprenants en un coup d’œil."
    :image="asset('images/svg/header/Stagiaires.svg')"
    imageAlt="Illustration des stagiaires"
  >
    <x-slot:badges>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-bleuone/15 bg-bleuone/5 px-3 py-1 text-bleuone">
        {{ $stagiaires->total() }} stagiaires
      </span>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-orangeone/20 bg-orangeone/10 px-3 py-1 text-orangeone">
        {{ $groupes->count() }} groupes
      </span>
    </x-slot:badges>
  </x-formateur.page-header>

  {{-- 📋 CONTENU PRINCIPAL --}}
  <main class="space-y-8">
    @if (session('success'))
      <div class="rounded-[16px] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
      </div>
    @endif

    {{-- 🔎 Filtres --}}
    <div x-data="{ filtersOpen: {{ (request()->filled('search') || request()->filled('group_id') || (int) request('per_page', 10) !== 10) ? 'true' : 'false' }} }">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('formateur.emargement.index', request()->filled('group_id') ? ['group_id' => request('group_id')] : []) }}"
           class="btn-oneduc-outline inline-flex h-10 items-center gap-2 !text-sm">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6.586-6.586a2 2 0 112.828 2.828L11.828 13.828a4 4 0 01-1.414.94l-3.536 1.178a.5.5 0 01-.632-.632l1.178-3.536a4 4 0 01.94-1.414z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 21h14"/>
          </svg>
          <span>Émargement</span>
        </a>

        <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
          <button type="button"
                  @click="filtersOpen = !filtersOpen"
                  :aria-expanded="filtersOpen"
                  class="btn-oneduc-blue inline-flex h-10 items-center gap-2 !text-sm">
            <x-icons.filter-iconify class="h-4 w-4 shrink-0" />
            <span>Filtrer</span>
          </button>
          <a href="{{ route('formateur.stagiaires.create', request()->filled('group_id') ? ['group_id' => request('group_id')] : []) }}"
             class="btn-oneduc-blue inline-flex h-10 items-center gap-2 !text-sm">
            <x-icons.add-stagiaire-button-iconify class="h-4 w-4 shrink-0" />
            Ajouter un stagiaire
          </a>
        </div>
      </div>

      <form method="GET" x-show="filtersOpen" x-collapse.duration.300ms x-cloak class="mt-3 space-y-3">
        <div class="flex flex-wrap items-end gap-3 rounded-md border border-gray-200 bg-white p-4">
          <div class="w-full md:w-[180px]">
            <label for="per_page" class="sr-only">Nombre de stagiaires à afficher</label>
            <select id="per_page"
                    name="per_page"
                    class="h-10 w-full rounded-md border border-gray-300 px-4 text-sm font-lisible shadow-sm focus:border-orangeone focus:ring-orangeone">
              @foreach ($allowedPerPage as $option)
                <option value="{{ $option }}" @selected($perPage === $option)>
                  {{ $option }} par page
                </option>
              @endforeach
            </select>
          </div>

          <div class="w-full md:flex-1 md:min-w-[260px]">
            <label for="search" class="sr-only">Recherche prénom</label>
            <input type="text"
                  id="search"
                  name="search"
                  value="{{ request('search') }}"
                  placeholder="Recherche prénom"
                  class="h-10 w-full rounded-md border border-gray-300 px-4 text-sm font-lisible shadow-sm focus:border-orangeone focus:ring-orangeone">
          </div>

          <div class="w-full md:w-[280px]">
            <label for="group_id" class="sr-only">Recherche de groupe</label>
            <select id="group_id"
                    name="group_id"
                    class="h-10 w-full rounded-md border border-gray-300 px-4 text-sm font-lisible shadow-sm focus:border-orangeone focus:ring-orangeone">
              <option value="">Recherche de groupe</option>
              @foreach ($groupes as $groupe)
                <option value="{{ $groupe->id }}" @selected((string)request('group_id') === (string)$groupe->id)>
                  {{ $groupe->name }}
                </option>
              @endforeach
            </select>
          </div>

          <button type="submit" class="btn-oneduc-blue inline-flex h-10 w-full items-center justify-center gap-2 sm:w-[200px] !text-sm">
            <x-icons.filter-iconify class="h-4 w-4 shrink-0" />
            <span>Filtrer</span>
          </button>

          @if(request()->filled('search') || request()->filled('group_id') || request('per_page', 10) != 10)
            <a href="{{ route('formateur.stagiaires.index') }}"
              class="btn-oneduc-outline h-10 !text-sm">
              Réinitialiser
            </a>
          @endif
        </div>

        @if(request('group_id'))
          <p class="pt-1 text-sm text-gray-600 font-varela">
            Groupe sélectionné :
            <span class="text-orangeone font-semibold">
              {{ optional($groupes->firstWhere('id', (int)request('group_id')))->name }}
            </span>
          </p>
        @endif
      </form>
    </div>


    {{-- 📊 Tableau des stagiaires --}}
    <div class="overflow-x-auto overflow-y-visible bg-white rounded-md border-2 border-bleuone/20">
      <table class="min-w-full bg-white text-sm text-left text-gray-800 font-lisible">
        <thead class="bg-bleuone uppercase text-xs text-white font-varela sticky top-0 z-10">
          <tr>
            <th class="px-6 py-3">#</th>
            <th class="px-6 py-3">Prénom</th>
            <th class="px-6 py-3">Nom</th>
            <th class="px-6 py-3">Email</th>
            <th class="px-6 py-3">Code d'accès</th>
            <th class="px-6 py-3">Groupe(s)</th>
            <th class="px-6 py-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($stagiaires as $index => $stagiaire)
            <tr class="border-t {{ $index % 2 === 0 ? 'bg-white' : 'bg-orangeone/8' }} hover:bg-orangeone/15 transition-colors">
              <td class="px-6 py-4 font-medium">{{ $stagiaires->firstItem() + $index }}</td>
              <td class="px-6 py-4">{{ $stagiaire->prenom }}</td>
              <td class="px-6 py-4">{{ $stagiaire->name }}</td>
              <td class="px-6 py-4">{{ $stagiaire->email }}</td>
              <td class="px-6 py-4 font-mono text-sm text-orangeone">
                {{ $stagiaire->code_acces ?? '—' }}
              </td>
              <td class="px-6 py-4">
                @forelse ($stagiaire->groupesStagiaire as $groupe)
                  <span class="inline-block bg-vertone/10 text-vertone text-xs font-varela px-2 py-1 rounded-full mr-1">
                    {{ $groupe->name }}
                  </span>
                @empty
                  <span class="text-gray-400 text-xs italic">Aucun</span>
                @endforelse
              </td>
              <td class="px-6 py-4">
                <div class="flex gap-2">
                  {{-- Modifier --}}
                  <div class="relative inline-flex group">
                    <a href="{{ route('formateur.stagiaires.edit', $stagiaire->id) }}"
                       class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-orangeone/20 bg-orangeone/10 text-orangeone transition hover:border-orangeone hover:bg-orangeone hover:text-white"
                       aria-label="Modifier {{ trim($stagiaire->prenom . ' ' . $stagiaire->name) ?: $stagiaire->email }}">
                      <x-icons.edit-iconify class="h-4 w-4" />
                    </a>
                    <span class="pointer-events-none absolute -top-9 left-1/2 z-20 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-lg group-hover:block">
                      Modifier ce stagiaire
                    </span>
                  </div>

                  {{-- Supprimer --}}
                  <div class="relative inline-flex group">
                    <form action="{{ route('formateur.stagiaires.destroy', $stagiaire->id) }}"
                          method="POST">
                      @csrf
                      @method('DELETE')
                      <button type="button"
                              class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-bleuone/20 bg-bleuone/10 text-bleuone transition hover:border-bleuone hover:bg-bleuone hover:text-white"
                              aria-label="Supprimer {{ trim($stagiaire->prenom . ' ' . $stagiaire->name) ?: $stagiaire->email }}"
                              data-delete-trigger
                              data-stagiaire-name="{{ trim($stagiaire->prenom . ' ' . $stagiaire->name) ?: $stagiaire->email }}">
                        <x-icons.trash-iconify class="h-5 w-5" />
                      </button>
                    </form>
                    <span class="pointer-events-none absolute -top-9 left-1/2 z-20 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-lg group-hover:block">
                      Supprimer ce stagiaire
                    </span>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="px-6 py-4 text-center text-gray-500">Aucun stagiaire trouvé.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{-- 📄 Pagination --}}
    <div>
      {{ $stagiaires->links('pagination::tailwind') }}
    </div>

    <div id="deleteStagiaireModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-labelledby="deleteStagiaireTitle">
      <div class="absolute inset-0 bg-slate-900/50" data-delete-dismiss></div>
      <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md rounded-[20px] border border-bleuone/10 bg-white p-6 shadow-xl">
          <div class="flex items-start gap-3">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-bleuone/10 text-bleuone">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 7.5h15m-10.5 0V6a1.5 1.5 0 011.5-1.5h3A1.5 1.5 0 0115 6v1.5m-7.5 0v10.125A1.875 1.875 0 009.375 19.5h5.25A1.875 1.875 0 0016.5 17.625V7.5M10 10.5v6m4-6v6" />
              </svg>
            </div>
            <div class="flex-1">
              <h3 id="deleteStagiaireTitle" class="text-lg font-semibold text-bleuone">Confirmer la suppression</h3>
              <p class="mt-2 text-sm text-gray-700">
                Voulez-vous vraiment supprimer
                <span id="deleteStagiaireName" class="font-semibold text-gray-900"></span> ?
              </p>
              <p class="mt-2 text-sm text-gray-500">Cette action est irréversible.</p>
            </div>
          </div>

          <div class="mt-6 flex flex-wrap justify-end gap-3">
            <button type="button" class="btn-oneduc-outline h-10 !text-sm" data-delete-dismiss>
              Annuler
            </button>
            <button type="button" id="confirmDeleteStagiaire" class="inline-flex h-10 items-center justify-center rounded-md border border-red-600 bg-red-600 px-5 text-sm font-varela text-white transition hover:bg-red-700">
              Supprimer
            </button>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('deleteStagiaireModal');
    const modalName = document.getElementById('deleteStagiaireName');
    const confirmButton = document.getElementById('confirmDeleteStagiaire');

    if (!modal || !modalName || !confirmButton) {
      return;
    }

    const dismissButtons = modal.querySelectorAll('[data-delete-dismiss]');
    let activeForm = null;
    let activeTrigger = null;

    const closeModal = () => {
      modal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
      activeForm = null;

      if (activeTrigger) {
        activeTrigger.focus();
      }
    };

    const openModal = (form, trigger, stagiaireName) => {
      activeForm = form;
      activeTrigger = trigger;
      modalName.textContent = stagiaireName;
      modal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
      confirmButton.focus();
    };

    document.querySelectorAll('[data-delete-trigger]').forEach((button) => {
      button.addEventListener('click', () => {
        const form = button.closest('form');

        if (!form) {
          return;
        }

        openModal(form, button, button.dataset.stagiaireName || 'ce stagiaire');
      });
    });

    dismissButtons.forEach((button) => {
      button.addEventListener('click', closeModal);
    });

    confirmButton.addEventListener('click', () => {
      if (activeForm) {
        activeForm.submit();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
        closeModal();
      }
    });
  });
</script>
@endsection
