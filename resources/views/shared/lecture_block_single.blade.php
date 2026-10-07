{{-- resources/views/shared/lecture_block_single.blade.php --}}
@php $lecture = $lecture ?? null; @endphp

@switch($block['type'] ?? null)
  @case('text')
    <div class="rich-text-content prose prose-slate max-w-none mb-6">
      {!! $block['html'] ?? '' !!}
    </div>
    @break

  @case('image')
    @php $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($block['media_id'] ?? null); @endphp
    @if($media)
      <figure class="mb-6">
        <img src="{{ $media->getUrl('display') }}"
             alt="{{ ($block['decorative'] ?? false) ? '' : ($block['alt'] ?? $block['caption'] ?? '') }}"
             class="w-full rounded-xl border border-gray-200">
        @if(!empty($block['caption']))
          <figcaption class="mt-2 text-center text-sm text-gray-500">{{ $block['caption'] }}</figcaption>
        @endif
      </figure>
    @endif
    @break

  @case('video')
    @php $videoInfo = \App\Domains\ModulesFormateur\Support\ClassifieurUrlVideo::classify($block['url'] ?? ''); @endphp
    @if($videoInfo)
      <figure class="mb-6">
        @if($videoInfo['kind'] === 'file')
          <video src="{{ $videoInfo['embed_url'] }}" controls class="w-full rounded-xl border border-gray-200"></video>
        @else
          <div class="aspect-video w-full overflow-hidden rounded-xl border border-gray-200">
            <iframe src="{{ $videoInfo['embed_url'] }}" title="{{ $block['caption'] ?? 'Vidéo de la leçon' }}" class="h-full w-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
          </div>
        @endif
        @if(!empty($block['caption']))
          <figcaption class="mt-2 text-center text-sm text-gray-500">{{ $block['caption'] }}</figcaption>
        @endif
        @if(!empty($block['transcript']))
          <details class="mt-3 rounded-[10px] border border-gray-200 p-3">
            <summary class="cursor-pointer text-sm font-semibold text-bleuone">Lire la transcription de la vidéo</summary>
            <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $block['transcript'] }}</p>
          </details>
        @endif
      </figure>
    @endif
    @break

  @case('quote')
    <blockquote class="mb-6 border-l-4 border-orangeone bg-orange-50/60 px-5 py-4 italic text-gray-700 rounded-r-xl">
      <p>&laquo;&nbsp;{{ $block['text'] ?? '' }}&nbsp;&raquo;</p>
      @if(!empty($block['source']))
        <cite class="mt-2 block not-italic text-sm font-semibold text-gray-500">&mdash; {{ $block['source'] }}</cite>
      @endif
    </blockquote>
    @break

  @case('audio')
    @php $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($block['media_id'] ?? null); @endphp
    @if($media)
      <div class="mb-6">
        <audio controls class="w-full" src="{{ $media->getUrl() }}"></audio>
        @if(!empty($block['caption']))
          <p class="mt-2 text-center text-sm text-gray-500">{{ $block['caption'] }}</p>
        @endif
        @if(!empty($block['transcript']))
          <details class="mt-3 rounded-[10px] border border-gray-200 p-3">
            <summary class="cursor-pointer text-sm font-semibold text-bleuone">Lire la transcription de l’audio</summary>
            <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $block['transcript'] }}</p>
          </details>
        @endif
      </div>
    @endif
    @break

  @case('divider')
    <hr class="my-8 border-gray-200">
    @break

  @case('scorm')
    @if($lecture && !empty($block['content_block_key']))
      <div class="mb-6 overflow-hidden rounded-xl border border-gray-200" style="height: 70vh;">
        <iframe src="{{ ($apercu ?? false)
            ? route($nomRoutesConstructeur.'.lectures.apercu-scorm', ['lecture' => $lecture, 'bloc' => $block['content_block_key']])
            : route('lecture.scorm-block', ['id' => $lecture->id, 'key' => $block['content_block_key']]) }}"
                class="h-full w-full border-0" title="Contenu SCORM" allowfullscreen></iframe>
      </div>
    @endif
    @break
@endswitch
