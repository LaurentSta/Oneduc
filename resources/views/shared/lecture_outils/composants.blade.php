{{-- resources/views/shared/lecture_outils/composants.blade.php --}}
{{-- Terminée quand chaque élément a reçu une réponse (clic juste, clic raté ou « je ne sais pas »).
     Le plateau est rendu deux fois sur le même état : dans la leçon, et en pleine page. --}}
@php $idActivite = 'zone-clic-'.uniqid(); @endphp
<div x-data="{
         zones: @js(array_values($elements)),
         ordre: [],
         position: 0,
         bonnes: 0,
         resultat: null,
         pleinePage: false,
         init() {
             this.melanger();
         },
         melanger() {
             this.ordre = this.zones.map((zone, index) => index).sort(() => Math.random() - 0.5);
         },
         get zone() {
             return this.zones[this.ordre[this.position]] ?? null;
         },
         get fini() {
             return this.position >= this.zones.length;
         },
         cliquer(evenement) {
             if (this.resultat || this.fini) return;
             const cadre = evenement.currentTarget.getBoundingClientRect();
             const x = ((evenement.clientX - cadre.left) / cadre.width) * 100;
             const y = ((evenement.clientY - cadre.top) / cadre.height) * 100;
             this.repondre(this.contient(this.zone, x, y));
         },
         repondre(trouve) {
             if (trouve) this.bonnes++;
             this.resultat = { trouve };
         },
         contient(zone, x, y) {
             const dansCadre = x >= zone.x && x <= zone.x + zone.w && y >= zone.y && y <= zone.y + zone.h;
             if (zone.shape === 'circle' || zone.shape === 'oval') {
                 const dx = (x - (zone.x + zone.w / 2)) / (zone.w / 2);
                 const dy = (y - (zone.y + zone.h / 2)) / (zone.h / 2);
                 return dx * dx + dy * dy <= 1;
             }
             if (zone.shape === 'triangle') {
                 // Sommet en haut au centre : la largeur utile grandit en descendant.
                 const demiLargeur = (zone.w / 2) * ((y - zone.y) / zone.h);
                 return dansCadre && Math.abs(x - (zone.x + zone.w / 2)) <= demiLargeur;
             }
             return dansCadre;
         },
         suivant() {
             this.resultat = null;
             this.position++;
             if (this.fini) this.terminer();
         },
         recommencer() {
             this.melanger();
             this.position = 0;
             this.bonnes = 0;
             this.resultat = null;
         },
         agrandir() {
             this.pleinePage = true;
             this.$nextTick(() => document.getElementById(@js($idActivite.'-reduire'))?.focus());
         },
         reduire() {
             if (!this.pleinePage) return;
             this.pleinePage = false;
             document.getElementById(@js($idActivite.'-agrandir'))?.focus();
         },
     }"
     x-effect="document.documentElement.classList.toggle('overflow-hidden', pleinePage)"
     @keydown.escape.window="reduire()">
    @include('shared.lecture_outils._composants_plateau', ['pleinePage' => false])

    {{-- Sorti de la leçon (x-teleport) : un parent animé empêcherait le plein cadre. --}}
    <template x-teleport="body">
        <div x-show="pleinePage" x-cloak role="dialog" aria-modal="true" aria-label="Activité en pleine page"
             class="fixed inset-0 z-[100] overflow-auto bg-white p-4 sm:p-8">
            <div class="mx-auto max-w-6xl">
                @include('shared.lecture_outils._composants_plateau', ['pleinePage' => true])
            </div>
        </div>
    </template>
</div>
