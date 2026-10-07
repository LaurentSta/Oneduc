@extends('frontend.master')

@section('title', 'Onéduc - Plateforme de formation pour l\'inclusion numérique')
@section('description', 'Onéduc est une plateforme de formation gratuite pensée pour l\'inclusion numérique : parcours, quiz, SCORM et suivi pédagogique, sans installation ni carte bancaire.')

@section('home')
{{-- HERO SECTION --}}
  <section class="relative overflow-hidden bg-white pt-6 pb-20 lg:pt-8">
    <div data-akene-hero class="oneduc-akene-stage" aria-hidden="true">
      <img
        src="{{ asset('frontend/assets/img/front-pages/animations/akene/logo.svg') }}"
        alt=""
        class="oneduc-akene-fallback oneduc-akene-fallback--hidden"
        aria-hidden="true">
    </div>

    <div class="relative z-10 max-w-[1248px] mx-auto px-6">
      <div class="flex flex-col lg:flex-row items-center gap-12" >
        
        <div class="oneduc-hero-akene w-full lg:w-1/2 z-10 justify-center md:justify-start">
          <div class="relative z-10 space-y-6">
            <h1 class="font-raleway text-bleuone">
              <span class="text-titre block">Outil Numérique Éducatif</span>
              {{-- Sous-titre explicatif pour les nouveaux visiteurs --}}
              <span class="text-2xl font-varela text-gray-500 block mt-3">Une plateforme simple pour former et accompagner vos apprenants</span>
            </h1>

            <p class="prose-oneduc text-xl max-w-xl text-gray-600">
              Créez des parcours de formation sur-mesure, adaptés aux besoins et aux niveaux de chaque apprenant, sans barrière technique.
            </p>

            {{-- Deux portes d'entrée claires : inscription et presentation du projet --}}
            <div class="flex flex-wrap gap-4 pt-4">
              <a href="{{ route('formateur.inscription.form') }}" class="btn-oneduc">
                Je suis formateur
              </a>
              <a href="{{ route('projet') }}" class="btn-oneduc-outline">
                Découvrir le projet
              </a>
            </div>
            {{-- Texte rassurant sous les CTA --}}
            <p class="text-sm text-gray-500 mt-2">Inscription en 2 minutes — aucune carte bancaire demandée.</p>
          </div>
        </div>

        <div class="w-full lg:w-1/2 relative">
          {{-- Correction layout : suppression du w-1/2 interne qui réduisait la vidéo à 25% de la largeur --}}
          <div class="w-full flex justify-center items-center py-4 lg:py-0">
            {{--
              La vidéo YouTube n'est chargée qu'après consentement aux cookies
              (ou sur clic explicite, qui vaut action volontaire) : avant ça,
              seule une image statique (i.ytimg.com, sans cookie ni script
              tiers) est affichée. Script de chargement en bas de page
              (@push('scripts')).
            --}}
            <div
              class="aspect-video w-full max-w-lg rounded-xl overflow-hidden shadow-lg"
              data-oneduc-consent-video
              data-video-id="Bw4_SlnqZj8"
              data-video-title="Présentation de la plateforme Onéduc"
            >
              <button
                type="button"
                class="group relative block h-full w-full"
                data-oneduc-consent-video-play
                aria-label="Lancer la vidéo de présentation Onéduc"
              >
                <img
                  src="https://i.ytimg.com/vi/Bw4_SlnqZj8/hqdefault.jpg"
                  alt=""
                  loading="lazy"
                  class="h-full w-full object-cover"
                >
                <span class="absolute inset-0 flex items-center justify-center bg-black/30 transition group-hover:bg-black/45">
                  <span class="flex h-16 w-16 items-center justify-center rounded-full bg-white/90 shadow-lg transition group-hover:scale-105">
                    <svg class="ml-1 h-7 w-7 text-bleuone" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                      <path d="M6 4l12 6-12 6V4z" />
                    </svg>
                  </span>
                </span>
              </button>
            </div>
          </div>
        </div>
    </div>
  </section>

{{-- SECTION "COMMENT ÇA MARCHE ?" --}}
<section class="relative overflow-hidden bg-gradient-to-br from-bleuone-dark via-bleuone to-[#2b1720] py-20 text-white md:py-24">
  <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-11.svg') }}" alt="" class="absolute left-4 top-16 w-24 -rotate-12 opacity-16 mix-blend-screen md:left-16 md:w-36">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-15.svg') }}" alt="" class="absolute left-[18%] top-36 hidden w-14 rotate-[22deg] opacity-14 mix-blend-screen md:block">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-18.svg') }}" alt="" class="absolute left-[7%] bottom-24 w-12 rotate-45 opacity-12 mix-blend-screen md:w-16">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-21.svg') }}" alt="" class="absolute right-[16%] top-24 hidden w-16 -rotate-[18deg] opacity-12 mix-blend-screen lg:block">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-23.svg') }}" alt="" class="absolute -right-4 bottom-8 w-28 rotate-12 opacity-10 mix-blend-screen md:right-10 md:w-44">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-14.svg') }}" alt="" class="absolute right-[34%] bottom-20 hidden w-10 rotate-[36deg] opacity-10 mix-blend-screen lg:block">
  </div>

  <div class="relative z-10 max-w-[1248px] mx-auto px-6">
    <div class="mx-auto max-w-4xl text-center">
      <h2 class="font-raleway text-[34px] font-extrabold leading-tight md:text-[40px]">Comment ça marche ?</h2>
      <p class="mx-auto mt-6 max-w-3xl font-lisible text-lg leading-relaxed text-white/75 md:text-2xl">
        En quelques minutes, vous êtes prêt à former — sans installation.
      </p>
    </div>

    <div class="mt-16 grid grid-cols-1 gap-12 font-lisible md:mt-20 lg:grid-cols-3 lg:gap-7">
      <article class="group">
        <div class="relative mx-auto max-w-md lg:max-w-none">
          <div class="absolute inset-0 translate-x-3 translate-y-3 rotate-[-2deg] rounded-[1.75rem] border-4 border-orangeone/85 transition duration-500 group-hover:rotate-0"></div>
          <div class="relative overflow-hidden rounded-[1.75rem] border border-white/70 bg-white shadow-2xl shadow-black/25">
            <div class="flex h-11 items-center gap-2 border-b border-slate-100 bg-slate-50 px-5">
              <span class="h-2.5 w-2.5 rounded-full bg-orangeone"></span>
              <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
              <span class="h-2.5 w-2.5 rounded-full bg-vertone"></span>
            </div>
            <div class="relative min-h-[250px] p-7 text-bleuone">
              <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-11.svg') }}" alt="" aria-hidden="true" class="absolute right-5 top-5 w-12 opacity-35">
              <div class="space-y-4 pr-10">
                <div>
                  <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Email</p>
                  <div class="h-11 rounded-lg border border-slate-200 bg-slate-50"></div>
                </div>
                <div>
                  <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Structure</p>
                  <div class="h-11 rounded-lg border border-slate-200 bg-slate-50"></div>
                </div>
                <div>
                  <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.16em] text-slate-400">Mot de passe</p>
                  <div class="h-11 rounded-lg border border-slate-200 bg-slate-50"></div>
                </div>
                <div class="inline-flex rounded-full bg-orangeone px-5 py-3 text-sm font-semibold text-white">Créer mon compte</div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-8 text-center lg:text-left">
          <p class="font-varela text-sm font-semibold uppercase tracking-[0.22em] text-orangeone">Étape 01</p>
          <h3 class="mt-3 font-raleway text-2xl font-semibold text-white">Créez votre compte</h3>
          <p class="mt-3 text-base leading-relaxed text-white/72">Inscrivez-vous gratuitement en 2 minutes. Aucune installation nécessaire — tout se passe dans votre navigateur.</p>
        </div>
      </article>

      <article class="group lg:translate-y-8">
        <div class="relative mx-auto max-w-md lg:max-w-none">
          <div class="absolute inset-0 translate-x-3 translate-y-3 rotate-[2deg] rounded-[1.75rem] border-4 border-orangeone/85 transition duration-500 group-hover:rotate-0"></div>
          <div class="relative overflow-hidden rounded-[1.75rem] border border-white/70 bg-white shadow-2xl shadow-black/25">
            <div class="flex h-11 items-center gap-2 border-b border-slate-100 bg-slate-50 px-5">
              <span class="h-2.5 w-2.5 rounded-full bg-orangeone"></span>
              <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
              <span class="h-2.5 w-2.5 rounded-full bg-vertone"></span>
            </div>
            <div class="relative min-h-[250px] p-7 text-bleuone">
              <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-15.svg') }}" alt="" aria-hidden="true" class="absolute right-5 top-5 w-12 opacity-35">
              <p class="mb-5 pr-12 text-sm font-bold uppercase tracking-[0.14em] text-bleuone">Modules du parcours</p>
              <div class="space-y-3">
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                  <span class="h-9 w-9 rounded-lg bg-bleuone/10"></span>
                  <span class="h-3 flex-1 rounded-full bg-slate-200"></span>
                </div>
                <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                  <span class="h-9 w-9 rounded-lg bg-orangeone/10"></span>
                  <span class="h-3 flex-1 rounded-full bg-slate-200"></span>
                </div>
                <div class="flex h-16 items-center justify-center rounded-xl border-2 border-dashed border-orangeone/50 bg-orangeone/5 text-sm font-semibold text-orangeone">+ Ajouter</div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-8 text-center lg:text-left">
          <p class="font-varela text-sm font-semibold uppercase tracking-[0.22em] text-orangeone">Étape 02</p>
          <h3 class="mt-3 font-raleway text-2xl font-semibold text-white">Préparez votre formation</h3>
          <p class="mt-3 text-base leading-relaxed text-white/72">Créez vos groupes, ajoutez vos contenus et organisez votre parcours. Les outils sont simples et guidés pas à pas.</p>
        </div>
      </article>

      <article class="group">
        <div class="relative mx-auto max-w-md lg:max-w-none">
          <div class="absolute inset-0 translate-x-3 translate-y-3 rotate-[-2deg] rounded-[1.75rem] border-4 border-orangeone/85 transition duration-500 group-hover:rotate-0"></div>
          <div class="relative overflow-hidden rounded-[1.75rem] border border-white/70 bg-white shadow-2xl shadow-black/25">
            <div class="flex h-11 items-center gap-2 border-b border-slate-100 bg-slate-50 px-5">
              <span class="h-2.5 w-2.5 rounded-full bg-orangeone"></span>
              <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
              <span class="h-2.5 w-2.5 rounded-full bg-vertone"></span>
            </div>
            <div class="relative min-h-[250px] p-7 text-bleuone">
              <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-23.svg') }}" alt="" aria-hidden="true" class="absolute right-5 top-5 w-12 opacity-35">
              <p class="mb-3 text-sm font-bold uppercase tracking-[0.14em] text-slate-400">Code d'accès</p>
              <div class="inline-flex rounded-full border border-bleuone/15 bg-bleuone/10 px-5 py-2 font-varela text-xl font-bold tracking-[0.16em] text-bleuone">ON-7K2P</div>
              <p class="mt-6 mb-4 text-xs font-bold uppercase tracking-[0.14em] text-slate-400">4 apprenants connectés</p>
              <div class="space-y-3">
                <div class="flex items-center gap-3"><span class="h-3 w-3 rounded-full bg-vertone"></span><span class="h-3 flex-1 rounded-full bg-slate-200"></span></div>
                <div class="flex items-center gap-3"><span class="h-3 w-3 rounded-full bg-orangeone"></span><span class="h-3 flex-1 rounded-full bg-slate-200"></span></div>
                <div class="flex items-center gap-3"><span class="h-3 w-3 rounded-full bg-bleuone"></span><span class="h-3 flex-1 rounded-full bg-slate-200"></span></div>
                <div class="flex items-center gap-3"><span class="h-3 w-3 rounded-full bg-slate-300"></span><span class="h-3 flex-1 rounded-full bg-slate-200"></span></div>
              </div>
            </div>
          </div>
        </div>
        <div class="mt-8 text-center lg:text-left">
          <p class="font-varela text-sm font-semibold uppercase tracking-[0.22em] text-orangeone">Étape 03</p>
          <h3 class="mt-3 font-raleway text-2xl font-semibold text-white">Invitez vos apprenants</h3>
          <p class="mt-3 text-base leading-relaxed text-white/72">Donnez un code d'accès à vos apprenants. Ils rejoignent votre formation en quelques secondes, sans compte à créer.</p>
        </div>
      </article>
    </div>
  </div>
