@extends('formateur.dashboard')

@section('formateur')

{{-- Wrapper unique pour en-tête + contenu --}}
<div class="max-w-[1285px] mx-auto px-8">

  <x-formateur.page-header
    :breadcrumb="[['label' => 'Accueil', 'url' => route('formateur.dashboard')], ['label' => 'Mes groupes']]"
    title="Mes groupes de formation"
    subtitle="Gérez facilement vos groupes, formations et stagiaires."
    :image="asset('images/svg/Groupes.svg')"
    imageAlt="Illustration des groupes de formation"
  >
    <x-slot:badges>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-bleuone/15 bg-bleuone/5 px-3 py-1 text-bleuone">
        {{ $groupes->count() }} groupes
      </span>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-vertone/20 bg-vertone/10 px-3 py-1 text-vertone">
        {{ $groupes->where('is_active', true)->count() }} actifs
      </span>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-orangeone/20 bg-orangeone/10 px-3 py-1 text-orangeone">
        {{ $groupes->sum(fn ($groupe) => $groupe->students->count()) }} stagiaires
      </span>
    </x-slot:badges>
  </x-formateur.page-header>


  {{-- 💼 CONTENU PRINCIPAL --}}
  <main class="space-y-8">

    {{-- Alertes --}}
    @if (session('success'))
      <div class="bg-green-100 text-green-800 px-4 py-3 rounded font-lisible">
        {{ session('success') }}
      </div>
    @endif

    {{-- Grille: carte "Ajouter" + cartes groupes --}}
    <section aria-labelledby="groupes-title">
      <h2 id="groupes-title" class="sr-only">Liste des groupes</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        {{-- ➕ Carte "Ajouter un groupe" --}}
        <a href="{{ route('formateur.groupes.create') }}"
           class="flex flex-col items-center justify-center border-4 border-dashed border-orangeone rounded-[20px] p-10 min-h-[180px] text-orangeone hover:bg-orangeone hover:text-white transition font-varela text-lg font-semibold">
          Ajouter un groupe
        </a>

        {{-- 📋 Cartes groupes --}}
        @forelse ($groupes as $groupe)
          @php
            $isPrimaryTrainer = (int) $groupe->instructor_id === (int) auth()->id();
          @endphp
          <article class="flex flex-col rounded-[20px] border border-gray-200 bg-white p-6 shadow">
            <div class="flex-1 space-y-5">
              <div class="border-b border-gray-100 pb-4">
                <h3 class="break-words font-raleway text-xl font-bold text-bleuone">
                  {{ $groupe->name }}
                </h3>
                <p class="mt-2 text-xs italic text-gray-400 font-lisible">
                  Créé le {{ optional($groupe->created_at)->format('d/m/Y') ?? '—' }}
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                  <span class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-bold {{ $groupe->is_active ? 'border-vertone/20 bg-vertone/10 text-vertone' : 'border-gray-200 bg-gray-100 text-gray-600' }}">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $groupe->is_active ? 'bg-vertone' : 'bg-gray-400' }}"></span>
                    {{ $groupe->is_active ? 'Actif' : 'Inactif' }}
                  </span>

                  @unless($isPrimaryTrainer)
                    <span class="inline-flex items-center rounded-full border border-orangeone/20 bg-orangeone/10 px-3 py-1 text-xs font-bold text-orangeone">
                      Co-formateur
                    </span>
                  @endunless

                  @if($groupe->coFormateurs->isNotEmpty())
                    <div class="relative inline-flex group">
                      <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 cursor-default">
                        {{ $groupe->coFormateurs->count() }} co-formateur{{ $groupe->coFormateurs->count() > 1 ? 's' : '' }}
                      </span>
                      <div class="pointer-events-none absolute left-0 top-full z-20 mt-2 hidden w-max max-w-xs rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-900 shadow-lg group-hover:block group-focus-within:block">
                        @foreach($groupe->coFormateurs as $trainer)
                          <div>{{ $trainer->email }}</div>
                        @endforeach
                      </div>
                    </div>
                  @endif

                  @if($groupe->observers->isNotEmpty())
                    <div class="relative inline-flex group">
                      <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 cursor-default">
                        Groupe suivi
                      </span>
                      <div class="pointer-events-none absolute left-0 top-full z-20 mt-2 hidden w-max max-w-xs rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs text-blue-900 shadow-lg group-hover:block group-focus-within:block">
                        @foreach($groupe->observers as $observer)
                          <div>{{ trim(($observer->prenom ?? '').' '.($observer->name ?? '')) }}</div>
                        @endforeach
                      </div>
                    </div>
                  @endif
                </div>
              </div>

              @if ($groupe->description)
                <p class="text-sm leading-7 text-gray-700 font-lisible line-clamp-2">
                  {{ $groupe->description }}
                </p>
              @endif

              @if ($groupe->start_date || $groupe->end_date)
                <div class="grid gap-3 sm:grid-cols-2">
                  @if ($groupe->start_date)
                    <div class="flex items-center gap-3 rounded-2xl border border-bleuone/15 bg-slate-50 px-4 py-3">
                      <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-bleuone/10 text-bleuone">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z" />
                        </svg>
                      </span>
                      <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Début</p>
                        <p class="text-sm font-bold text-bleuone">{{ $groupe->start_date->format('d/m/Y') }}</p>
                      </div>
                    </div>
                  @endif

                  @if ($groupe->end_date)
                    <div class="flex items-center gap-3 rounded-2xl border border-orangeone/15 bg-orange-50/60 px-4 py-3">
                      <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-orangeone/10 text-orangeone">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-11 9h12a2 2 0 002-2V7a2 2 0 00-2-2H6a2 2 0 00-2 2v11a2 2 0 002 2z" />
                        </svg>
                      </span>
                      <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Fin</p>
                        <p class="text-sm font-bold text-orangeone">{{ $groupe->end_date->format('d/m/Y') }}</p>
                      </div>
                    </div>
                  @endif
                </div>
              @endif

              <div class="rounded-2xl bg-gray-50/80 p-4" x-data="{ open: false }">
                <div class="mb-2 flex items-center justify-between gap-2">
                  <h4 class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-500 font-varela">Formations associées</h4>
                  @if($groupe->modules->count() > 3)
                    <button type="button"
                            x-on:click="open = !open"
                            :aria-expanded="open"
                            aria-label="Afficher toutes les formations"
                            class="shrink-0 rounded-full p-1 text-gray-400 transition hover:bg-white hover:text-orangeone">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                      </svg>
                    </button>
                  @endif
                </div>

                <div class="flex flex-wrap gap-2">
                  @forelse ($groupe->modules->take(3) as $module)
                    @php
                      $moduleUrl = !empty($module->category_id)
                        ? route('frontend.modules.show', ['category' => $module->category_id, 'module' => $module->id])
                        : route('frontend.modules.show.legacy', ['module' => $module->id]);
                    @endphp
                    <a href="{{ $moduleUrl }}"
                       class="inline-flex items-center rounded-full bg-vertone/10 px-3 py-1 text-xs font-varela text-vertone transition hover:bg-vertone/20">
                      {{ Str::limit($module->module_title, 30) }}
                    </a>
                  @empty
                    <p class="text-sm italic text-gray-400 font-lisible">Aucune formation</p>
                  @endforelse
                </div>

                @if($groupe->modules->count() > 3)
                  <div x-show="open" x-collapse.duration.300ms class="mt-2 flex flex-wrap gap-2">
                    @foreach ($groupe->modules->slice(3) as $module)
                      @php
                        $moduleUrl = !empty($module->category_id)
                          ? route('frontend.modules.show', ['category' => $module->category_id, 'module' => $module->id])
                          : route('frontend.modules.show.legacy', ['module' => $module->id]);
                      @endphp
                      <a href="{{ $moduleUrl }}"
                         class="inline-flex items-center rounded-full bg-vertone/10 px-3 py-1 text-xs font-varela text-vertone transition hover:bg-vertone/20">
                        {{ Str::limit($module->module_title, 30) }}
                      </a>
                    @endforeach
                  </div>
                @endif
              </div>

              <div class="flex items-center justify-between gap-3 rounded-2xl bg-gray-50/80 p-4">
                <h4 class="text-[11px] font-bold uppercase tracking-[0.16em] text-gray-500 font-varela">Stagiaires</h4>
                <a href="{{ route('formateur.stagiaires.index') }}"
                   class="shrink-0 text-sm font-semibold text-orangeone hover:underline font-lisible">
                  {{ $groupe->students->count() }} stagiaire{{ $groupe->students->count() > 1 ? 's' : '' }}
                </a>
              </div>
            </div>

            <div class="flex gap-2 mt-6">
              <a href="{{ route('formateur.groupes.edit', $groupe->id) }}" class="btn-oneduc {{ $isPrimaryTrainer ? 'w-1/2' : 'w-full' }} text-center">
                Modifier
              </a>
              @if($isPrimaryTrainer)
                <button type="button" x-data x-on:click="$dispatch('open-modal', 'delete-groupe-{{ $groupe->id }}')" class="btn-oneduc-blue w-1/2">
                  Supprimer
                </button>
                <x-confirm-modal
                  name="delete-groupe-{{ $groupe->id }}"
                  title="Êtes-vous sûr de vouloir supprimer ce groupe ?"
                  :action="route('formateur.groupes.destroy', $groupe->id)"
                  method="DELETE"
                  confirm-label="Supprimer"
                />
              @endif
            </div>

          </article>
        @empty
          <p class="text-gray-500 col-span-full font-lisible">Aucun groupe n’a encore été créé.</p>
        @endforelse
      </div>
    </section>

  </main>
</div>
@endsection
