{{-- resources/views/shared/lecture_segments.blade.php --}}
@php
    $segments = $segments ?? [];
    $lecture = $lecture ?? null;

    // Une activité « outil » obligatoire retient le « Continuer » de son segment, comme une question.
    $outilsRestants = array_map(
        fn ($segment) => \App\Domains\ModulesFormateur\Support\OutilsLecon::nombreObligatoires($segment['blocks'] ?? []),
        $segments
    );
@endphp

@if(count($segments) > 1)
    <div x-data="{
            revealed: @js(array_merge([true], array_fill(0, count($segments) - 1, false))),
            answered: @js(array_map(fn ($segment, $restants) => ($segment['kind'] ?? 'content') !== 'quiz' && $restants === 0, $segments, $outilsRestants)),
            outilsRestants: @js($outilsRestants),
         }">
        @foreach($segments as $segIndex => $segment)
            {{-- The background bleeds full-width of the scroll pane; the text inside
                 stays constrained to the usual reading column. --}}
            <div x-show="revealed[{{ $segIndex }}]"
                 @outil-termine="outilsRestants[{{ $segIndex }}]--; if (outilsRestants[{{ $segIndex }}] <= 0) answered[{{ $segIndex }}] = true"
                 @if($segIndex > 0)
                     x-cloak
                     class="transform-gpu will-change-transform bg-gray-50"
                     x-transition:enter="transition-all ease-in-out duration-1000"
                     x-transition:enter-start="opacity-0 -translate-y-10"
                     x-transition:enter-end="opacity-100 translate-y-0"
                 @endif
            >
                <div class="max-w-3xl mx-auto px-6 @if($segIndex > 0) py-6 @endif">
                    @if(($segment['kind'] ?? 'content') === 'quiz')
                        @include('shared.lecture_quiz_activity', ['question' => $segment['question'], 'segIndex' => $segIndex])
                    @else
                        @foreach($segment['blocks'] ?? [] as $block)
                            @include('shared.lecture_block_single', ['block' => $block, 'lecture' => $lecture])
                        @endforeach
                    @endif
                </div>
            </div>
            @if($segIndex + 1 < count($segments))
                <div class="max-w-3xl mx-auto px-6 mb-6"
                     x-show="revealed[{{ $segIndex }}] && !revealed[{{ $segIndex + 1 }}] && answered[{{ $segIndex }}]">
                    <button type="button"
                            @click="revealed[{{ $segIndex + 1 }}] = true; $store.lectureProgress.revealedCount++; $nextTick(() => window.oneducSmoothScrollBy($el.closest('.overflow-y-auto'), 320, 900))"
                            class="btn-oneduc-blue w-full !py-3">
                        Continuer
                        <i class="ti ti-arrow-right"></i>
                    </button>
                </div>
            @endif
        @endforeach
    </div>
@else
    <div class="max-w-3xl mx-auto px-6">
        @foreach(($segments[0]['blocks'] ?? []) as $block)
            @include('shared.lecture_block_single', ['block' => $block, 'lecture' => $lecture])
        @endforeach
    </div>
@endif
