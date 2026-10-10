{{-- resources/views/shared/lecture_outils/_composants_plateau.blade.php --}}
{{-- Plateau de la zone de clic : la question, l'image cliquable et le retour.
     $pleinePage distingue l'exemplaire agrandi de celui de la leçon. --}}
<div x-show="!fini">
    <div class="mb-3 flex items-center justify-between gap-3">
        <p class="text-base font-semibold text-gray-900">
            Trouvez : <span class="text-orangeone" x-text="zone ? zone.label : ''"></span>
        </p>
        <div class="flex shrink-0 items-center gap-3">
            <p class="text-sm text-gray-500">
                <span x-text="Math.min(position + 1, zones.length)">1</span> / {{ count($elements) }}
            </p>
            <x-help-tooltip :message="$aide" align="right" @click="open = true" />
            @if($pleinePage)
                <button type="button" id="{{ $idActivite }}-reduire" @click="reduire()"
                        class="rounded-lg border border-bleuone px-3 py-1.5 text-xs font-bold text-bleuone transition hover:bg-bleuone hover:text-white">
                    Réduire
                </button>
            @else
                <button type="button" id="{{ $idActivite }}-agrandir" @click="agrandir()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-bleuone px-3 py-1.5 text-xs font-bold text-bleuone transition hover:bg-bleuone hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/>
                    </svg>
                    Agrandir
                </button>
            @endif
        </div>
    </div>

    {{-- En pleine page, le cadre épouse l'image (les zones sont en pourcentage du cadre). --}}
    <div class="{{ $pleinePage ? 'text-center' : '' }}">
        <div @click="cliquer($event)"
             class="relative select-none overflow-hidden rounded-xl border border-gray-200 {{ $pleinePage ? 'inline-block max-w-full align-top' : '' }}"
             :class="resultat ? '' : 'cursor-crosshair'">
            <img src="{{ \App\Domains\ModulesFormateur\Support\OutilsLecon::urlImage($configuration['image']) }}"
                 alt="Image dans laquelle retrouver les éléments demandés"
                 draggable="false"
                 class="pointer-events-none block {{ $pleinePage ? 'h-auto max-h-[calc(100vh-13rem)] w-auto max-w-full' : 'h-auto w-full' }}">

            <template x-if="resultat && zone">
                <div class="pointer-events-none absolute"
                     :style="`left:${zone.x}%;top:${zone.y}%;width:${zone.w}%;height:${zone.h}%;`">
                    <template x-if="zone.shape === 'triangle'">
                        <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-0 h-full w-full overflow-visible">
                            <polygon points="50,0 0,100 100,100" fill="rgba(1,198,156,0.2)" stroke="#01c69c" stroke-width="2" vector-effect="non-scaling-stroke" />
                        </svg>
                    </template>
                    <template x-if="zone.shape !== 'triangle'">
                        <div class="absolute inset-0 border-2 border-vertone bg-vertone/20"
                             :class="(zone.shape === 'circle' || zone.shape === 'oval') ? 'rounded-full' : ''"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Sans souris ni écran tactile, ou sans idée : la réponse reste accessible. --}}
    <button type="button" x-show="!resultat" @click="repondre(false)"
            class="mt-2 text-sm font-semibold text-bleuone underline">
        Je ne sais pas
    </button>

    <div x-cloak x-show="resultat" role="status" class="mt-3 rounded-xl border px-4 py-3"
         :class="resultat && resultat.trouve ? 'border-green-200 bg-green-50' : 'border-orange-200 bg-orange-50'">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-semibold"
               :class="resultat && resultat.trouve ? 'text-green-800' : 'text-orange-800'"
               x-text="resultat && resultat.trouve ? 'Bien vu !' : 'La bonne zone est entourée.'"></p>
            <button type="button" @click="suivant()"
                    class="shrink-0 rounded-lg bg-bleuone px-4 py-2 text-xs font-bold text-white transition hover:bg-bleuone-light"
                    x-text="position + 1 < zones.length ? 'Suivant' : 'Terminer'"></button>
        </div>
        <template x-if="resultat && zone && zone.description">
            <div class="mt-3 border-t border-gray-200 pt-3">
                <p class="text-sm font-bold text-gray-900" x-text="zone.label"></p>
                <p class="mt-1 whitespace-pre-line text-sm text-gray-700" x-text="zone.description"></p>
            </div>
        </template>
    </div>
</div>

<div x-cloak x-show="fini" class="flex flex-wrap items-center justify-between gap-3">
    <p role="status" class="text-sm font-semibold text-bleuone"
       x-text="bonnes + (bonnes > 1 ? ' éléments trouvés' : ' élément trouvé') + ' sur ' + zones.length"></p>
    <div class="flex items-center gap-4">
        <button type="button" @click="recommencer()" class="text-sm font-semibold text-bleuone underline">Recommencer</button>
        @if($pleinePage)
            <button type="button" @click="reduire()" class="text-sm font-semibold text-bleuone underline">Réduire</button>
        @endif
    </div>
</div>
