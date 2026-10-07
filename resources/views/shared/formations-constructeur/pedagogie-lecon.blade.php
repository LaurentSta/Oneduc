@php
  $objectifsInitiaux = old('objectives', $lecture->objectives->map(fn ($objectif) => [
      'id' => $objectif->id, 'title' => $objectif->title, 'description' => $objectif->description,
      'competency_ids' => $objectif->competencies->pluck('id')->map(fn ($id) => (string) $id)->all(),
  ])->all());
@endphp
<aside class="min-w-0 rounded-[20px] bg-white p-5 shadow-md lg:self-start"
       x-data="{ objectifs: @js(array_values($objectifsInitiaux ?? [])), modifie: false }"
       @beforeunload.window="if (modifie) { $event.preventDefault(); $event.returnValue = ''; }">
  <h2 class="font-varela text-lg font-bold text-bleuone">Intention pédagogique</h2>
  <p class="mt-2 text-sm leading-relaxed text-gray-600">Que saura faire le stagiaire à la fin de cette leçon ?</p>
  <form method="POST" action="{{ route($nomRoutesConstructeur.'.lectures.pedagogie', $lecture) }}"
        class="mt-5 space-y-5" @change="modifie = true" @input="modifie = true" @submit="if (!$event.defaultPrevented) modifie = false">
    @csrf
    @method('PUT')
    <input type="hidden" name="objectives_present" value="1">
    <fieldset @disabled($lectureSeule) class="space-y-5">
      <div>
        <label for="duree-lecon" class="block text-sm font-semibold text-bleuone">Durée estimée (minutes)</label>
        <input id="duree-lecon" type="number" name="duration" min="0" max="1440"
               value="{{ old('duration', $lecture->duration) }}" class="mt-2 w-full rounded-[10px] border-gray-300 text-sm">
        <p class="mt-1 text-xs text-gray-500">Incluez le temps de lecture et de pratique.</p>
      </div>
      <div class="space-y-3">
        <h3 class="text-sm font-semibold text-bleuone">Objectifs et compétences</h3>
        <template x-for="(objectif, index) in objectifs" :key="objectif.id || 'nouveau-' + index">
          <div class="space-y-2 rounded-[12px] border border-gray-200 bg-gray-50 p-3">
            <input type="hidden" :name="'objectives[' + index + '][id]'" :value="objectif.id || ''">
            <label class="block text-sm font-semibold" :for="'objectif-' + index">Objectif <span x-text="index + 1"></span></label>
            <input :id="'objectif-' + index" :name="'objectives[' + index + '][title]'"
                   x-model="objectif.title" required maxlength="255"
                   placeholder="Ex. Envoyer un courriel avec une pièce jointe"
                   class="w-full rounded-[8px] border-gray-300 text-sm">
            <label class="block text-xs text-gray-600" :for="'description-objectif-' + index">Critère de réussite ou précision</label>
            <textarea :id="'description-objectif-' + index" :name="'objectives[' + index + '][description]'"
                      x-model="objectif.description" rows="2" maxlength="3000"
                      class="w-full rounded-[8px] border-gray-300 text-sm"></textarea>
            <details>
              <summary class="cursor-pointer text-sm font-semibold text-bleuone">Compétences associées</summary>
              <div class="mt-2 max-h-48 space-y-2 overflow-y-auto">
                @forelse($competences as $competence)
                  <label class="flex items-start gap-2 text-xs">
                    <input type="checkbox" :name="'objectives[' + index + '][competency_ids][]'"
                           value="{{ $competence->id }}" x-model="objectif.competency_ids" class="mt-0.5 rounded border-gray-300 text-orangeone">
                    <span>{{ $competence->code }} — {{ $competence->label }}{{ $competence->is_active ? '' : ' (inactive)' }}</span>
                  </label>
                @empty
                  <p class="text-xs text-gray-500">Aucune compétence disponible.</p>
                @endforelse
              </div>
            </details>
            @unless($lectureSeule)
              <button type="button" @click="objectifs.splice(index, 1); modifie = true"
                      class="text-xs font-semibold text-red-700">Retirer cet objectif</button>
            @endunless
          </div>
        </template>
        @unless($lectureSeule)
          <button type="button" @click="objectifs.push({ id: null, title: '', description: '', competency_ids: [] }); modifie = true"
                  class="btn-oneduc-outline !w-full !px-3 !py-2 !text-sm">+ Ajouter un objectif</button>
        @endunless
      </div>
      <details open class="border-t border-gray-200 pt-4">
        <summary class="cursor-pointer text-sm font-semibold text-bleuone">Validation de la leçon</summary>
        <div class="mt-3 space-y-3">
          <input type="hidden" name="quiz_enabled" value="0">
          <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" name="quiz_enabled" value="1" @checked(old('quiz_enabled', $lecture->quiz_enabled))
                   class="mt-0.5 rounded border-gray-300 text-orangeone">
            <span>Demander la réussite du quiz de validation</span>
          </label>
          <label for="questions-par-tentative" class="block text-sm">Questions par tentative</label>
          <input id="questions-par-tentative" type="number" name="quiz_questions_per_attempt" min="0"
                 value="{{ old('quiz_questions_per_attempt', $lecture->quiz_questions_per_attempt) }}"
                 class="w-full rounded-[10px] border-gray-300 text-sm">
          <p class="text-xs leading-relaxed text-gray-500">{{ $nombreQuestionsActives }} question(s) active(s) disponible(s). Le quiz conserve son seuil de réussite actuel de 50 %.</p>
          <a href="{{ route($nomRoutesConstructeur.'.quiz-questions.index', ['module' => $module, 'lecture' => $lecture->id]) }}"
             class="inline-block text-sm font-semibold text-orangeone hover:underline">Gérer les questions de cette leçon →</a>
        </div>
      </details>
      @unless($lectureSeule)
        <p x-show="modifie" x-cloak class="text-xs font-semibold text-orangeone" role="status">Réglages modifiés : pensez à les enregistrer.</p>
        <button type="submit" class="btn-oneduc !w-full !px-3 !py-2 !text-sm">Enregistrer les réglages</button>
      @endunless
    </fieldset>
  </form>
</aside>