</section>

<script>
  window.oneducProductCarousel = function () {
    return {
      current: 0,
      timer: null,
      textVisible: true,
      slides: [
        {
          tag: "Côté formateur",
          role: "f",
          label: "Suivi stagiaire",
          title: "Suivi détaillé de chaque stagiaire",
          desc: "Visualisez l'assiduité, le rythme d'apprentissage et le journal d'activité unifié — leçons, quiz et vidéos rassemblés sur une chronologie claire.",
          bullets: [
            "Indicateurs lisibles : jours actifs, présence, taux d'acquisition.",
            "Détection des points de blocage.",
            "Vue par groupe ou par stagiaire individuel."
          ]
        },
        {
          tag: "Côté formateur",
          role: "f",
          label: "Outils numériques",
          title: "Outils d'animation prêts à l'emploi",
          desc: "Nuages de mots, quiz en direct, tableau blanc, sondages et questions : les outils utiles sont regroupés au même endroit.",
          bullets: [
            "Bibliothèque organisée par usage.",
            "Activités synchrones ou asynchrones.",
            "Interface pensée pour animer sans préparation technique."
          ]
        },
        {
          tag: "Côté formateur",
          role: "f",
          label: "Groupes",
          title: "Gestion simple des groupes",
          desc: "Créez vos groupes, associez des modules et suivez les périodes de formation depuis un écran clair.",
          bullets: [
            "Modules associés visibles d'un coup d'œil.",
            "Périodes de formation bien délimitées.",
            "Modification ou archivage rapide."
          ]
        },
        {
          tag: "Côté apprenant",
          role: "s",
          label: "Résultats",
          title: "Mes résultats, en toute clarté",
          desc: "L'apprenant retrouve sa progression, son taux de réussite et le détail de ses tentatives sans tableaux complexes.",
          bullets: [
            "Répartition visuelle des réponses.",
            "Statut acquis ou à revoir par question.",
            "Historique des tentatives avec dates précises."
          ]
        },
        {
          tag: "Côté apprenant",
          role: "s",
          label: "Objectifs",
          title: "Des objectifs pédagogiques explicites",
          desc: "Chaque chapitre commence par les objectifs à atteindre, accompagnés d'une présentation claire du formateur.",
          bullets: [
            "Objectifs structurés par compétence.",
            "Vidéo d'introduction possible.",
            "Repères simples avant de commencer."
          ]
        },
        {
          tag: "Côté apprenant",
          role: "s",
          label: "Lecture",
          title: "Une lecture de leçon sans distraction",
          desc: "L'interface de lecture laisse la place au contenu, avec un sommaire et une progression toujours lisibles.",
          bullets: [
            "Sommaire visible avec statut par leçon.",
            "Progression sauvegardée.",
            "Mode plein écran disponible."
          ]
        }
      ],
      init() {
        this.start();
      },
      start() {
        this.stop();
        this.timer = window.setInterval(() => this.next(), 6500);
      },
      stop() {
        if (this.timer) window.clearInterval(this.timer);
      },
      changeSlide(index, restartTimer = false) {
        if (index === this.current) return;

        this.textVisible = false;
        window.setTimeout(() => {
          this.current = index;
          this.textVisible = true;
        }, 170);

        if (restartTimer) this.start();
      },
      go(index) {
        this.changeSlide(index, true);
      },
      next() {
        this.changeSlide((this.current + 1) % this.slides.length);
      },
      prev() {
        this.changeSlide((this.current - 1 + this.slides.length) % this.slides.length, true);
      }
    }
  };
