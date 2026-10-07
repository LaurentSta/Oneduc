@extends('admin.admin_dashboard')
@section('admin')

<div class="w-full px-6 lg:px-8">
  <div class="bg-white rounded-[20px] shadow-soft p-6 my-6 w-full border border-gray-100">
    <div class="border-b border-gray-100 pb-4 mb-4">
      <h1 class="text-[20px] font-varela text-bleuone">Outils numériques</h1>
      <p class="text-sm text-gray-600">Activez ou désactivez chaque outil pour les formateurs et les stagiaires. L'effet est immédiat, sans redémarrage.</p>
    </div>

    @if(session('success'))
      <p role="status" class="mb-4 rounded-[12px] border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</p>
    @endif

    <div class="overflow-x-auto">
      <table class="min-w-full text-left text-sm text-gray-800">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500">
          <tr>
            <th class="px-4 py-3">Outil</th>
            <th class="w-[160px] px-4 py-3">Statut</th>
            <th class="w-[120px] px-4 py-3">Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($outils as $outil)
            <tr class="border-t border-gray-100">
              <td class="px-4 py-3 font-medium">{{ $outil['libelle'] }}</td>
              <td class="px-4 py-3">
                @if($outil['actif'])
                  <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-600" aria-hidden="true"></span>
                    Activé
                  </span>
                @else
                  <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                    <span class="h-1.5 w-1.5 rounded-full bg-gray-400" aria-hidden="true"></span>
                    Désactivé
                  </span>
                @endif
              </td>
              <td class="px-4 py-3">
                <form method="POST" action="{{ route('admin.outils.toggle', $outil['cle']) }}">
                  @csrf
                  <button type="submit"
                          aria-label="{{ $outil['actif'] ? 'Désactiver' : 'Activer' }} {{ $outil['libelle'] }}"
                          class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors {{ $outil['actif'] ? 'bg-green-500' : 'bg-gray-300' }}">
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform {{ $outil['actif'] ? 'translate-x-[22px]' : 'translate-x-0.5' }}"></span>
                  </button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
