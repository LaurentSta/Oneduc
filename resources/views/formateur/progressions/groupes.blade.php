@extends('formateur.dashboard')

@section('formateur')

<div class="max-w-[1285px] mx-auto px-8">

  <x-formateur.page-header
    :breadcrumb="[['label' => 'Accueil', 'url' => route('formateur.dashboard')], ['label' => 'Progression par groupe']]"
    title="Progression par groupe"
    subtitle="Suivez l’avancement des groupes et repérez ceux qui ont besoin d’accompagnement."
    :image="asset('images/svg/header/Progression.svg')"
    imageAlt="Illustration progression par groupe"
  >
    <x-slot:badges>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-bleuone/15 bg-bleuone/5 px-3 py-1 text-bleuone">
        {{ $totalGroupes }} groupes
      </span>
    </x-slot:badges>
  </x-formateur.page-header>

  <main class="space-y-8">

    {{-- Recherche --}}
    <form method="GET" class="space-y-3">
      <div class="flex flex-wrap items-end gap-3">
        <div class="w-full md:flex-1 md:min-w-[280px]">
        <label for="search" class="sr-only">Recherche</label>
        <input type="text"
               id="search"
               name="search"
               value="{{ $search ?? request('search') }}"
               placeholder="Recherche nom du groupe"
               class="h-10 w-full rounded-md border border-gray-300 px-4 text-sm font-lisible shadow-sm focus:border-orangeone focus:ring-orangeone">
        </div>

        <button type="submit" class="btn-oneduc h-10 !text-sm">
          <x-icons.filter-iconify class="h-4 w-4" />
          Filtrer
        </button>

        @if(request()->filled('search'))
          <a href="{{ route('formateur.progressions.groupes') }}"
             class="btn-oneduc-outline h-10 !text-sm">
            Réinitialiser
          </a>
        @endif

        <a href="{{ route('formateur.progressions.stagiaires') }}"
           class="btn-oneduc h-10 !text-sm">
          <x-icons.stagiaire-iconify class="h-4 w-4" />
          Suivi par stagiaire
        </a>
        <a href="{{ route('formateur.progressions.modules') }}"
           class="btn-oneduc h-10 !text-sm">
          <x-icons.module-iconify class="h-4 w-4" />
          Suivi par formation
        </a>
      </div>

      @if(request('search'))
        <p class="pt-1 text-sm text-gray-600 font-varela">
          Recherche active :
          <span class="text-orangeone font-semibold">{{ request('search') }}</span>
        </p>
      @endif
    </form>

    {{-- Tableau --}}
    <div class="overflow-x-auto overflow-y-visible bg-white rounded-md border-2 border-bleuone/20">
      <table class="min-w-full bg-white text-sm text-left text-gray-800 font-lisible">
        <thead class="bg-bleuone uppercase text-xs text-white font-varela sticky top-0 z-10">
          <tr>
            <th class="px-6 py-3">#</th>
            <th class="px-6 py-3">Groupe</th>
            <th class="px-6 py-3 text-center">Stagiaires</th>
            <th class="px-6 py-3 text-center">Formations</th>
            <th class="px-6 py-3 text-center">Leçons terminées</th>
            <th class="px-6 py-3 text-center">Temps total site</th>
            <th class="px-6 py-3 text-center">Taux de réussite</th>
            <th class="px-6 py-3">Action</th>
          </tr>
        </thead>

        <tbody>
          @forelse($groupes as $index => $g)
            @php
              $taux = (int) ($g->taux_reussite ?? 0);
              $seconds = (int) ($g->total_site_time ?? 0);
              $hours = intdiv($seconds, 3600);
              $mins = intdiv($seconds % 3600, 60);
            @endphp

            <tr class="border-t {{ $index % 2 === 0 ? 'bg-white' : 'bg-orangeone/8' }} hover:bg-orangeone/15 transition-colors">
              <td class="px-6 py-4 font-medium">{{ $groupes->firstItem() + $index }}</td>

              <td class="px-6 py-4 font-medium text-gray-900">
                {{ $g->name }}
              </td>

              <td class="px-6 py-4 text-center">
                {{ (int) ($g->stagiaires_count ?? 0) }}
              </td>

              <td class="px-6 py-4 text-center">
                {{ (int) ($g->modules_count ?? 0) }}
              </td>

              <td class="px-6 py-4 text-center">
                {{ (int) ($g->lecons_terminees_count ?? 0) }}
              </td>

              <td class="px-6 py-4 text-center text-gray-700">
                @if($seconds > 0)
                  {{ $hours }} h {{ str_pad((string) $mins, 2, '0', STR_PAD_LEFT) }} min
                @else
                  —
                @endif
              </td>

              <td class="px-6 py-4 text-center">
                <span class="font-semibold {{ $taux < 50 ? 'text-red-600' : 'text-vertone' }}">
                  {{ $taux }} %
                </span>
              </td>

              <td class="px-6 py-4">
                <div class="relative inline-flex group">
                  <a href="{{ route('formateur.progressions.stagiaires', ['group_id' => $g->id]) }}"
                     class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-orangeone/20 bg-orangeone/10 text-orangeone transition hover:border-orangeone hover:bg-orangeone hover:text-white"
                     aria-label="Voir les stagiaires du groupe {{ $g->name }}">
                    <x-icons.eye-iconify class="h-4 w-4" />
                  </a>
                  <span class="pointer-events-none absolute -top-9 left-1/2 z-20 hidden -translate-x-1/2 whitespace-nowrap rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-lg group-hover:block">
                    Voir les stagiaires
                  </span>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="px-6 py-6 text-center text-gray-500">Aucun groupe trouvé.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <div class="inline-flex items-center gap-3 rounded-full border border-bleuone/20 bg-white px-4 py-2 text-sm font-varela text-gray-700">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-bleuone/10 text-bleuone">
          <x-icons.group-iconify class="h-4 w-4" />
        </span>
        <span>Nombre total de groupes :</span>
        <span class="font-bold text-bleuone">{{ $totalGroupes ?? 0 }}</span>
      </div>
    </div>

    {{-- Pagination --}}
    <div>
      {{ $groupes->links('pagination::tailwind') }}
    </div>

  </main>

</div>
@endsection