</script>

{{-- SECTION APERÇU PRODUIT --}}
<section
  class="relative overflow-hidden bg-white py-20 md:py-28"
  x-data="oneducProductCarousel()"
  x-on:mouseenter="stop()"
  x-on:mouseleave="start()"
>
  <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    <div class="absolute inset-x-0 top-0 h-[360px] bg-gradient-to-b from-orangeone/5 via-white to-white"></div>
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-15.svg') }}" alt="" class="absolute right-8 top-12 w-24 rotate-[20deg] opacity-25 md:right-20 md:w-32">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-23.svg') }}" alt="" class="absolute -left-4 bottom-28 w-20 -rotate-12 opacity-20 md:left-10 md:w-28">
  </div>

  <div class="relative z-10 max-w-[1248px] mx-auto px-6">
    <div class="mx-auto mb-14 max-w-3xl text-center md:mb-16">
      <h2 class="text-center font-raleway text-[34px] font-extrabold leading-tight text-orangeone md:text-[40px]">À quoi ressemble la plateforme ?</h2>
      <p class="mx-auto mt-4 max-w-2xl text-center text-gray-600 text-lg font-lisible">
        Six aperçus formateur et apprenant pour comprendre l'interface en quelques secondes.
      </p>
    </div>

    <div class="grid items-start gap-12 lg:grid-cols-[1.55fr_1fr] lg:gap-14">
      <div class="relative">
        <div class="relative">
        <div class="absolute -inset-3 rotate-[-1.2deg] rounded-[1.4rem] border-[3px] border-orangeone"></div>

        <div class="relative aspect-[1280/800] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-bleuone/20">
          <div x-cloak x-show="current === 0" x-transition.opacity.duration.500ms class="absolute inset-0 bg-slate-50">
            <div class="flex h-full">
              <aside class="hidden w-[24%] bg-bleuone p-5 text-white md:block">
                <p class="font-varela text-xl font-bold">Onéduc</p>
                <div class="mt-8 space-y-3 text-sm text-white/75">
                  <div class="rounded-lg bg-white/12 px-3 py-2 text-white">Tableau de bord</div>
                  <div class="px-3 py-2">Stagiaires</div>
                  <div class="px-3 py-2">Groupes</div>
                  <div class="px-3 py-2">Résultats</div>
                </div>
              </aside>
              <div class="flex-1 p-5 md:p-8">
                <p class="text-sm font-semibold text-slate-500">Bonjour Camille</p>
                <h3 class="mt-1 font-raleway text-2xl font-bold text-bleuone">Suivi stagiaire</h3>
                <div class="mt-5 grid grid-cols-3 gap-3">
                  <div class="rounded-xl bg-white p-4"><p class="text-2xl font-bold text-bleuone">28</p><p class="text-xs text-slate-500">Stagiaires</p></div>
                  <div class="rounded-xl bg-white p-4"><p class="text-2xl font-bold text-vertone">78%</p><p class="text-xs text-slate-500">Complétion</p></div>
                  <div class="rounded-xl bg-white p-4"><p class="text-2xl font-bold text-orangeone">6</p><p class="text-xs text-slate-500">Alertes</p></div>
                </div>
                <div class="mt-5 space-y-3">
                  @foreach ([82, 34, 100, 58] as $progress)
                    <div class="flex items-center gap-3 rounded-xl bg-white p-3">
                      <span class="h-9 w-9 rounded-full bg-bleuone/10"></span>
                      <span class="h-3 flex-1 rounded-full bg-slate-200"><i class="block h-3 rounded-full {{ $progress < 50 ? 'bg-orangeone' : 'bg-vertone' }}" style="width: {{ $progress }}%"></i></span>
                      <span class="w-10 text-right text-xs font-bold text-slate-500">{{ $progress }}%</span>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>

          <div x-cloak x-show="current === 1" x-transition.opacity.duration.500ms class="absolute inset-0 bg-[#f8f7fa] p-5 md:p-8">
            <div class="rounded-2xl bg-white p-5 h-full">
              <h3 class="font-raleway text-2xl font-bold text-bleuone">Outils numériques</h3>
              <p class="mt-1 text-sm text-slate-500">Interaction, collaboration et animation</p>
              <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3">
                @foreach (['Nuage de mots', 'Quiz live', 'Tableau blanc', 'Sondage', 'Questions', 'Roue'] as $tool)
                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <span class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-orangeone/10 text-orangeone">
                      <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg>
                    </span>
                    <p class="font-varela text-sm font-semibold text-bleuone">{{ $tool }}</p>
                    <p class="mt-2 h-2 rounded-full bg-slate-200"></p>
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          <div x-cloak x-show="current === 2" x-transition.opacity.duration.500ms class="absolute inset-0 bg-slate-50 p-5 md:p-8">
            <div class="h-full rounded-2xl bg-white p-5">
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="font-raleway text-2xl font-bold text-bleuone">Mes groupes</h3>
                  <p class="text-sm text-slate-500">Modules, périodes et stagiaires associés</p>
                </div>
                <span class="rounded-full bg-orangeone px-4 py-2 text-sm font-semibold text-white">Nouveau groupe</span>
              </div>
              <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
                @foreach ([['Alpha numérique', '12 stagiaires', '82%'], ['Clavier débutant', '8 stagiaires', '64%'], ['Remise à niveau', '15 stagiaires', '91%'], ['Bureautique', '10 stagiaires', '47%']] as $group)
                  <div class="grid grid-cols-[1fr_auto_auto] items-center gap-4 border-b border-slate-100 bg-white px-4 py-4 last:border-b-0">
                    <div>
                      <p class="font-semibold text-bleuone">{{ $group[0] }}</p>
                      <p class="text-xs text-slate-500">Période active</p>
                    </div>
                    <span class="hidden text-sm text-slate-500 md:inline">{{ $group[1] }}</span>
                    <span class="rounded-full bg-vertone/10 px-3 py-1 text-xs font-bold text-vertone">{{ $group[2] }}</span>
                  </div>
                @endforeach
              </div>
            </div>
          </div>

          <div x-cloak x-show="current === 3" x-transition.opacity.duration.500ms class="absolute inset-0 bg-[#f8f7fa] p-5 md:p-8">
            <div class="grid h-full gap-5 md:grid-cols-[0.9fr_1.1fr]">
              <div class="rounded-2xl bg-white p-6">
                <p class="text-sm font-semibold text-slate-500">Mes résultats</p>
                <div class="mx-auto mt-8 flex h-40 w-40 items-center justify-center rounded-full border-[18px] border-vertone text-3xl font-bold text-bleuone">84%</div>
                <p class="mt-6 text-center text-sm text-slate-500">Taux de réussite global</p>
              </div>
              <div class="rounded-2xl bg-white p-6">
                <h3 class="font-raleway text-2xl font-bold text-bleuone">Détail des tentatives</h3>
                <div class="mt-5 space-y-3">
                  @foreach ([['Copier un texte', 'Acquis', true], ['Envoyer un email', 'À revoir', false], ['Utiliser un raccourci', 'Acquis', true]] as $result)
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 p-4">
                      <span class="font-semibold text-slate-700">{{ $result[0] }}</span>
                      <span class="rounded-full px-3 py-1 text-xs font-bold {{ $result[2] ? 'bg-vertone/10 text-vertone' : 'bg-orangeone/10 text-orangeone' }}">{{ $result[1] }}</span>
                    </div>
                  @endforeach
                </div>
              </div>
            </div>
          </div>

          <div x-cloak x-show="current === 4" x-transition.opacity.duration.500ms class="absolute inset-0 bg-slate-50 p-5 md:p-8">
            <div class="grid h-full gap-5 md:grid-cols-[1.05fr_0.95fr]">
              <div class="rounded-2xl bg-white p-6">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-orangeone">Chapitre</p>
                <h3 class="mt-2 font-raleway text-3xl font-bold text-bleuone">Objectifs pédagogiques</h3>
                <div class="mt-6 space-y-3">
                  @foreach (['Identifier les touches principales', 'Saisir un court texte', 'Utiliser deux raccourcis clavier'] as $goal)
                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                      <span class="flex h-7 w-7 items-center justify-center rounded-full bg-vertone/10 text-vertone">✓</span>
                      <span class="text-sm font-semibold text-slate-700">{{ $goal }}</span>
                    </div>
                  @endforeach
                </div>
              </div>
              <div class="rounded-2xl bg-bleuone p-5 text-white">
                <div class="flex h-full flex-col items-center justify-center rounded-xl border border-white/15 bg-white/10">
                  <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-orangeone">
                    <svg class="ml-1 h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                  </span>
                  <p class="font-varela text-lg font-semibold">Vidéo du formateur</p>
                </div>
              </div>
            </div>
          </div>

          <div x-cloak x-show="current === 5" x-transition.opacity.duration.500ms class="absolute inset-0 bg-[#f8f7fa]">
            <div class="flex h-full">
              <aside class="hidden w-[28%] border-r border-slate-200 bg-white p-5 md:block">
                <p class="font-varela font-semibold text-bleuone">Sommaire</p>
                <div class="mt-5 space-y-3">
                  @foreach (['Introduction', 'Lire le contenu', 'Question', 'Synthèse'] as $lesson)
                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-600">{{ $lesson }}</div>
                  @endforeach
                </div>
              </aside>
              <div class="flex-1 p-6 md:p-10">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-orangeone">Leçon</p>
                <h3 class="mt-2 font-raleway text-3xl font-bold text-bleuone">Utiliser le clavier</h3>
                <div class="mt-8 space-y-4">
                  <p class="h-3 w-full rounded-full bg-slate-200"></p>
                  <p class="h-3 w-11/12 rounded-full bg-slate-200"></p>
                  <p class="h-3 w-4/5 rounded-full bg-slate-200"></p>
                </div>
                <div class="mt-8 rounded-2xl border border-orangeone/20 bg-orangeone/10 p-5">
                  <p class="font-semibold text-orangeone">Le saviez-vous ?</p>
                  <p class="mt-3 h-2 rounded-full bg-orangeone/25"></p>
                  <p class="mt-2 h-2 w-3/4 rounded-full bg-orangeone/25"></p>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="pointer-events-none absolute inset-y-0 -left-5 -right-5 z-20 flex items-center justify-between md:-left-8 md:-right-8">
          <button type="button" x-on:click="prev()" class="pointer-events-auto flex h-14 w-14 items-center justify-center rounded-full border-4 border-white bg-orangeone text-white shadow-xl shadow-orangeone/25 transition hover:scale-105 hover:bg-orangeone-hover focus:outline-none focus:ring-4 focus:ring-orangeone/25" aria-label="Aperçu précédent">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6"/></svg>
          </button>
          <button type="button" x-on:click="next(); start()" class="pointer-events-auto flex h-14 w-14 items-center justify-center rounded-full border-4 border-white bg-orangeone text-white shadow-xl shadow-orangeone/25 transition hover:scale-105 hover:bg-orangeone-hover focus:outline-none focus:ring-4 focus:ring-orangeone/25" aria-label="Aperçu suivant">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>
          </button>
        </div>
        </div>

        <div class="mt-7 grid grid-cols-2 items-start gap-2 rounded-2xl border border-slate-200/80 bg-white/75 p-2 shadow-sm shadow-slate-200/60 backdrop-blur sm:grid-cols-3 lg:grid-cols-6">
          <template x-for="(slide, index) in slides" :key="slide.title">
            <button
              type="button"
              x-on:click="go(index)"
              class="group relative min-h-[4.5rem] overflow-hidden rounded-xl px-3.5 py-3 text-left transition duration-200 focus:outline-none focus:ring-2 focus:ring-orangeone/40"
              :class="current === index ? 'bg-white text-bleuone shadow-md shadow-slate-200/80 ring-1 ring-slate-200' : 'text-slate-500 hover:bg-white/80 hover:text-bleuone'"
            >
              <span class="absolute inset-x-3 top-0 h-1 rounded-full transition" :class="current === index ? (slide.role === 'f' ? 'bg-orangeone' : 'bg-vertone') : 'bg-transparent'"></span>
              <span class="flex items-center gap-2">
                <span class="h-2 w-2 flex-none rounded-full" :class="slide.role === 'f' ? 'bg-orangeone' : 'bg-vertone'"></span>
                <span class="truncate text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400" x-text="slide.role === 'f' ? 'Formateur' : 'Apprenant'"></span>
              </span>
              <span class="mt-2 block font-varela text-sm font-normal leading-tight" x-text="slide.label"></span>
            </button>
          </template>
        </div>
      </div>

      <div class="self-start lg:pl-2">
        <div class="min-h-[360px]">
          <div
            x-show="textVisible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-3"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            :key="current"
          >
            <h3 class="font-varela text-3xl font-normal leading-tight text-bleuone" x-text="slides[current].title"></h3>
            <span class="mt-5 inline-flex rounded-full px-4 py-2 text-xs font-bold uppercase tracking-[0.16em]" :class="slides[current].role === 'f' ? 'bg-orangeone/10 text-orangeone' : 'bg-vertone/10 text-vertone'" x-text="slides[current].tag"></span>
            <p class="mt-5 font-lisible text-base leading-relaxed text-slate-600 md:text-lg" x-text="slides[current].desc"></p>
            <ul class="mt-6 space-y-4 font-lisible text-base leading-relaxed text-slate-600 md:text-lg">
              <template x-for="bullet in slides[current].bullets" :key="bullet">
                <li class="flex gap-3">
                  <span class="mt-2 h-3.5 w-3.5 flex-none rounded-full bg-orangeone/15 after:block after:h-1.5 after:w-1.5 after:translate-x-1 after:translate-y-1 after:rounded-full after:bg-orangeone"></span>
                  <span x-text="bullet"></span>
                </li>
              </template>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

	 {{-- SECTION "C'EST QUOI ?" --}}
	  <section class="relative overflow-hidden bg-gray-50 py-20">
	    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-11.svg') }}" alt="" aria-hidden="true" class="absolute left-8 top-14 w-16 -rotate-12 opacity-10 md:w-24">
	    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-23.svg') }}" alt="" aria-hidden="true" class="absolute right-10 bottom-12 w-20 rotate-12 opacity-10 md:w-28">
	    <div class="mx-auto max-w-[1248px] px-6">
	      <div class="grid items-center gap-14 lg:grid-cols-2">
	        <div class="group relative mx-auto w-full max-w-xl">
	          <div class="absolute -inset-3 rounded-[28px] border-2 border-orangeone/35 bg-orangeone/5 rotate-2 transition-transform duration-500 group-hover:rotate-0"></div>
	          <div class="relative rounded-[28px] bg-white p-5 shadow-xl shadow-slate-200/80 transition-transform duration-500 group-hover:-translate-y-2">
	            <img src="{{ asset('frontend/assets/img/front-pages/landing-page/LivreOneduc.svg') }}"
	                 alt="Illustration LMS Oneduc"
	                 class="w-full rounded-2xl">
	          </div>
	        </div>
	
	        <div class="space-y-7">
	          <div>
	            <h2 class="flex items-center gap-4 font-raleway text-[34px] font-extrabold leading-tight text-bleuone md:text-[40px]">
		              <img src="{{ asset('frontend/assets/img/front-pages/icons/etoile8.gif') }}" width="60" height="61" alt="" aria-hidden="true" class="h-[60px] w-[60px] flex-none object-contain">
	              <span>Onéduc, c'est quoi ?</span>
	            </h2>
	          </div>
	
	          <div class="max-w-[62ch] space-y-5 font-lisible text-lg leading-relaxed text-slate-600">
	            <p class="border-l-4 border-orangeone pl-5 text-xl font-semibold leading-snug text-bleuone">
	              Une plateforme de formation en ligne conçue par et pour le terrain.
	            </p>
	            <p>
	              Onéduc facilite l'organisation de parcours accessibles. Pensée pour accompagner des personnes qui débutent avec l'ordinateur, elle permet aux formateurs de se concentrer sur l'essentiel : <strong>l'accompagnement pédagogique</strong>.
	            </p>
	            <ul class="grid gap-3 pt-2 text-base text-slate-600 sm:grid-cols-3">
	              <li class="flex items-start gap-2">
	                <span class="mt-2.5 h-1.5 w-1.5 flex-none rounded-full bg-orangeone"></span>
	                <span>Créer des parcours</span>
	              </li>
	              <li class="flex items-start gap-2">
	                <span class="mt-2.5 h-1.5 w-1.5 flex-none rounded-full bg-orangeone"></span>
	                <span>Suivre les progrès</span>
	              </li>
	              <li class="flex items-start gap-2">
	                <span class="mt-2.5 h-1.5 w-1.5 flex-none rounded-full bg-orangeone"></span>
	                <span>Adapter le rythme</span>
	              </li>
	            </ul>
	          </div>
	        </div>
	      </div>
	    </div>
	  </section>
	
	  
	<section class="relative overflow-hidden bg-white py-20">
	  <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-21.svg') }}" alt="" aria-hidden="true" class="absolute left-[6%] bottom-12 w-14 rotate-[24deg] opacity-10 md:w-20">
	  <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-14.svg') }}" alt="" aria-hidden="true" class="absolute right-[8%] top-16 w-12 -rotate-[18deg] opacity-10 md:w-16">
	  <div class="mx-auto max-w-[1248px] px-6">
	    <div class="grid items-center gap-14 lg:grid-cols-2">
	
	      <div class="space-y-7 lg:order-1">
	        <div>
	          <h2 class="flex items-center gap-4 font-raleway text-[34px] font-extrabold leading-tight text-bleuone md:text-[40px]">
		            <img src="{{ asset('frontend/assets/img/front-pages/icons/etoile8.gif') }}" width="60" height="61" alt="" aria-hidden="true" class="h-[60px] w-[60px] flex-none object-contain">
	            <span>À qui s'adresse Onéduc ?</span>
	          </h2>
	        </div>
	
	        <div class="max-w-[64ch] space-y-5 font-lisible text-lg leading-relaxed text-slate-600">
	          <p class="border-l-4 border-orangeone pl-5 text-xl font-semibold leading-snug text-bleuone">
	            Onéduc s'adresse aux formateurs, éducateurs et professionnels de l'accompagnement qui souhaitent un outil simple pour organiser et adapter leurs formations.
	          </p>
	
	          <p>
	            La plateforme est particulièrement adaptée aux contextes où les apprenants
	            <strong>débutent avec l'ordinateur</strong> ou ont peu d'expérience du numérique.
	            Elle permet de créer des parcours progressifs, à leur rythme.
	          </p>
	
	          <p>
	            Onéduc accompagne aussi bien des <strong>formateurs individuels</strong> que des
	            <strong>structures de formation</strong> souhaitant disposer d'un outil fiable pour
	            <strong>suivre les apprenants</strong>, <strong>ajuster les parcours</strong> et
	            <strong>renforcer l'accompagnement pédagogique</strong>.
	          </p>
	        </div>
	      </div>
	
	      <div class="group relative mx-auto w-full max-w-xl lg:order-2">
	        <div class="absolute -inset-3 rounded-[28px] border-2 border-vertone/35 bg-vertone/5 -rotate-2 transition-transform duration-500 group-hover:rotate-0"></div>
	        <div class="relative rounded-[28px] bg-white p-5 shadow-xl shadow-slate-200/80 transition-transform duration-500 group-hover:-translate-y-2">
	          <img src="{{ asset('frontend/assets/img/front-pages/landing-page/LandingPage2.svg') }}"
	               alt="Illustration Oneduc"
	               class="w-full rounded-2xl object-cover">
	        </div>
	      </div>
	
	    </div>
	  </div>
	</section>

  <section class="relative overflow-hidden bg-gray-50 py-20">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-18.svg') }}" alt="" aria-hidden="true" class="absolute -left-8 top-20 w-24 -rotate-12 opacity-10 md:left-10 md:w-32">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-21.svg') }}" alt="" aria-hidden="true" class="absolute right-6 bottom-16 w-20 rotate-[18deg] opacity-10 md:right-16 md:w-28">
    <div class="relative mx-auto max-w-[1248px] px-6">

      <div class="mx-auto mb-12 max-w-3xl text-center">
        <h2 class="inline-flex items-center justify-center gap-4 font-raleway text-[34px] font-extrabold leading-tight text-bleuone md:text-[40px]">
	          <img src="{{ asset('frontend/assets/img/front-pages/icons/etoile8.gif') }}" width="60" height="61" alt="" aria-hidden="true" class="h-[60px] w-[60px] flex-none object-contain">
          <span>Les avantages de la formation avec Onéduc.fr</span>
        </h2>
        <p class="mt-5 font-lisible text-lg leading-relaxed text-slate-600">
          Des fonctionnalités pensées pour gagner du temps, accompagner plus finement les apprenants et garder une progression claire.
        </p>
      </div>

      <div class="grid grid-cols-1 gap-4 font-lisible text-slate-700 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['src' => 'iconeEntete-04.svg', 'title' => 'Parcours adaptables', 'desc' => 'Organisez les modules selon les besoins de chaque groupe, avec une progression claire et évolutive.', 'soft' => 'bg-orangeone/10', 'line' => 'bg-orangeone'],
            ['src' => 'iconeEntete-06.svg', 'title' => 'Prise en main simple', 'desc' => 'Une interface pensée pour les formateurs non techniciens. Pas besoin de formation informatique pour démarrer.', 'soft' => 'bg-bleuone/10', 'line' => 'bg-bleuone'],
            ['src' => 'iconeEntete-02.svg', 'title' => 'Apprenants débutants', 'desc' => 'Ajustez les rythmes et les contenus pour répondre aux niveaux et aux situations de chaque apprenant.', 'soft' => 'bg-vertone/10', 'line' => 'bg-vertone'],
            ['src' => 'iconeEntete-03.svg', 'title' => 'Suivi pédagogique', 'desc' => 'Visualisez la progression, les acquis et les points à renforcer, sans tableaux complexes.', 'soft' => 'bg-orangeone/10', 'line' => 'bg-orangeone'],
            ['src' => 'iconeEntete-10.svg', 'title' => 'Gain de temps', 'desc' => "Centralisez les contenus, le suivi et les résultats pour vous concentrer sur l'accompagnement.", 'soft' => 'bg-bleuone/10', 'line' => 'bg-bleuone'],
            ['src' => 'iconeEntete-11.svg', 'title' => 'Activités variées', 'desc' => 'Intégrez facilement vidéos, documents, activités interactives et évaluations.', 'soft' => 'bg-vertone/10', 'line' => 'bg-vertone'],
            ['src' => 'iconeEntete-12.svg', 'title' => 'Progrès valorisés', 'desc' => 'Mettez en évidence les avancées des apprenants grâce à des indicateurs clairs et des badges de réussite.', 'soft' => 'bg-orangeone/10', 'line' => 'bg-orangeone'],
            ['src' => 'iconeEntete-09.svg', 'title' => 'Présentiel et en ligne', 'desc' => 'Combinez facilement les séances en salle et les activités en ligne selon les contraintes de votre terrain.', 'soft' => 'bg-bleuone/10', 'line' => 'bg-bleuone'],
        ] as $block)
          <article class="group relative overflow-hidden rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/70 transition duration-300 hover:-translate-y-1 hover:border-orangeone/40 hover:shadow-xl hover:shadow-slate-200/80">
            <span class="absolute inset-x-5 top-0 h-1 rounded-full {{ $block['line'] }}"></span>
            <div class="flex min-h-full flex-col">
              <div class="mb-5 flex h-28 w-28 items-center justify-center rounded-lg {{ $block['soft'] }}">
                <img src="{{ asset('frontend/assets/img/front-pages/icons/' . $block['src']) }}"
                     alt=""
                     aria-hidden="true"
                     class="h-24 w-24 object-contain transition duration-300 group-hover:scale-105">
              </div>
              <h5 class="font-raleway text-xl font-bold leading-snug text-bleuone">{{ $block['title'] }}</h5>
              <p class="mt-3 text-base leading-relaxed text-slate-600">{{ $block['desc'] }}</p>
            </div>
          </article>
        @endforeach
      </div>

    </div>
  </section>

  <section class="relative overflow-hidden bg-white py-20">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-15.svg') }}" alt="" aria-hidden="true" class="absolute left-[8%] top-16 w-16 -rotate-[18deg] opacity-10 md:w-24">
    <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-23.svg') }}" alt="" aria-hidden="true" class="absolute right-[7%] bottom-12 w-20 rotate-[16deg] opacity-10 md:w-28">
    <div class="relative mx-auto max-w-[1248px] px-6">
      <div class="grid items-center gap-14 lg:grid-cols-2">

        <div class="space-y-7 font-lisible text-slate-600">
          <h2 class="flex items-center gap-4 font-raleway text-[34px] font-extrabold leading-tight text-bleuone md:text-[40px]">
            <img src="{{ asset('frontend/assets/img/front-pages/icons/etoile8.gif') }}" width="60" height="61" alt="" aria-hidden="true" class="h-[60px] w-[60px] flex-none object-contain">
            <span>Un projet créé par des formateurs</span>
          </h2>

          <div class="max-w-[64ch] space-y-5 text-lg leading-relaxed">
            <p class="border-l-4 border-orangeone pl-5 text-xl font-semibold leading-snug text-bleuone">
              Onéduc est né d'une expérience concrète du terrain, au contact des formateurs et des apprenants qui découvrent le numérique.
            </p>
            <p>
              Le projet repose sur une conviction simple : un outil de formation ne doit pas imposer des pratiques, mais accompagner les formateurs dans la construction de parcours accessibles, adaptés aux publics et aux contextes d'intervention.
            </p>
          </div>

          <div class="grid gap-3 text-base sm:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-gray-50 p-4">
              <p class="font-semibold text-bleuone">Pensé pour le terrain</p>
              <p class="mt-2 leading-relaxed text-slate-600">Des usages simples, testés dans des situations réelles d'accompagnement.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-gray-50 p-4">
              <p class="font-semibold text-bleuone">Accessible par défaut</p>
              <p class="mt-2 leading-relaxed text-slate-600">Une interface qui aide à avancer, même quand le numérique n'est pas familier.</p>
            </div>
          </div>
        </div>

        <div class="group relative mx-auto w-full max-w-xl">
          <div class="absolute -inset-3 rounded-[28px] border-2 border-orangeone/35 bg-orangeone/5 -rotate-2 transition-transform duration-500 group-hover:rotate-0"></div>
          <div class="relative rounded-[28px] bg-white p-5 shadow-xl shadow-slate-200/80 transition-transform duration-500 group-hover:-translate-y-2">
            <img src="{{ asset('frontend/assets/img/front-pages/landing-page/module.svg') }}"
                 alt="Illustration d'un module Oneduc"
                 class="w-full rounded-2xl object-cover">
          </div>
        </div>

      </div>
    </div>
  </section>


