@extends('formateur.dashboard')

@section('formateur')
<div class="w-full px-6 lg:px-8">

  <header class="bg-white rounded-[20px] shadow-md px-8 pt-5 pb-6 my-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <nav class="text-sm font-varela text-gray-500 mb-2">
          <ol class="inline-flex items-center space-x-1">
            <li><a href="{{ route('formateur.outils.index') }}" class="text-orangeone hover:underline">Outils numériques</a></li>
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li><a href="{{ route('formateur.composants.index') }}" class="text-orangeone hover:underline">Zone de clic</a></li>
            <li><span class="mx-2 text-gray-400">/</span></li>
            <li class="text-gray-400">{{ $session->title }}</li>
          </ol>
        </nav>
        <p class="font-raleway text-2xl text-bleuone">{{ $session->title }}</p>
        <p class="text-sm text-gray-500 mt-1">
          Groupe : <span class="font-semibold">{{ $session->group?->name ?? '—' }}</span>
          - Code : <span class="font-mono font-semibold text-orangeone">{{ $session->access_code }}</span>
        </p>
      </div>

      <div class="flex flex-wrap gap-2">
        <a href="{{ $joinUrl }}" target="_blank"
           class="inline-flex items-center rounded-[10px] border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
          Ouvrir la vue stagiaire
        </a>

        <form method="POST" action="{{ route('formateur.composants.toggle', $session) }}">
          @csrf
          <button type="submit"
                  class="inline-flex items-center rounded-[10px] px-4 py-2 text-sm font-bold text-white transition {{ $session->is_active ? 'bg-red-500 hover:bg-red-600' : 'bg-green-600 hover:bg-green-700' }}">
            {{ $session->is_active ? 'Fermer le jeu' : 'Rouvrir le jeu' }}
          </button>
        </form>

        <form method="POST" action="{{ route('formateur.composants.destroy', $session) }}">
          @csrf
          @method('DELETE')
          <button type="submit"
                  class="inline-flex items-center rounded-[10px] border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-red-50 hover:border-red-300 hover:text-red-600 transition">
            Supprimer
          </button>
        </form>
      </div>
    </div>
  </header>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <section class="bg-white rounded-[20px] shadow-md p-6">
      <img src="{{ $session->image_url }}" alt="{{ $session->title }}" class="w-full h-auto rounded-[12px] mb-4">
      <h2 class="text-base font-bold text-bleuone mb-3">Réussite par composant</h2>
      <div class="space-y-3">
        @forelse($zoneStats as $stat)
          <div class="rounded-[12px] border border-gray-200 bg-gray-50 p-3">
            <div class="flex items-center justify-between gap-3 mb-2">
              <p class="text-sm font-semibold text-gray-700">{{ $stat['label'] }}</p>
              <p class="text-xs text-gray-500">{{ $stat['correct'] }}/{{ $stat['total'] }} - {{ $stat['percent'] }}%</p>
            </div>
            @if($stat['description'] !== '')
              <p class="mb-2 text-xs text-gray-500 whitespace-pre-line">{{ $stat['description'] }}</p>
            @endif
            <div class="h-2 rounded-full bg-gray-200 overflow-hidden">
              <div class="h-full bg-orangeone" style="width: {{ $stat['percent'] }}%"></div>
            </div>
          </div>
        @empty
          <p class="text-sm text-gray-400">Aucune participation pour le moment.</p>
        @endforelse
      </div>
    </section>

    <section class="bg-white rounded-[20px] shadow-md p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-bold text-bleuone">Classement des stagiaires</h2>
        <a href="{{ route('formateur.composants.show', $session) }}"
           class="text-xs font-semibold text-orangeone hover:underline">Actualiser</a>
      </div>

      @if($leaderboard->isEmpty())
        <p class="text-sm text-gray-400">Aucun stagiaire n'a encore joué.</p>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-left text-[11px] uppercase tracking-wider text-gray-400">
                <th class="py-2 pr-2">#</th>
                <th class="py-2 pr-2">Stagiaire</th>
                <th class="py-2 pr-2">Score</th>
                <th class="py-2 pr-2">Durée</th>
                <th class="py-2">Terminé</th>
              </tr>
            </thead>
            <tbody>
              @foreach($leaderboard as $i => $attempt)
                <tr class="border-t border-gray-100">
                  <td class="py-2 pr-2 font-bold text-gray-500">{{ $i + 1 }}</td>
                  <td class="py-2 pr-2 font-semibold text-gray-800">{{ $attempt->user?->name ?? '—' }}</td>
                  <td class="py-2 pr-2">
                    <span class="font-bold {{ $attempt->score === $attempt->total ? 'text-vertone' : 'text-gray-700' }}">
                      {{ $attempt->score }}/{{ $attempt->total }}
                    </span>
                  </td>
                  <td class="py-2 pr-2 text-gray-500">{{ $attempt->duration_seconds }}s</td>
                  <td class="py-2 text-gray-400 text-xs">{{ $attempt->updated_at->diffForHumans() }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </section>
  </div>
</div>
@endsection
