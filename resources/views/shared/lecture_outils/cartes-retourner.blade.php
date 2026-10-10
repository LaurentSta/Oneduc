{{-- resources/views/shared/lecture_outils/cartes-retourner.blade.php --}}
{{-- Terminée quand chaque carte a été retournée au moins une fois. --}}
@once
    <style>
        .outil-carte { perspective: 1000px; }
        .outil-carte-interieur { display: grid; height: 100%; transition: transform 0.5s; transform-style: preserve-3d; }
        .outil-carte.est-retournee .outil-carte-interieur { transform: rotateY(180deg); }
        /* Les deux faces partagent la même cellule : la carte prend la hauteur de la plus longue. */
        .outil-carte-face { grid-area: 1 / 1; display: flex; align-items: center; justify-content: center; min-height: 10rem; padding: 1rem; text-align: center; border-radius: 12px; -webkit-backface-visibility: hidden; backface-visibility: hidden; }
        .outil-carte-verso { transform: rotateY(180deg); }
        @media (prefers-reduced-motion: reduce) {
            .outil-carte-interieur { transition: none; }
        }
    </style>
@endonce

<div x-data="{
         retournees: @js(array_fill(0, count($elements), false)),
         vues: @js(array_fill(0, count($elements), false)),
         get nombreVues() {
             return this.vues.filter(Boolean).length;
         },
         retourner(index) {
             this.retournees[index] = !this.retournees[index];
             this.vues[index] = true;
             if (this.nombreVues === this.vues.length) this.terminer();
         },
     }">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        @foreach($elements as $index => $carte)
            <div>
                <button type="button"
                        class="outil-carte block h-full w-full cursor-pointer rounded-[12px] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-bleuone"
                        :class="retournees[{{ $index }}] && 'est-retournee'"
                        :aria-pressed="retournees[{{ $index }}].toString()"
                        @click="retourner({{ $index }})">
                    <span class="outil-carte-interieur">
                        <span class="outil-carte-face border border-purple-200 bg-purple-50 text-sm font-semibold text-purple-900"
                              :aria-hidden="retournees[{{ $index }}].toString()">
                            <span class="whitespace-pre-line">{{ $carte['recto'] }}</span>
                        </span>
                        <span class="outil-carte-face outil-carte-verso border border-orangeone/30 bg-orange-50 text-sm text-gray-800"
                              :aria-hidden="(!retournees[{{ $index }}]).toString()">
                            <span class="whitespace-pre-line">{{ $carte['verso'] }}</span>
                        </span>
                    </span>
                </button>
                {{-- Un lecteur d'écran ne relit pas un bouton après un clic : la face découverte est annoncée ici. --}}
                <span class="sr-only" aria-live="polite"
                      x-text="vues[{{ $index }}] ? (retournees[{{ $index }}] ? @js('Verso : '.$carte['verso']) : @js('Recto : '.$carte['recto'])) : ''"></span>
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-sm text-gray-500">
        <span x-text="nombreVues">0</span> / {{ count($elements) }} carte{{ count($elements) > 1 ? 's' : '' }} retournée{{ count($elements) > 1 ? 's' : '' }}
    </p>
</div>