{{-- SECTION TÉMOIGNAGES --}}
<section class="relative overflow-hidden bg-gray-50 py-20">
  <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-12.svg') }}" alt="" aria-hidden="true" class="absolute left-8 top-20 w-16 -rotate-[20deg] opacity-10 md:left-20 md:w-24">
  <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-22.svg') }}" alt="" aria-hidden="true" class="absolute right-8 bottom-16 w-20 rotate-[18deg] opacity-10 md:right-24 md:w-28">
  <div class="relative mx-auto max-w-[1248px] px-6">
    <div class="mx-auto mb-14 max-w-3xl text-center">
      <h2 class="mb-4 text-center font-raleway text-[34px] font-extrabold leading-tight text-bleuone md:text-[40px]">Ils utilisent Onéduc</h2>
      <p class="mx-auto max-w-2xl font-lisible text-lg leading-relaxed text-slate-600">Des retours de formateurs qui cherchent un outil simple, clair et adapté à leurs publics.</p>
      <p class="mx-auto mt-3 max-w-2xl font-lisible text-sm text-slate-400">Profils illustratifs, représentatifs des usages types d'Onéduc.</p>
    </div>

    <div class="grid grid-cols-1 gap-5 font-lisible md:grid-cols-3">
      @foreach([
        ['quote' => "Onéduc m'aide à garder une vue claire sur chaque stagiaire. Je vois rapidement qui avance, qui bloque, et je peux adapter mes séances sans perdre de temps.", 'name' => 'Claire M.', 'role' => 'Formatrice numérique', 'structure' => "Association d'insertion", 'color' => 'bg-orangeone'],
        ['quote' => "La prise en main est rassurante. Pour des apprenants qui débutent avec l'ordinateur, les parcours sont plus faciles à suivre et les consignes restent accessibles.", 'name' => 'Karim D.', 'role' => 'Coordinateur pédagogique', 'structure' => 'Centre de formation', 'color' => 'bg-bleuone'],
        ['quote' => "J'apprécie surtout le côté progressif. Les contenus, les quiz et le suivi sont réunis au même endroit, ce qui facilite beaucoup l'accompagnement au quotidien.", 'name' => 'Sophie L.', 'role' => 'Formatrice FLE et numérique', 'structure' => 'Ateliers sociolinguistiques', 'color' => 'bg-vertone'],
      ] as $temoignage)
        <article class="group relative flex min-h-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/70 transition duration-300 hover:-translate-y-1 hover:border-orangeone/40 hover:shadow-xl hover:shadow-slate-200/80">
          <span class="absolute inset-x-6 top-0 h-1 rounded-full {{ $temoignage['color'] }}"></span>
          <svg class="mt-4 h-9 w-9 text-orangeone/80" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
          </svg>
          <p class="mt-5 flex-1 text-lg italic leading-relaxed text-slate-700">« {{ $temoignage['quote'] }} »</p>
          <div class="mt-7 flex items-center gap-3 border-t border-slate-100 pt-5">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-bleuone/10 font-raleway text-lg font-bold text-bleuone">
              {{ mb_substr($temoignage['name'], 0, 1) }}
            </div>
            <div>
              <p class="font-semibold text-bleuone">{{ $temoignage['name'] }}</p>
              <p class="text-sm text-slate-500">{{ $temoignage['role'] }}</p>
              <p class="text-sm text-slate-400">{{ $temoignage['structure'] }}</p>
            </div>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

