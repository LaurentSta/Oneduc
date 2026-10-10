{{-- resources/views/shared/lecture_block_single.blade.php --}}
@php $lecture = $lecture ?? null; @endphp

@switch($block['type'] ?? null)
  @case('text')
    <div class="rich-text-content prose prose-slate max-w-none mb-6">
      {!! $block['html'] ?? '' !!}
    </div>
    @break

  @case('image')
    @php
        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($block['media_id'] ?? null);
        $altImage = ($block['decorative'] ?? false) ? '' : ($block['alt'] ?? $block['caption'] ?? '');
        $idImage = 'image-'.uniqid();
    @endphp
    @if($media)
      {{-- Survol, focus clavier ou simple clic sur l'image : elle s'ouvre en pleine page. --}}
      <figure class="mb-6"
              x-data="{
                  pleinePage: false,
                  ouvrir() {
                      this.pleinePage = true;
                      this.$nextTick(() => document.getElementById(@js($idImage.'-fermer'))?.focus());
                  },
                  fermer() {
                      if (!this.pleinePage) return;
                      this.pleinePage = false;
                      document.getElementById(@js($idImage.'-ouvrir'))?.focus();
                  },
              }"
              x-effect="document.documentElement.classList.toggle('overflow-hidden', pleinePage)"
              @keydown.escape.window="fermer()">
        <div class="group relative">
          <img src="{{ $media->getUrl('display') }}"
               alt="{{ $altImage }}"
               @click="ouvrir()"
               class="w-full cursor-zoom-in rounded-xl border border-gray-200">
          <button type="button" id="{{ $idImage }}-ouvrir" @click="ouvrir()"
                  class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-lg bg-bleuone px-3 py-1.5 text-xs font-bold text-white opacity-0 shadow-md transition focus-visible:opacity-100 group-hover:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>
            </svg>
            Pleine page
          </button>
        </div>
        @if(!empty($block['caption']))
          <figcaption class="mt-2 text-center text-sm text-gray-500">{{ $block['caption'] }}</figcaption>
        @endif

        {{-- Sorti de la leçon (x-teleport) : un parent animé empêcherait le plein cadre. --}}
        <template x-teleport="body">
          <div x-show="pleinePage" x-cloak role="dialog" aria-modal="true" aria-label="Image en pleine page"
               class="fixed inset-0 z-[100] flex cursor-zoom-out items-center justify-center bg-black/90 p-4"
               @click="fermer()">
            <template x-if="pleinePage">
              <img src="{{ $media->getUrl() }}" alt="{{ $altImage }}" class="max-h-full max-w-full object-contain">
            </template>
            <button type="button" id="{{ $idImage }}-fermer" @click.stop="fermer()"
                    class="absolute right-4 top-4 rounded-lg bg-white px-3 py-1.5 text-sm font-bold text-bleuone shadow-md">
              Fermer
            </button>
          </div>
        </template>
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

  @case('outil')
    @if(\App\Domains\ModulesFormateur\Support\OutilsLecon::affichable($block))
      @include('shared.lecture_outil', ['block' => $block])
    @endif
    @break
@endswitch
