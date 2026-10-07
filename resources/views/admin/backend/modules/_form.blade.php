@php
    $module = $module ?? null;
    $old = fn (string $field, $default = null) => old($field, $module->{$field} ?? $default);
    $currentModuleVideoSrc = $module && $module->module_video
        ? \App\Support\LearningAssetPath::resolveModuleVideoUrl($module->module_video)
        : null;
@endphp

{{-- 1. Informations générales --}}
<section class="form-oneduc-section">
  <h2 class="form-oneduc-section-title">1. Informations générales</h2>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <label class="form-oneduc-label">Nom technique <span class="text-red-600">*</span></label>
      <input name="module_name" value="{{ $old('module_name') }}" required
             class="form-oneduc-input" placeholder="Ex : word_debutant_01">
    </div>

    <div>
      <label class="form-oneduc-label">Titre affiché <span class="text-red-600">*</span></label>
      <input name="module_title" value="{{ $old('module_title') }}" required
             class="form-oneduc-input" placeholder="Ex : Word – Débuter">
    </div>

    <div>
      <label class="form-oneduc-label">Slug</label>
      <input name="module_name_slug" value="{{ $old('module_name_slug') }}"
             class="form-oneduc-input" placeholder="Ex : word-debuter">
    </div>

    <div>
      <label class="form-oneduc-label">{{ $module ? 'Remplacer par un fichier vidéo' : 'Téléverser une vidéo locale' }}</label>
      <input id="module_video_file" type="file" name="module_video_file"
             accept="video/mp4,video/x-m4v,video/quicktime,video/x-msvideo,video/webm"
             class="form-oneduc-file">
      <div class="form-oneduc-help">
        Formats acceptés : MP4, M4V, MOV, AVI, WEBM.
        Le fichier sera enregistré dans `public/modules/videos/modules/module_{{ $module->id ?? '{id}' }}`.
      </div>
      @error('module_video_file')
        <p class="text-sm text-red-600">{{ $message }}</p>
      @enderror
      <video id="moduleVideoPreview"
             class="mt-3 w-full max-w-md rounded-lg border border-gray-200 bg-black {{ $currentModuleVideoSrc ? '' : 'hidden' }}"
             controls preload="metadata"
             @if($currentModuleVideoSrc) src="{{ $currentModuleVideoSrc }}" @endif></video>
    </div>

    <div>
      <label class="form-oneduc-label">Label</label>
      <input name="label" value="{{ $old('label') }}"
             class="form-oneduc-input" placeholder="Ex : Gratuit / Premium">
    </div>

    <div>
      <label class="form-oneduc-label">Durée</label>
      <input name="duree" value="{{ $old('duree') }}"
             class="form-oneduc-input" placeholder="Ex : 2h, 3 jours">
    </div>

    <div>
      <label class="form-oneduc-label">Temps estimé par question (secondes)</label>
      @php
        // À ne pas lire via $module->estimated_question_seconds : un accesseur du même nom
        // recalcule un total (nb questions x secondes) et masque la valeur brute stockée.
        $estimatedSecondsRaw = $module?->getRawOriginal('estimated_question_seconds');
      @endphp
      <input type="number" min="1" max="600" name="estimated_question_seconds"
             value="{{ old('estimated_question_seconds', $estimatedSecondsRaw ?? 30) }}"
             class="form-oneduc-input" placeholder="30">
      <div class="form-oneduc-help">Cette valeur est utilisée pour calculer la durée estimée du module en fonction des questions.</div>
    </div>
  </div>
</section>