{{-- SECTION FAQ - accordéon Alpine.js (plugin @alpinejs/collapse déjà chargé) --}}
<section class="relative overflow-hidden bg-white py-20" x-data="{ open: null }">
  <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-19.svg') }}" alt="" aria-hidden="true" class="absolute left-8 top-16 w-16 -rotate-[18deg] opacity-10 md:left-24 md:w-24">
  <img src="{{ asset('frontend/assets/img/front-pages/akene-seeds/seed-14.svg') }}" alt="" aria-hidden="true" class="absolute right-8 bottom-16 w-20 rotate-[22deg] opacity-10 md:right-24 md:w-28">
  <div class="relative mx-auto max-w-[980px] px-6">
    <div class="mx-auto mb-12 max-w-3xl text-center">
      <h2 class="inline-flex items-center justify-center gap-4 font-raleway text-[34px] font-extrabold leading-tight text-bleuone md:text-[40px]">
        <img src="{{ asset('frontend/assets/img/front-pages/icons/etoile8.gif') }}" width="60" height="61" alt="" aria-hidden="true" class="h-[60px] w-[60px] flex-none object-contain">
        <span>Questions fréquentes</span>
      </h2>
      <p class="mx-auto mt-5 max-w-2xl font-lisible text-lg leading-relaxed text-slate-600">Les réponses essentielles avant de créer votre espace formateur ou d'inviter vos apprenants.</p>
    </div>

    <div class="space-y-4 font-lisible">
      @foreach([
        ['q' => 'Est-ce vraiment gratuit ?',             'r' => "Pour les apprenants, oui : entièrement gratuit, sans compte ni carte bancaire. Pour les formateurs, l'accès est gratuit pendant une période de découverte de 30 jours ; au-delà, la poursuite de l'usage de l'espace formateur nécessite une adhésion à l'association Onéduc (cotisation annuelle via HelloAsso, montant indiqué dans le formulaire)."],
        ['q' => "Que se passe-t-il après l'inscription ?", 'r' => "Vous accédez directement à votre espace formateur. Vous pouvez créer votre premier groupe, ajouter des contenus et inviter vos apprenants avec un code d'accès."],
        ['q' => 'Faut-il installer quelque chose ?',     'r' => "Non. Onéduc fonctionne entièrement dans votre navigateur internet (Chrome, Firefox, Edge…). Aucune installation n'est nécessaire, ni sur votre ordinateur ni sur celui de vos apprenants."],
        ['q' => 'Mes apprenants doivent-ils créer un compte ?', 'r' => "Non. Vos apprenants rejoignent votre formation en saisissant simplement le code d'accès que vous leur donnez. Pas de compte, pas de mot de passe à retenir."],
      ] as $i => $faq)
        <article class="overflow-hidden rounded-lg border bg-white shadow-sm shadow-slate-200/70 transition duration-300" :class="open === {{ $i }} ? 'border-orangeone/45 shadow-lg shadow-slate-200/80' : 'border-slate-200 hover:border-orangeone/30'">
          <button
            type="button"
            class="flex w-full items-center justify-between gap-5 px-6 py-5 text-left transition hover:bg-gray-50"
            @click="open = open === {{ $i }} ? null : {{ $i }}"
            :aria-expanded="(open === {{ $i }}).toString()"
          >
            <span class="font-raleway text-lg font-bold leading-snug text-bleuone md:text-xl">{{ $faq['q'] }}</span>
            <span class="flex h-10 w-10 flex-none items-center justify-center rounded-full bg-orangeone/10 text-orangeone transition" :class="{ 'rotate-180 bg-orangeone text-white': open === {{ $i }} }">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
              </svg>
            </span>
          </button>
          <div x-show="open === {{ $i }}" x-collapse.duration.400ms class="px-6 pb-6 pr-16 text-base leading-relaxed text-slate-600 md:text-lg">
            {{ $faq['r'] }}
          </div>
        </article>
      @endforeach
    </div>

    <div class="mt-10 rounded-lg border border-orangeone/20 bg-orangeone/5 p-6 text-center font-lisible">
      <p class="text-lg font-semibold text-bleuone">Prêt à essayer Onéduc ?</p>
      <p class="mt-2 text-slate-600">Vous pouvez créer votre espace formateur en quelques minutes.</p>
      <a href="{{ route('formateur.inscription.form') }}" class="btn-oneduc mt-5 inline-flex">
        Je suis formateur
      </a>
    </div>
  </div>
