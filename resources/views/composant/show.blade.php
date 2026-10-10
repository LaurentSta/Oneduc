<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $session->title }} - Zone de clic</title>
  @vite(['resources/css/app.css'])
  <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="min-h-screen bg-slate-100 p-4"
      x-data='{
        zones: @json($zones->values()),
        currentIndex: 0,
        score: 0,
        details: [],
        feedback: null,
        startedAt: null,
        questionStartedAt: null,
        started: false,
        finished: {{ $existingAttempt ? "true" : "false" }},
        previousScore: {{ $existingAttempt->score ?? 0 }},
        previousTotal: {{ $existingAttempt->total ?? 0 }},
        submitting: false,
        isActive: {{ $session->is_active ? "true" : "false" }},
        start() {
          this.started = true;
          this.finished = false;
          this.currentIndex = 0;
          this.score = 0;
          this.details = [];
          this.feedback = null;
          this.startedAt = Date.now();
          this.questionStartedAt = Date.now();
        },
        handleClick(e) {
          if (this.feedback || !this.isActive) return;
          const rect = this.$refs.imageWrap.getBoundingClientRect();
          const x = ((e.clientX - rect.left) / rect.width) * 100;
          const y = ((e.clientY - rect.top) / rect.height) * 100;
          const zone = this.zones[this.currentIndex];
          const correct = this.pointInZone(x, y, zone);
          if (correct) this.score++;
          this.details.push({
            label: zone.label,
            correct: correct,
            time_ms: Date.now() - this.questionStartedAt,
          });
          this.feedback = { correct: correct, zone: zone };
        },
        pointInZone(x, y, zone) {
          const shape = zone.shape || "rectangle";
          if (shape === "circle" || shape === "oval") {
            const rx = zone.w / 2;
            const ry = zone.h / 2;
            if (rx <= 0 || ry <= 0) return false;
            const dx = (x - (zone.x + rx)) / rx;
            const dy = (y - (zone.y + ry)) / ry;
            return (dx * dx + dy * dy) <= 1;
          }
          if (shape === "triangle") {
            return this.pointInTriangle(
              x, y,
              zone.x + zone.w / 2, zone.y,
              zone.x, zone.y + zone.h,
              zone.x + zone.w, zone.y + zone.h
            );
          }
          return x >= zone.x && x <= zone.x + zone.w && y >= zone.y && y <= zone.y + zone.h;
        },
        pointInTriangle(px, py, x1, y1, x2, y2, x3, y3) {
          const sign = (ax, ay, bx, by, cx, cy) => (ax - cx) * (by - cy) - (bx - cx) * (ay - cy);
          const d1 = sign(px, py, x1, y1, x2, y2);
          const d2 = sign(px, py, x2, y2, x3, y3);
          const d3 = sign(px, py, x3, y3, x1, y1);
          const hasNeg = (d1 < 0) || (d2 < 0) || (d3 < 0);
          const hasPos = (d1 > 0) || (d2 > 0) || (d3 > 0);
          return !(hasNeg && hasPos);
        },
        shapeExtraClass(shape) {
          return (shape === "circle" || shape === "oval") ? "rounded-full" : "";
        },
        next() {
          this.feedback = null;
          this.currentIndex++;
          this.questionStartedAt = Date.now();
          if (this.currentIndex >= this.zones.length) {
            this.submit();
          }
        },
        async submit() {
          this.submitting = true;
          const durationSeconds = Math.round((Date.now() - this.startedAt) / 1000);
          try {
            await fetch(@json(route("composants.submit", $session->access_code)), {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": @json(csrf_token()),
              },
              body: JSON.stringify({
                score: this.score,
                total: this.zones.length,
                duration_seconds: durationSeconds,
                details: this.details,
              }),
            });
          } catch (e) {}
          this.submitting = false;
          this.finished = true;
          this.previousScore = this.score;
          this.previousTotal = this.zones.length;
        },
      }'>

  <div class="mx-auto w-full max-w-3xl space-y-4">
    <div class="rounded-2xl bg-white shadow-lg p-6">
      <p class="text-xs uppercase tracking-wide text-gray-400">Code - {{ $session->access_code }}</p>
      <h1 class="text-xl font-bold text-bleuone mt-1">{{ $session->title }}</h1>

      @unless($session->is_active)
        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
          Ce jeu est actuellement fermé.
        </div>
      @endunless
    </div>

    <div x-show="!started && !finished" x-cloak class="rounded-2xl bg-white shadow-lg p-6 text-center">
      <p class="text-gray-700 mb-4">
        Trouvez chaque composant demandé en cliquant sur la bonne zone de l'image.
        {{ $zones->count() }} composant{{ $zones->count() > 1 ? 's' : '' }} à trouver.
      </p>
      <button @click="start()" :disabled="!isActive"
              class="rounded-xl bg-bleuone px-6 py-2.5 text-sm font-bold text-white hover:bg-bleuone-light transition disabled:opacity-50">
        Commencer
      </button>
    </div>

    <div x-show="finished" x-cloak class="rounded-2xl bg-white shadow-lg p-6 text-center">
      <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Résultat</p>
      <p class="text-3xl font-bold text-bleuone" x-text="previousScore + ' / ' + previousTotal"></p>
      <p class="text-sm text-gray-500 mt-2">Vous avez terminé le jeu.</p>
      <button @click="start()" :disabled="!isActive"
              class="mt-4 rounded-xl bg-bleuone px-6 py-2.5 text-sm font-bold text-white hover:bg-bleuone-light transition disabled:opacity-50">
        Rejouer
      </button>
    </div>

    <div x-show="started && !finished" x-cloak class="rounded-2xl bg-white shadow-lg p-6">
      <div class="flex items-center justify-between gap-3 mb-4">
        <p class="text-sm text-gray-500">
          Composant <span x-text="currentIndex + 1"></span> / <span x-text="zones.length"></span>
        </p>
        <p class="text-sm font-bold text-bleuone">Score : <span x-text="score"></span></p>
      </div>

      <p class="text-lg font-semibold text-gray-800 mb-4">
        Trouvez : <span class="text-orangeone" x-text="zones[currentIndex] ? zones[currentIndex].label : ''"></span>
      </p>

      <div class="relative select-none rounded-xl overflow-hidden border border-gray-200 cursor-crosshair"
           x-ref="imageWrap"
           @click="handleClick($event)">
        <img src="{{ $session->image_url }}" alt="{{ $session->title }}" draggable="false" class="w-full h-auto block pointer-events-none">

        <template x-if="feedback">
          <div class="absolute pointer-events-none"
               :style="`left:${feedback.zone.x}%;top:${feedback.zone.y}%;width:${feedback.zone.w}%;height:${feedback.zone.h}%;`">
            <template x-if="feedback.zone.shape === 'triangle'">
              <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="absolute inset-0 h-full w-full overflow-visible">
                <polygon points="50,0 0,100 100,100" fill="rgba(1,198,156,0.2)" stroke="#01c69c" stroke-width="2" vector-effect="non-scaling-stroke" />
              </svg>
            </template>
            <template x-if="feedback.zone.shape !== 'triangle'">
              <div class="absolute inset-0 border-2 border-vertone bg-vertone/20" :class="shapeExtraClass(feedback.zone.shape)"></div>
            </template>
          </div>
        </template>
      </div>

      <template x-if="feedback">
        <div class="mt-4 rounded-xl px-4 py-3"
             :class="feedback.correct ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
          <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-semibold" :class="feedback.correct ? 'text-green-700' : 'text-red-700'">
              <span x-show="feedback.correct">Correct !</span>
              <span x-show="!feedback.correct">Raté ! La zone correcte est mise en évidence.</span>
            </p>
            <button @click="next()" :disabled="submitting"
                    class="shrink-0 rounded-lg bg-bleuone px-4 py-2 text-xs font-bold text-white hover:bg-bleuone-light transition disabled:opacity-50">
              <span x-show="currentIndex + 1 < zones.length">Suivant</span>
              <span x-show="currentIndex + 1 >= zones.length">Terminer</span>
            </button>
          </div>
          <template x-if="feedback.zone.description">
            <div class="mt-3 border-t pt-3" :class="feedback.correct ? 'border-green-200' : 'border-red-200'">
              <p class="text-sm font-bold text-gray-800" x-text="feedback.zone.label"></p>
              <p class="mt-1 text-sm text-gray-700 whitespace-pre-line" x-text="feedback.zone.description"></p>
            </div>
          </template>
        </div>
      </template>
    </div>
  </div>

  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
