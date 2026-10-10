{{-- resources/views/shared/lecture_outils/vrai-faux.blade.php --}}
{{-- Terminée quand chaque affirmation a reçu une réponse. --}}
<div x-data="{
         solutions: @js(array_map(fn ($affirmation) => (bool) ($affirmation['reponse'] ?? false), $elements)),
         reponses: @js(array_fill(0, count($elements), null)),
         get nombreRepondues() {
             return this.reponses.filter((reponse) => reponse !== null).length;
         },
         get nombreBonnes() {
             return this.reponses.filter((reponse, index) => reponse !== null && reponse === this.solutions[index]).length;
         },
         repondre(index, valeur) {
             if (this.reponses[index] !== null) return;
             this.reponses[index] = valeur;
             if (this.nombreRepondues === this.reponses.length) this.terminer();
         },
         recommencer() {
             this.reponses = this.reponses.map(() => null);
         },
     }">
    <ol class="space-y-4">
        @foreach($elements as $index => $affirmation)
            <li class="rounded-xl border border-gray-200 p-4">
                <p class="mb-3 whitespace-pre-line font-medium text-gray-900">{{ $affirmation['texte'] }}</p>

                <div class="flex gap-3">
                    <button type="button"
                            :disabled="reponses[{{ $index }}] !== null"
                            @click="repondre({{ $index }}, true)"
                            class="flex-1 rounded-xl border-2 px-5 py-3 text-sm font-bold text-green-700 transition"
                            :class="reponses[{{ $index }}] === true
                                ? 'border-vertone bg-vertone/10'
                                : (reponses[{{ $index }}] === null ? 'border-gray-200 hover:border-vertone hover:bg-vertone/10' : 'border-gray-200 opacity-60')">
                        Vrai
                    </button>
                    <button type="button"
                            :disabled="reponses[{{ $index }}] !== null"
                            @click="repondre({{ $index }}, false)"
                            class="flex-1 rounded-xl border-2 px-5 py-3 text-sm font-bold text-orangeone transition"
                            :class="reponses[{{ $index }}] === false
                                ? 'border-orangeone bg-orange-50'
                                : (reponses[{{ $index }}] === null ? 'border-gray-200 hover:border-orangeone hover:bg-orange-50' : 'border-gray-200 opacity-60')">
                        Faux
                    </button>
                </div>

                <div x-cloak x-show="reponses[{{ $index }}] !== null" role="status"
                     class="mt-3 rounded-lg border px-3 py-2 text-sm"
                     :class="reponses[{{ $index }}] === solutions[{{ $index }}]
                         ? 'border-green-200 bg-green-50 text-green-800'
                         : 'border-orange-200 bg-orange-50 text-orange-800'">
                    <span x-text="reponses[{{ $index }}] === solutions[{{ $index }}] ? 'Bonne réponse.' : 'Ce n\'est pas tout à fait ça.'"></span>
                    La bonne réponse était <strong>{{ ($affirmation['reponse'] ?? false) ? 'Vrai' : 'Faux' }}</strong>.
                    @if(($affirmation['explication'] ?? '') !== '')
                        <p class="mt-1 whitespace-pre-line text-gray-600">{{ $affirmation['explication'] }}</p>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    <div x-cloak x-show="nombreRepondues === reponses.length" class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <p role="status" class="text-sm font-semibold text-bleuone"
           x-text="nombreBonnes + (nombreBonnes > 1 ? ' bonnes réponses' : ' bonne réponse') + ' sur ' + reponses.length"></p>
        <button type="button" @click="recommencer()" class="text-sm font-semibold text-bleuone underline">Recommencer</button>
    </div>
</div>