</section>

@push('scripts')
<script>
  (function () {
    const container = document.querySelector('[data-oneduc-consent-video]');
    if (!container) {
      return;
    }

    const videoId = container.dataset.videoId;
    const videoTitle = container.dataset.videoTitle || '';
    const posterHtml = container.innerHTML;

    function hasVideoConsent() {
      return !!(window.laravelCookieConsent && window.laravelCookieConsent.hasConsented('video'));
    }

    function loadVideo() {
      if (container.querySelector('iframe')) {
        return;
      }

      const iframe = document.createElement('iframe');
      iframe.src = 'https://www.youtube-nocookie.com/embed/' + videoId
        + '?autoplay=1&mute=1&loop=1&playlist=' + videoId + '&controls=0&showinfo=0&modestbranding=1';
      iframe.className = 'w-full h-full';
      iframe.setAttribute('frameborder', '0');
      iframe.setAttribute('allow', 'autoplay');
      iframe.setAttribute('allowfullscreen', '');
      iframe.setAttribute('title', videoTitle);

      container.innerHTML = '';
      container.appendChild(iframe);
    }

    function unloadVideo() {
      if (!container.querySelector('iframe')) {
        return;
      }

      container.innerHTML = posterHtml;
      container.querySelector('[data-oneduc-consent-video-play]').addEventListener('click', loadVideo);
    }

    container.querySelector('[data-oneduc-consent-video-play]').addEventListener('click', loadVideo);

    if (hasVideoConsent()) {
      loadVideo();
    }

    // Bandeau/modale de préférences encore ouverts sur cette même page : on
    // (dé)charge la vidéo sans attendre un rechargement.
    document.addEventListener('cookie-consent:updated', (event) => {
      if (event.detail && event.detail.video) {
        loadVideo();
      } else {
        unloadVideo();
      }
    });
  })();
</script>
@endpush

@endsection