{{-- 2. Catégorisation --}}
<section class="form-oneduc-section form-oneduc-section--alt">
  <h2 class="form-oneduc-section-title">2. Catégorisation</h2>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <label class="form-oneduc-label">Catégorie <span class="text-red-600">*</span></label>
      <select name="category_id" required class="form-oneduc-select">
        <option value="">-- Choisir une catégorie --</option>
        @foreach ($categories as $cat)
          <option value="{{ $cat->id }}" @selected($old('category_id') == $cat->id)>{{ $cat->category_name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="form-oneduc-label">Sous-catégorie <span class="text-red-600">*</span></label>
      <select name="subcategory_id" required class="form-oneduc-select">
        <option value="">-- Choisir une sous-catégorie --</option>
        @foreach ($subcategories as $sub)
          <option value="{{ $sub->id }}" @selected($old('subcategory_id') == $sub->id)>{{ $sub->subcategory_name }}</option>
        @endforeach
      </select>
      <div class="form-oneduc-help">Rendu obligatoire pour éviter l’erreur "champ vide".</div>
    </div>

    <div>
      <label class="form-oneduc-label">Évaluation (optionnelle)</label>
      <select name="evaluation_id" class="form-oneduc-select">
        <option value="">-- Aucune --</option>
        @foreach ($evaluations as $eval)
          <option value="{{ $eval->id }}" @selected($old('evaluation_id') == $eval->id)>{{ $eval->titre }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="form-oneduc-label">Formateur <span class="text-red-600">*</span></label>
      <select name="formateur_id" required class="form-oneduc-select">
        <option value="">-- Choisir un formateur --</option>
        @foreach ($formateurs as $f)
          <option value="{{ $f->id }}" @selected($old('formateur_id') == $f->id)>{{ $f->name }}</option>
        @endforeach
      </select>
    </div>

    <div>
      <label class="form-oneduc-label">Certificat <span class="text-red-600">*</span></label>
      @php($certificatValue = $old('certificat'))
      <select name="certificat" required class="form-oneduc-select">
        @if ($certificatValue === null)
          <option value="" selected disabled>-- Avec certificat ? --</option>
        @endif
        <option value="1" @selected($certificatValue == 1)>Oui</option>
        <option value="0" @selected($certificatValue !== null && $certificatValue == 0)>Non</option>
      </select>
    </div>
  </div>
</section>

{{-- 3. Ressources & images --}}
<section class="form-oneduc-section">
  <h2 class="form-oneduc-section-title">3. Ressources & images</h2>

  <div class="grid grid-cols-1 gap-6">
    <div>
      <label class="form-oneduc-label">Ressources (URL ou chemin)</label>
      <input name="resources" value="{{ $old('resources') }}"
             class="form-oneduc-input" placeholder="Ex : https://... ou storage/...">
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <x-admin.module-image-field
        name="header_image"
        label="Image d’en-tête"
        :current="$module->header_image ?? null"
    />

    <x-admin.module-image-field
        name="module_image"
        label="Image principale"
        :current="$module->module_image ?? null"
    />
  </div>
</section>

{{-- 4. Contenu pédagogique --}}
<section class="form-oneduc-section">
  <h2 class="form-oneduc-section-title">4. Contenu pédagogique</h2>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      <label class="form-oneduc-label">Prérequis</label>
      <textarea name="prerequi" rows="5" class="form-oneduc-textarea"
                placeholder="Ex : savoir utiliser la souris...">{{ $old('prerequi') }}</textarea>
    </div>

    <div>
      <label class="form-oneduc-label">Description</label>
      <textarea name="description" rows="5" class="form-oneduc-textarea"
                placeholder="Décrire le module...">{{ $old('description') }}</textarea>
    </div>
  </div>
</section>

{{-- 5. Options --}}
<section class="form-oneduc-section form-oneduc-section--alt">
  <h2 class="form-oneduc-section-title">5. Options</h2>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="bestseller" value="1" @checked($old('bestseller'))>
      Bestseller
    </label>

    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="vedette" value="1" @checked($old('vedette'))>
      Vedette
    </label>

    <label class="inline-flex items-center gap-2">
      <input type="checkbox" name="surevalue" value="1" @checked($old('surevalue'))>
      Valeur ajoutée
    </label>

    @if ($module)
      <div>
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" name="status" value="1" @checked($old('status'))>
          Actif
        </label>
        <div class="form-oneduc-help">Publie une nouvelle version de ce brouillon dans le catalogue (nécessite au moins une section et une leçon).</div>
      </div>
    @endif
  </div>
</section>

<script>
  function bindVideoPreview(inputId, videoId) {
    const input = document.getElementById(inputId);
    const video = document.getElementById(videoId);
    if (!input || !video) return;

    input.addEventListener('change', function() {
      if (!this.files || !this.files[0]) {
        video.pause();
        video.removeAttribute('src');
        video.load();
        video.classList.add('hidden');
        return;
      }

      video.src = URL.createObjectURL(this.files[0]);
      video.classList.remove('hidden');
      video.load();
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    bindVideoPreview('module_video_file', 'moduleVideoPreview');
  });
</script>
