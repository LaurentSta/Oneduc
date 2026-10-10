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
            <li class="text-gray-400">Zone de clic</li>
          </ol>
        </nav>
        <p class="font-raleway text-2xl text-bleuone">Zone de clic</p>
        <p class="text-sm text-gray-500 mt-1">Créez vos zones de clic ici, une fois pour toutes. Lancez-les pour un groupe ou insérez-les dans vos leçons : une modification s'applique partout.</p>
      </div>
      <a href="{{ route('formateur.composants.create') }}"
         class="inline-flex items-center justify-center rounded-[10px] bg-orangeone px-5 py-2.5 text-sm font-bold text-white hover:bg-orangeone-hover transition">
        + Nouvelle zone de clic
      </a>
    </div>
  </header>

  @if(session('success'))
    <div class="mb-6 rounded-[10px] bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
      {{ session('success') }}
    </div>
  @endif

  @if($errors->any())
    <div class="mb-6 rounded-[10px] bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
      {{ $errors->first() }}
    </div>
  @endif

  <section class="mb-8">
    <h2 class="font-varela text-base font-bold text-bleuone mb-3">Mes zones de clic</h2>
    @if($activities->isEmpty())
      <div class="flex flex-col items-center justify-center rounded-[20px] border-2 border-dashed border-gray-200 bg-white py-12 text-center">
        <p class="text-sm font-semibold text-gray-700">Aucune zone de clic pour le moment</p>
        <p class="text-xs text-gray-400 mt-1">Importez une image et désignez les éléments que vos stagiaires devront retrouver.</p>
        <a href="{{ route('formateur.composants.create') }}" class="mt-4 text-sm font-semibold text-orangeone hover:underline">Créer ma première zone de clic</a>
      </div>
    @else
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach($activities as $activity)
          @php $lecons = $usages[$activity->id] ?? []; @endphp
          <article class="flex flex-col overflow-hidden rounded-[20px] bg-white shadow-md">
            <img src="{{ $activity->image_url }}" alt="" class="h-36 w-full border-b border-gray-100 object-cover">
            <div class="flex flex-1 flex-col gap-3 p-5">
              <div>
                <p class="text-sm font-bold text-gray-900 truncate">{{ $activity->title }}</p>
                <p class="text-xs text-gray-500">{{ count($activity->zones ?? []) }} composant{{ count($activity->zones ?? []) > 1 ? 's' : '' }} à trouver</p>
                @if($lecons)
                  <details class="mt-1 text-xs text-gray-500">
                    <summary class="cursor-pointer font-semibold text-bleuone">Utilisée dans {{ count($lecons) }} leçon{{ count($lecons) > 1 ? 's' : '' }}</summary>
                    <ul class="mt-1 list-disc pl-4">
                      @foreach($lecons as $usage)
                        <li>{{ $usage['lecon'] }} <span class="text-gray-400">({{ $usage['formation'] }})</span></li>
                      @endforeach
                    </ul>
                  </details>
                @else
                  <p class="mt-1 text-xs text-gray-400">Pas encore utilisée dans une leçon</p>
                @endif
              </div>

              @if($groups->isNotEmpty())
                <form method="POST" action="{{ route('formateur.composants.activities.launch', $activity) }}" class="flex gap-2">
                  @csrf
                  <select name="group_id" required aria-label="Groupe pour lequel lancer le jeu"
                          class="min-w-0 flex-1 rounded-[8px] border border-gray-300 px-2 py-1.5 text-xs focus:border-orangeone focus:outline-none focus:ring-2 focus:ring-orange-200">
                    <option value="">Lancer pour un groupe...</option>
                    @foreach($groups as $group)
                      <option value="{{ $group->id }}">{{ $group->name }}</option>
                    @endforeach
                  </select>
                  <button type="submit"
                          class="inline-flex shrink-0 items-center justify-center rounded-[8px] bg-orangeone px-3 py-1.5 text-xs font-bold text-white hover:bg-orangeone-hover transition">
                    Lancer
                  </button>
                </form>
              @endif

              <div class="mt-auto flex gap-2">
                <a href="{{ route('formateur.composants.activities.edit', $activity) }}"
                   class="inline-flex flex-1 items-center justify-center rounded-[8px] border border-bleuone px-3 py-1.5 text-xs font-semibold text-bleuone hover:bg-bleuone hover:text-white transition">
                  Modifier
                </a>
                @if($lecons)
                  <button type="button" disabled title="Retirez-la d'abord des leçons qui l'utilisent"
                          class="inline-flex cursor-not-allowed items-center justify-center rounded-[8px] border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-400">
                    Supprimer
                  </button>
                @else
                  <form method="POST" action="{{ route('formateur.composants.activities.destroy', $activity) }}"
                        onsubmit="return confirm('Supprimer cette zone de clic ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-[8px] border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-red-50 hover:border-red-300 hover:text-red-600 transition">
                      Supprimer
                    </button>
                  </form>
                @endif
              </div>
            </div>
          </article>
        @endforeach
      </div>
    @endif
  </section>

  @if($sessions->isNotEmpty())
    <section class="mb-8">
      <h2 class="font-varela text-base font-bold text-bleuone mb-3">Jeux lancés</h2>
      <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
        @foreach($sessions as $session)
          <div class="bg-white rounded-[20px] shadow-md p-5 flex flex-col gap-3">
            <div class="flex items-start gap-3 min-w-0">
              <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $session->is_active ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-500' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-0.5">
                  <p class="text-sm font-bold text-gray-900 truncate">{{ $session->title }}</p>
                  <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold {{ $session->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                    {{ $session->is_active ? 'Ouvert' : 'Fermé' }}
                  </span>
                </div>
                <p class="text-xs text-gray-500 truncate">{{ count($session->zones ?? []) }} composant{{ count($session->zones ?? []) > 1 ? 's' : '' }} à trouver</p>
                <p class="text-[10px] text-gray-400 mt-1">
                  Groupe : <span class="font-semibold">{{ $session->group?->name ?? '—' }}</span>
                  - Code : <span class="font-mono font-semibold text-orangeone">{{ $session->access_code }}</span>
                  - {{ $session->attempts_count }} participation{{ $session->attempts_count > 1 ? 's' : '' }}
                </p>
              </div>
            </div>
            <div class="flex gap-2 shrink-0">
              <a href="{{ route('formateur.composants.show', $session) }}"
                 class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-[8px] bg-orangeone px-3 py-1.5 text-xs font-bold text-white hover:bg-orangeone-hover transition">
                Ouvrir
              </a>
              <form method="POST" action="{{ route('formateur.composants.destroy', $session) }}">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center justify-center rounded-[8px] border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-red-50 hover:border-red-300 hover:text-red-600 transition">
                  Supprimer
                </button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    </section>
  @endif
</div>
@endsection
