@php($supportImporte = in_array($lecture->content_type, ['scorm', 'slides'], true))
<div class="rounded-[16px] border border-gray-200 bg-gray-50 p-5">
  @if($supportImporte)
    <h2 class="font-varela text-lg font-bold text-bleuone">{{ $lecture->content_type === 'slides' ? 'Présentation importée' : 'Contenu SCORM importé' }}</h2>
    <p class="mt-2 text-sm leading-relaxed text-gray-600">Le support conserve son format. Vous pouvez renseigner ses objectifs, ajouter des ressources et régler sa validation dans cet atelier.</p>
    @if($lecture->content_type === 'slides')
      <p class="mt-3 text-sm font-semibold text-bleuone" role="status">
        {{ ['pending' => 'Conversion en attente', 'processing' => 'Conversion en cours', 'ready' => 'Présentation prête', 'failed' => 'La conversion a échoué'][$lecture->slides_status] ?? 'Aucune présentation importée' }}
        @if($lecture->slides_status === 'ready') — {{ $lecture->slide_count }} diapositives @endif
      </p>
      @if(in_array($lecture->slides_status, ['pending', 'processing'], true))
        <a href="{{ route($nomRoutesConstructeur.'.lectures.edit', $lecture) }}" class="mt-2 inline-block text-sm font-semibold text-orangeone hover:underline">Actualiser l’état de conversion</a>
      @endif
      @if(!$lectureSeule && $lecture->slides_status === 'failed' && $lecture->slides_source_path)
        <form method="POST" action="{{ route($nomRoutesConstructeur.'.lectures.support.relancer', $lecture) }}" class="mt-3">
          @csrf
          <button type="submit" class="btn-oneduc-outline !px-4 !py-2 !text-sm">Relancer la conversion</button>
        </form>
      @endif
    @endif
  @else
    <h2 class="font-varela text-base font-bold text-bleuone">Partir d’un support existant</h2>
    <p class="mt-2 text-sm text-gray-600">Vous pouvez aussi commencer cette leçon vide avec une présentation ou un contenu SCORM.</p>
  @endif
  @unless($lectureSeule)
    <form method="POST" action="{{ route($nomRoutesConstructeur.'.lectures.support.store', $lecture) }}" enctype="multipart/form-data"
          class="mt-4 space-y-3" x-data="{ type: '{{ $lecture->content_type === 'scorm' ? 'scorm' : 'slides' }}' }">
      @csrf
      <label for="type-support" class="block text-sm font-semibold text-bleuone">{{ $supportImporte ? 'Remplacer le support' : 'Importer un support' }}</label>
      <select id="type-support" name="support_type" x-model="type" class="w-full rounded-[10px] border-gray-300 text-sm">
        <option value="slides">Présentation PowerPoint ou PDF</option>
        <option value="scorm">Contenu interactif SCORM</option>
      </select>
      <label for="fichier-support" class="block text-sm" x-text="type === 'scorm' ? 'Fichier ZIP (500 Mo maximum)' : 'Fichier PPT, PPTX ou PDF (50 Mo maximum)'"></label>
      <input id="fichier-support" name="support_file" type="file" required :accept="type === 'scorm' ? '.zip' : '.ppt,.pptx,.pdf'" class="block w-full text-sm">
      <button type="submit" class="btn-oneduc-outline !px-4 !py-2 !text-sm">{{ $supportImporte ? 'Importer le nouveau support' : 'Importer' }}</button>
    </form>
  @endunless
</div>
