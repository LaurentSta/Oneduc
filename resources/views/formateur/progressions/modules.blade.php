@extends('formateur.dashboard')

@section('formateur')

<div class="max-w-[1285px] mx-auto px-8">

  <x-formateur.page-header
    :breadcrumb="[['label' => 'Accueil', 'url' => route('formateur.dashboard')], ['label' => 'Suivi par formation']]"
    title="Suivi par formation"
    subtitle="Analyse des formations utilisées dans vos groupes"
    :image="asset('images/svg/Progressions.svg')"
    imageAlt="Illustration suivi par formation"
  >
    <x-slot:badges>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-bleuone/15 bg-bleuone/5 px-3 py-1 text-bleuone">
        {{ $modules->count() }} formations
      </span>
    </x-slot:badges>
  </x-formateur.page-header>

  {{-- ACTIONS --}}
  <div class="flex flex-wrap justify-end gap-3 mb-6">
    <a href="{{ route('formateur.progressions.groupes') }}"
       class="btn-oneduc h-10 !text-sm">
      <x-icons.eye-iconify class="h-4 w-4" />
      Suivi par groupe
    </a>

    <a href="{{ route('formateur.progressions.stagiaires') }}"
       class="btn-oneduc h-10 !text-sm">
      <x-icons.eye-iconify class="h-4 w-4" />
      Suivi par stagiaire
    </a>
  </div>


  {{-- LISTE DES MODULES (CARTES) --}}
  <main class="space-y-6">
    
    @forelse($modules as $m)
        <div class="bg-white rounded-[20px] shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow">
            <div class="p-6">
                <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                    
                    {{-- 1. Info Module --}}
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-2 py-1 bg-blue-50 text-bleuone text-xs font-bold rounded uppercase tracking-wider">Formation</span>
                            <h3 class="text-xl font-bold text-gray-900">{{ $m->module_title }}</h3>
                        </div>
                        <div class="flex items-center gap-6 text-sm text-gray-500 mt-4">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                <span><strong>{{ $m->stagiaires_count }}</strong> stagiaires inscrits</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span><strong>{{ $m->start_rate }}%</strong> ont démarré</span>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Score Moyen --}}
                    <div class="text-center px-6 border-l border-gray-100">
                        <span class="block text-sm text-gray-400 font-varela mb-1">Score Moyen</span>
                        <span class="text-3xl font-black {{ $m->avg_score < 50 ? 'text-red-500' : 'text-green-500' }}">
                            {{ $m->avg_score }}%
                        </span>
                    </div>

                    {{-- 3. Action --}}
                    <div>
                        <a href="{{ route('formateur.formations.detail', $m->id) }}" class="btn-oneduc">
                            <x-icons.eye-iconify class="h-4 w-4" />
                            Voir le détail
                        </a>
                    </div>
                </div>

                {{-- 4. ZONE D'ALERTE : TOP 3 ERREURS --}}
                @if(count($m->top_failed) > 0)
                    <div class="mt-6 pt-6 border-t border-dashed border-gray-200">
                        <h4 class="text-sm font-bold text-red-500 uppercase tracking-wide mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            Difficultés rencontrées (Top 3 erreurs)
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($m->top_failed as $q)
                                <div class="bg-red-50 rounded-lg p-3 border border-red-100">
                                    <div class="flex justify-between items-start mb-2">
                                        <span class="text-xs font-bold text-red-800 bg-red-200 px-2 py-0.5 rounded">
                                            {{ $q->fail_rate }}% d'échec
                                        </span>
                                        <span class="text-[10px] text-red-400 font-bold uppercase">{{ $q->failures }} erreurs</span>
                                    </div>
                                    <p class="text-sm text-gray-800 font-medium line-clamp-2" title="{{ $q->question_text }}">
                                        {{ $q->question_text }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-6 pt-6 border-t border-dashed border-gray-200">
                        <p class="text-sm text-gray-400 italic flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Aucune difficulté majeure détectée sur les quiz de cette formation.
                        </p>
                    </div>
                @endif

            </div>
        </div>
    @empty
        <div class="text-center py-12 bg-white rounded-2xl border border-dashed border-gray-300">
            <p class="text-gray-500">Aucune formation associée à vos groupes pour le moment.</p>
        </div>
    @endforelse

    <div class="flex flex-wrap items-center gap-2">
        <div class="inline-flex items-center gap-2 rounded-full border border-bleuone/20 bg-white px-4 py-2 text-sm font-varela text-gray-700">
            <span>Nombre total de formations :</span>
            <span class="font-bold text-bleuone">{{ $modules->count() }}</span>
        </div>
    </div>

  </main>
</div>
@endsection
