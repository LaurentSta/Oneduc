@extends('formateur.dashboard')

@section('formateur')
@php
    // En modification, l'assistant s'ouvre sur l'image et les zones déjà enregistrées.
    $editing = $editing ?? null;
    $imageInitiale = $editing?->image_url;
    $zonesInitiales = $editing ? array_values($editing->zones ?? []) : [];
    $titreInitial = (string) old('title', $editing?->title);
@endphp
<div class="w-full px-6 lg:px-8">

  <header class="bg-white rounded-[20px] shadow-md px-8 pt-5 pb-6 my-6">
    <nav class="text-sm font-varela text-gray-500 mb-2">
      <ol class="inline-flex items-center space-x-1">
        <li><a href="{{ route('formateur.outils.index') }}" class="text-orangeone hover:underline">Outils numériques</a></li>
        <li><span class="mx-2 text-gray-400">/</span></li>
        <li><a href="{{ route('formateur.composants.index') }}" class="text-orangeone hover:underline">Zone de clic</a></li>
        <li><span class="mx-2 text-gray-400">/</span></li>
        <li class="text-gray-400">{{ $editing ? $editing->title : 'Nouvelle' }}</li>
      </ol>
    </nav>
    <p class="font-raleway text-2xl text-bleuone">{{ $editing ? 'Modifier la zone de clic' : 'Nouvelle zone de clic' }}</p>
  </header>

  {{-- Un seul formulaire, envoyé à la dernière étape : les champs restent dans la page d'une étape à l'autre. --}}
  <form method="POST" enctype="multipart/form-data"
        action="{{ $editing ? route('formateur.composants.activities.update', $editing) : route('formateur.composants.store') }}"
        class="mx-auto mb-8 max-w-5xl rounded-[20px] bg-white p-6 shadow-md"
        @submit="if (step !== 3) $event.preventDefault()"
        x-data='{
         step: {{ $editing ? 2 : 1 }},
         title: @json($titreInitial),
         imageSrc: @json($imageInitiale),
         imageFile: null,
         zones: @json($zonesInitiales),
         drawing: false,
         startX: 0,
         startY: 0,
         currentRect: null,
         currentShape: "square",
         selectedShape: "square",
         pendingZone: null,
         labelInput: "",
         descriptionInput: "",
         movingIndex: null,
         resizingIndex: null,
         resizeHandle: "",
         handles: ["n", "s", "e", "w", "nw", "ne", "sw", "se"],
         zoneAt(i) {
           return i === -1 ? this.pendingZone : this.zones[i];
         },
         moveOffsetX: 0,
         moveOffsetY: 0,
         onFileChange(e) {
           const file = e.target.files[0];
           if (!file) return;
           this.imageFile = file;
           this.imageSrc = URL.createObjectURL(file);
           this.zones = [];
           this.pendingZone = null;
         },
         pointFromEvent(e) {
           const rect = this.$refs.imageWrap.getBoundingClientRect();
           const x = Math.max(0, Math.min(100, ((e.clientX - rect.left) / rect.width) * 100));
           const y = Math.max(0, Math.min(100, ((e.clientY - rect.top) / rect.height) * 100));
           return { x, y };
         },
         startDraw(e) {
           if (this.pendingZone) return;
           const p = this.pointFromEvent(e);
           this.startX = p.x;
           this.startY = p.y;
           this.drawing = true;
           this.currentShape = this.selectedShape;
           this.currentRect = { x: p.x, y: p.y, w: 0, h: 0 };
         },
         squareFromStart(p) {
           const rect = this.$refs.imageWrap.getBoundingClientRect();
           const roomX = p.x >= this.startX ? 100 - this.startX : this.startX;
           const roomY = p.y >= this.startY ? 100 - this.startY : this.startY;
           const sidePx = Math.min(
             Math.max((Math.abs(p.x - this.startX) / 100) * rect.width, (Math.abs(p.y - this.startY) / 100) * rect.height),
             (roomX / 100) * rect.width,
             (roomY / 100) * rect.height
           );
           const w = (sidePx / rect.width) * 100;
           const h = (sidePx / rect.height) * 100;
           return {
             x: p.x >= this.startX ? this.startX : this.startX - w,
             y: p.y >= this.startY ? this.startY : this.startY - h,
             w,
             h,
           };
         },
         moveDraw(e) {
           if (!this.drawing) return;
           this.currentRect = this.squareFromStart(this.pointFromEvent(e));
         },
         endDraw() {
           if (!this.drawing) return;
           this.drawing = false;
           if (this.currentRect && this.currentRect.w > 1.5 && this.currentRect.h > 1.5) {
             this.pendingZone = { ...this.currentRect, shape: this.currentShape };
           }
           this.currentRect = null;
         },
         confirmZone() {
           const label = this.labelInput.trim();
           if (!label || !this.pendingZone) return;
           this.zones.push({ ...this.pendingZone, label, description: this.descriptionInput.trim() });
           this.pendingZone = null;
           this.labelInput = "";
           this.descriptionInput = "";
         },
         cancelZone() {
           this.pendingZone = null;
           this.labelInput = "";
           this.descriptionInput = "";
         },
         removeZone(index) {
           this.zones.splice(index, 1);
         },
         startMove(e, i) {
           if (this.drawing || (this.pendingZone && i !== -1)) return;
           const p = this.pointFromEvent(e);
           this.movingIndex = i;
           this.moveOffsetX = p.x - this.zoneAt(i).x;
           this.moveOffsetY = p.y - this.zoneAt(i).y;
         },
         moveZone(e) {
           if (this.movingIndex === null) return;
           const p = this.pointFromEvent(e);
           const zone = this.zoneAt(this.movingIndex);
           const maxX = 100 - zone.w;
           const maxY = 100 - zone.h;
           zone.x = Math.max(0, Math.min(maxX, p.x - this.moveOffsetX));
           zone.y = Math.max(0, Math.min(maxY, p.y - this.moveOffsetY));
         },
         endMove() {
           this.movingIndex = null;
         },
         startResize(i, handle) {
           if (this.drawing || (this.pendingZone && i !== -1)) return;
           const zone = this.zoneAt(i);
           this.startX = handle.includes("w") ? zone.x + zone.w : zone.x;
           this.startY = handle.includes("n") ? zone.y + zone.h : zone.y;
           this.resizeHandle = handle;
           this.resizingIndex = i;
         },
         resizeZone(e) {
           if (this.resizingIndex === null) return;
           const zone = this.zoneAt(this.resizingIndex);
           const p = this.pointFromEvent(e);
           const w = Math.abs(p.x - this.startX);
           const h = Math.abs(p.y - this.startY);
           if ((this.resizeHandle.includes("e") || this.resizeHandle.includes("w")) && w > 1.5) {
             zone.x = Math.min(p.x, this.startX);
             zone.w = w;
           }
           if ((this.resizeHandle.includes("n") || this.resizeHandle.includes("s")) && h > 1.5) {
             zone.y = Math.min(p.y, this.startY);
             zone.h = h;
           }
         },
         endResize() {
           this.resizingIndex = null;
         },
         handleStyle(handle) {
           const left = handle.includes("w") ? 0 : (handle.includes("e") ? 100 : 50);
           const top = handle.includes("n") ? 0 : (handle.includes("s") ? 100 : 50);
           let cursor = (handle === "nw" || handle === "se") ? "nwse-resize" : "nesw-resize";
           if (handle === "n" || handle === "s") cursor = "ns-resize";
           if (handle === "e" || handle === "w") cursor = "ew-resize";
           return `left:${left}%;top:${top}%;cursor:${cursor};`;
         },
         shapeExtraClass(shape) {
           return shape === "circle" ? "rounded-full" : "";
         },
       }'>
    @csrf
    @if($editing)
      @method('PUT')
    @endif

    @if($errors->any())
      <div class="mb-6 rounded-[10px] bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        {{ $errors->first() }}
      </div>
    @endif

    {{-- Puces de progression --}}
    <div class="flex items-center justify-center gap-2 mb-8 text-xs font-semibold">
      <span class="flex items-center gap-1.5" :class="step >= 1 ? 'text-bleuone' : 'text-gray-400'">
        <span class="w-2 h-2 rounded-full" :class="step > 1 ? 'bg-emerald-500' : (step === 1 ? 'bg-bleuone ring-4 ring-bleuone/15' : 'bg-gray-300')"></span>
        Image
      </span>
      <span class="w-4 h-px bg-gray-200"></span>
      <span class="flex items-center gap-1.5" :class="step >= 2 ? 'text-bleuone' : 'text-gray-400'">
        <span class="w-2 h-2 rounded-full" :class="step > 2 ? 'bg-emerald-500' : (step === 2 ? 'bg-bleuone ring-4 ring-bleuone/15' : 'bg-gray-300')"></span>
        Zones
      </span>
      <span class="w-4 h-px bg-gray-200"></span>
      <span class="flex items-center gap-1.5" :class="step >= 3 ? 'text-bleuone' : 'text-gray-400'">
        <span class="w-2 h-2 rounded-full" :class="step === 3 ? 'bg-bleuone ring-4 ring-bleuone/15' : 'bg-gray-300'"></span>
        Enregistrer
      </span>
    </div>

    {{-- Étape 1 : l'image --}}
    <div x-show="step === 1" x-cloak class="mx-auto max-w-xl">
      <h2 class="text-lg font-bold text-bleuone text-center mb-1">Quelle image ?</h2>
      <p class="text-xs text-gray-500 text-center mb-5">Un schéma, une photo, une capture d'écran : ce que le stagiaire devra explorer.</p>

      <div class="space-y-4">
        <div>
          <label for="component-title-input" class="block text-xs font-semibold text-gray-600 mb-1">Titre</label>
          <input id="component-title-input" type="text" name="title" maxlength="255" x-model="title"
                 @keydown.enter.prevent
                 placeholder="Ex : L'intérieur d'un ordinateur"
                 class="w-full rounded-[10px] border border-gray-300 px-3 py-2.5 text-sm focus:border-orangeone focus:outline-none focus:ring-2 focus:ring-orange-200">
        </div>

        <div>
          <p class="block text-xs font-semibold text-gray-600 mb-1">Image</p>
          <div class="flex items-center gap-3">
            <label for="component-image-input"
                   class="inline-flex shrink-0 cursor-pointer items-center rounded-[8px] bg-orange-50 px-3 py-2 text-xs font-semibold text-orangeone hover:bg-orange-100 transition">
              {{ $editing ? 'Remplacer l’image' : 'Choisir un fichier' }}
            </label>
            <span class="truncate text-xs text-gray-500" x-text="imageFile ? imageFile.name : '{{ $editing ? 'Image actuelle conservée' : 'Aucun fichier choisi' }}'"></span>
          </div>
          <input id="component-image-input" type="file" name="image" accept="image/*"
                 @change="onFileChange($event)"
                 class="hidden">
          @if($editing)
            <p class="mt-1 text-[11px] text-gray-400">Changer d’image efface les zones déjà dessinées.</p>
          @endif
          <img x-show="imageSrc" x-cloak :src="imageSrc" alt="" class="mt-3 max-h-56 rounded-[10px] border border-gray-200">
        </div>
      </div>

      <div class="mt-6 flex items-center justify-between gap-3">
        <a href="{{ route('formateur.composants.index') }}" class="inline-flex items-center justify-center rounded-[10px] border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">Annuler</a>
        <button type="button" :disabled="!imageSrc" @click="step = 2" class="inline-flex items-center justify-center rounded-[10px] bg-orangeone px-5 py-2.5 text-sm font-bold text-white hover:bg-orangeone-hover transition disabled:cursor-not-allowed disabled:opacity-50">Continuer</button>
      </div>
    </div>

    {{-- Étape 2 : les zones --}}
    <div x-show="step === 2" x-cloak>
      <h2 class="text-lg font-bold text-bleuone text-center mb-1">Où sont les éléments à trouver ?</h2>
      <p class="text-xs text-gray-500 text-center mb-5">Tracez une zone sur chaque élément, puis donnez-lui un nom.</p>

        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Forme de la zone</label>
        <div class="mb-3 grid max-w-xs grid-cols-2 gap-2">
          <button type="button" @click="selectedShape = 'square'"
                  :class="selectedShape === 'square' ? 'border-orangeone bg-orange-50' : 'border-gray-200 bg-white hover:border-orange-300'"
                  class="flex flex-col items-center gap-1 rounded-[8px] border-2 py-2 transition">
            <span class="block h-4 w-4 bg-gray-500"></span>
            <span class="text-[10px] font-semibold text-gray-600">Carré</span>
          </button>
          <button type="button" @click="selectedShape = 'circle'"
                  :class="selectedShape === 'circle' ? 'border-orangeone bg-orange-50' : 'border-gray-200 bg-white hover:border-orange-300'"
                  class="flex flex-col items-center gap-1 rounded-[8px] border-2 py-2 transition">
            <span class="block h-4 w-4 rounded-full bg-gray-500"></span>
            <span class="text-[10px] font-semibold text-gray-600">Rond</span>
          </button>
        </div>

        <label class="block text-xs font-semibold text-gray-600 mb-1">
          Dessinez une zone par composant à trouver, puis nommez-la.
        </label>
        <p class="mb-1 text-[11px] text-gray-400">Astuce : dès qu'une zone est tracée, cliquez-glissez-la pour la déplacer. Tirez une poignée sur un côté pour l'étirer en largeur ou en hauteur, un coin pour les deux à la fois.</p>
        <div class="relative select-none rounded-[10px] overflow-hidden border border-gray-200"
             x-ref="imageWrap"
             @mousedown="startDraw($event)"
             @mousemove="moveDraw($event); moveZone($event); resizeZone($event)"
             @mouseup="endDraw(); endMove(); endResize()"
             @mouseleave="drawing && endDraw(); endMove(); endResize()">
          <img :src="imageSrc" draggable="false" class="w-full h-auto block pointer-events-none">

          <template x-for="(zone, i) in zones" :key="i">
            <div class="absolute cursor-move pointer-events-auto"
                 :class="(movingIndex === i || resizingIndex === i) ? 'z-10' : ''"
                 @mousedown.stop="startMove($event, i)"
                 :style="`left:${zone.x}%;top:${zone.y}%;width:${zone.w}%;height:${zone.h}%;`">
              <div class="absolute inset-0 border-2 border-vertone bg-vertone/20" :class="shapeExtraClass(zone.shape)"></div>
              <span class="absolute -top-5 left-0 whitespace-nowrap text-[10px] font-bold bg-vertone text-white px-1 rounded" x-text="zone.label"></span>
              <template x-for="handle in handles" :key="handle">
                <span class="absolute h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-vertone bg-white"
                      :style="handleStyle(handle)"
                      @mousedown.stop="startResize(i, handle)"></span>
              </template>
            </div>
          </template>

          <div x-show="currentRect" x-cloak class="absolute pointer-events-none"
               :style="currentRect ? `left:${currentRect.x}%;top:${currentRect.y}%;width:${currentRect.w}%;height:${currentRect.h}%;` : ''">
            <div class="absolute inset-0 border-2 border-dashed border-orangeone bg-orangeone/10" :class="shapeExtraClass(currentShape)"></div>
          </div>

          <div x-show="pendingZone" x-cloak class="absolute z-10 cursor-move"
               @mousedown.stop="startMove($event, -1)"
               :style="pendingZone ? `left:${pendingZone.x}%;top:${pendingZone.y}%;width:${pendingZone.w}%;height:${pendingZone.h}%;` : ''">
            <div class="absolute inset-0 border-2 border-orangeone bg-orangeone/20" :class="pendingZone ? shapeExtraClass(pendingZone.shape) : ''"></div>
            <template x-for="handle in handles" :key="handle">
              <span class="absolute h-3 w-3 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-orangeone bg-white"
                    :style="handleStyle(handle)"
                    @mousedown.stop="startResize(-1, handle)"></span>
            </template>
          </div>
        </div>

        <div x-show="pendingZone" x-cloak class="mt-2 space-y-2">
          <input type="text" x-model="labelInput" maxlength="100"
                 @keydown.enter.prevent="confirmZone()"
                 placeholder="Nom du composant (ex : clavier)"
                 class="w-full rounded-[8px] border border-gray-300 px-3 py-2 text-sm focus:border-orangeone focus:outline-none focus:ring-2 focus:ring-orange-200">
          <textarea x-model="descriptionInput" maxlength="500" rows="2"
                    placeholder="Description (optionnelle) : à quoi sert ce composant ?"
                    class="w-full rounded-[8px] border border-gray-300 px-3 py-2 text-sm focus:border-orangeone focus:outline-none focus:ring-2 focus:ring-orange-200"></textarea>
          <div class="flex items-center justify-between gap-2">
            <p class="text-[11px] text-gray-400">La description s'affiche au stagiaire une fois sa réponse donnée.</p>
            <div class="flex shrink-0 gap-2">
              <button type="button" @click="confirmZone()"
                      class="rounded-[8px] bg-orangeone px-3 py-2 text-xs font-bold text-white hover:bg-orangeone-hover">
                Ajouter
              </button>
              <button type="button" @click="cancelZone()"
                      class="rounded-[8px] border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50">
                Annuler
              </button>
            </div>
          </div>
        </div>

        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
          <template x-for="(zone, i) in zones" :key="'list-' + i">
            <div class="rounded-[8px] bg-gray-50 px-3 py-2">
              <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-gray-700" x-text="zone.label"></span>
                <button type="button" @click="removeZone(i)"
                        class="text-[11px] font-semibold text-red-500 hover:text-red-600">
                  Supprimer
                </button>
              </div>
              <textarea x-model="zone.description" maxlength="500" rows="2"
                        placeholder="Description (optionnelle)"
                        class="mt-1.5 w-full rounded-[8px] border border-gray-300 bg-white px-2 py-1.5 text-xs focus:border-orangeone focus:outline-none focus:ring-2 focus:ring-orange-200"></textarea>
            </div>
          </template>
        </div>

      <p x-show="zones.length < 2" x-cloak class="mt-4 text-xs text-orangeone">Ajoutez au moins deux éléments pour continuer.</p>
      <div class="mt-6 flex items-center justify-between gap-3">
        <button type="button" @click="step = 1" class="inline-flex items-center justify-center rounded-[10px] border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">Retour</button>
        <button type="button" :disabled="zones.length < 2 || !!pendingZone" @click="step = 3" class="inline-flex items-center justify-center rounded-[10px] bg-orangeone px-5 py-2.5 text-sm font-bold text-white hover:bg-orangeone-hover transition disabled:cursor-not-allowed disabled:opacity-50">Continuer</button>
      </div>
    </div>

    {{-- Étape 3 : enregistrer --}}
    <div x-show="step === 3" x-cloak class="mx-auto max-w-xl">
      <h2 class="text-lg font-bold text-bleuone text-center mb-1">{{ $editing ? 'Enregistrer les modifications ?' : 'Tout est prêt ?' }}</h2>
      <p class="text-xs text-gray-500 text-center mb-5">
        @if($editing)
          La modification s'applique dans toutes les leçons qui utilisent cette zone de clic. Les jeux déjà lancés gardent leur version.
        @else
          Une fois enregistrée, la zone de clic se lance pour un groupe ou s'insère dans une leçon.
        @endif
      </p>

      <div class="flex items-center gap-4 rounded-xl border-2 border-gray-200 p-4">
        <img :src="imageSrc" alt="" class="h-20 w-28 shrink-0 rounded-[8px] border border-gray-200 object-cover">
        <div class="min-w-0">
          <p class="truncate text-sm font-bold text-gray-900" x-text="title.trim() || 'Zone de clic'"></p>
          <p class="text-xs text-gray-500"><span x-text="zones.length"></span> éléments à trouver</p>
        </div>
      </div>

      @if(! $editing && $groups->isNotEmpty())
        <div class="mt-4">
          <label for="component-group-input" class="block text-xs font-semibold text-gray-600 mb-1">Lancer tout de suite pour un groupe (optionnel)</label>
          <select id="component-group-input" name="group_id" class="w-full rounded-[10px] border border-gray-300 px-3 py-2.5 text-sm focus:border-orangeone focus:outline-none focus:ring-2 focus:ring-orange-200">
            <option value="">Enregistrer sans lancer</option>
            @foreach($groups as $group)
              <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                {{ $group->name }} ({{ $group->students_count }} stagiaire{{ $group->students_count > 1 ? 's' : '' }})
              </option>
            @endforeach
          </select>
        </div>
      @endif

      <input type="hidden" name="zones" :value="JSON.stringify(zones)">

      <div class="mt-6 flex items-center justify-between gap-3">
        <button type="button" @click="step = 2" class="inline-flex items-center justify-center rounded-[10px] border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition">Retour</button>
        <button type="submit" class="inline-flex items-center justify-center rounded-[10px] bg-orangeone px-5 py-2.5 text-sm font-bold text-white hover:bg-orangeone-hover transition disabled:cursor-not-allowed disabled:opacity-50">{{ $editing ? 'Enregistrer les modifications' : 'Enregistrer la zone de clic' }}</button>
      </div>
    </div>
  </form>
</div>
@endsection
