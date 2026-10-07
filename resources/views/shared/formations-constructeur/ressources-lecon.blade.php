@php
  $parametresRessources = ['module' => $module, 'section' => $section, 'lecture' => $lecture];
  $urlAjoutRessource = $constructeurAdmin
      ? route($nomRoutesConstructeur.'.lectures.ressources.store', $lecture)
      : route('formateur.formations.lesson.resources.store', $parametresRessources);
@endphp
<details class="mt-6 rounded-[20px] bg-white p-5 shadow-md">
  <summary class="cursor-pointer font-varela text-lg font-bold text-bleuone">Ressources de la formation ({{ $ressources->count() }})</summary>
  <p class="mt-3 text-sm text-gray-600">Fiches mémo et documents complémentaires, disponibles dans les leçons de cette formation.</p>
  <ul class="mt-4 space-y-3">
    @foreach($ressources as $ressource)
      @php
        $parametresRessource = [...$parametresRessources, 'resource' => $ressource];
        $urlVisibilite = $constructeurAdmin
            ? route($nomRoutesConstructeur.'.lectures.ressources.visibilite', ['lecture' => $lecture, 'resource' => $ressource])
            : route('formateur.formations.lesson.resources.visibility', $parametresRessource);
        $urlSuppression = $constructeurAdmin
            ? route($nomRoutesConstructeur.'.lectures.ressources.destroy', ['lecture' => $lecture, 'resource' => $ressource])
            : route('formateur.formations.lesson.resources.destroy', $parametresRessource);
      @endphp
      <li class="flex flex-wrap items-center justify-between gap-3 rounded-[12px] border border-gray-200 p-3">
        <div>
          <a href="{{ $ressource->public_url }}" target="_blank" rel="noopener" class="text-sm font-semibold text-bleuone hover:underline">{{ $ressource->title }}</a>
          <p class="mt-1 text-xs text-gray-500">{{ $ressource->is_visible_to_stagiaire ? 'Visible pour les stagiaires' : 'Réservée au formateur' }}</p>
        </div>
        @unless($lectureSeule)
          <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ $urlVisibilite }}">
              @csrf
              @if($constructeurAdmin) @method('PUT') @endif
              <input type="hidden" name="is_visible_to_stagiaire" value="{{ $ressource->is_visible_to_stagiaire ? '0' : '1' }}">
              <button type="submit" class="text-xs font-semibold text-bleuone">{{ $ressource->is_visible_to_stagiaire ? 'Masquer aux stagiaires' : 'Afficher aux stagiaires' }}</button>
            </form>
            <button type="button" x-data @click="$dispatch('open-modal', 'ressource-conception-{{ $ressource->id }}')" class="text-xs font-semibold text-red-700">Supprimer</button>
            <x-confirm-modal name="ressource-conception-{{ $ressource->id }}" title="Supprimer cette ressource ?" :action="$urlSuppression" method="DELETE" confirm-label="Supprimer" />
          </div>
        @endunless
      </li>
    @endforeach
  </ul>
  @unless($lectureSeule)
    <form method="POST" action="{{ $urlAjoutRessource }}" enctype="multipart/form-data" class="mt-5 space-y-3 border-t border-gray-200 pt-4">
      @csrf
      <label for="titre-ressource" class="block text-sm font-semibold text-bleuone">Ajouter une ressource</label>
      <input id="titre-ressource" name="title" maxlength="255" placeholder="Titre du document (facultatif)" class="w-full rounded-[10px] border-gray-300 text-sm">
      <label for="fichier-ressource" class="block text-sm">Document (50 Mo maximum)</label>
      <input id="fichier-ressource" type="file" name="resource_file" required accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.pdf,.doc,.docx,.odt,.txt,.rtf,.xls,.xlsx,.ods,.ppt,.pptx,.odp,.csv" class="block w-full text-sm">
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_visible_to_stagiaire" value="1" checked class="rounded border-gray-300 text-orangeone"> Visible pour les stagiaires</label>
      <button type="submit" class="btn-oneduc-outline !px-4 !py-2 !text-sm">Ajouter le document</button>
    </form>
  @endunless
</details>
