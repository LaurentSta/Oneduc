/**
 * /public/scorm_core/js/API.js
 * API SCORM 1.2 Onéduc optimisée
 */

(function () {
  "use strict";

  // ----------------------------
  // 1) Helpers Onéduc (parent)
  // ----------------------------
  function getContext() {
    return window.parent?.SCORM_CONTEXT || window.SCORM_CONTEXT || null;
  }

  function getLectureId() {
    return getContext()?.lecture_id || 0;
  }

  function isDoneStatus(scormValue) {
    // SCORM 1.2/2004 : valeurs de complétion/succès
    const v = String(scormValue ?? "").toLowerCase();
    return v === "completed" || v === "passed";
  }

  let nextButtonShown = false;
  let nextButtonRetryTimer = null;
  let nextButtonRetryCount = 0;
  const MAX_NEXT_BUTTON_RETRIES = 30;
  const NEXT_BUTTON_RETRY_DELAY_MS = 200;

  function scheduleNextButtonRetry() {
    if (nextButtonShown) return;
    if (nextButtonRetryTimer) return;
    if (nextButtonRetryCount >= MAX_NEXT_BUTTON_RETRIES) return;

    nextButtonRetryTimer = setTimeout(() => {
      nextButtonRetryTimer = null;
      nextButtonRetryCount += 1;
      afficherBoutonSuivantDepuisIframe();
    }, NEXT_BUTTON_RETRY_DELAY_MS);
  }

  function afficherBoutonSuivantDepuisIframe() {
    if (nextButtonShown) return;

    const context = getContext();

    // Un bloc SCORM embarqué dans une leçon mixte ne pilote pas le bouton "leçon
    // suivante" de la leçon englobante (ambigu s'il y a plusieurs blocs) : on se
    // contente de suivre sa progression, sans chercher le DOM du wrapper.
    if (context?.embedded === true) return;
    const parentDoc = window.parent?.document;
    const wrappers = Array.from(parentDoc?.querySelectorAll("#next-lesson-wrapper") ?? []);
    const boutons = Array.from(parentDoc?.querySelectorAll("#next-lesson-button") ?? []);
    const textesBouton = Array.from(parentDoc?.querySelectorAll("#next-button-text") ?? []);

    if (context?.hide_next_button === true) {
      wrappers.forEach((wrapper) => {
        wrapper.classList.add("hidden");
        wrapper.style.pointerEvents = "none";
      });

      boutons.forEach((bouton) => {
        bouton.style.opacity = "0";
        bouton.style.pointerEvents = "none";
        bouton.style.cursor = "default";
      });

      nextButtonShown = true;
      return;
    }

    wrappers.forEach((wrapper) => {
      wrapper.classList.remove("hidden");
      wrapper.style.pointerEvents = "auto";
    });

    boutons.forEach((bouton) => {
      bouton.style.opacity = "1";
      bouton.style.pointerEvents = "auto";
      bouton.style.cursor = "pointer";
    });

    if (!context) {
      scheduleNextButtonRetry();
      return;
    }

    if (!wrappers.length || !boutons.length) {
      // Certains contenus SCORM remontent "completed" très tôt :
      // on réessaie un peu plus tard si le DOM parent n'est pas prêt.
      scheduleNextButtonRetry();
      return;
    }

    // Logique de redirection Onéduc
    const shouldOfferQuiz = Boolean(context.quiz_start_url) && context.force_next_lesson !== true;
    const nextStepLabel = String(
      shouldOfferQuiz
        ? (context.quiz_button_label || "Passer au Questionnaire")
        : (context.next_button_label || "Leçon suivante")
    );
    if (shouldOfferQuiz) {
      textesBouton.forEach((texte) => { texte.innerText = nextStepLabel; });
      boutons.forEach((bouton) => {
        bouton.onclick = function () {
        // Appel de la fonction globale définie dans lecon.blade.php
        if (typeof window.parent.goToQuiz === "function") {
            window.parent.goToQuiz();
        } else if (typeof context.goToQuiz === "function") {
            context.goToQuiz();
        } else {
            window.parent.location.href = context.quiz_start_url;
        }
        };
      });
    } else {
      textesBouton.forEach((texte) => { texte.innerText = nextStepLabel; });
      boutons.forEach((bouton) => {
        bouton.onclick = function () {
        if (typeof window.parent.goToNextLesson === "function") {
            window.parent.goToNextLesson();
        } else if (typeof context.goToNextLesson === "function") {
            context.goToNextLesson();
        } else {
            window.parent.location.href = context.next_url;
        }
        };
      });
    }

    if (nextButtonRetryTimer) {
      clearTimeout(nextButtonRetryTimer);
      nextButtonRetryTimer = null;
    }
    nextButtonRetryCount = 0;
    nextButtonShown = true;
    console.log("🚀 Bouton de navigation Onéduc activé");
  }

  // ----------------------------
  // 2) Envoi SCORM -> Laravel
  // ----------------------------
  let lastSent = { key: null, value: null, at: 0 };

  function shouldSend(key, value) {
    const now = Date.now();
    if (lastSent.key === key && lastSent.value === value && (now - lastSent.at) < 500) {
      return false;
    }
    lastSent = { key, value, at: now };
    return true;
  }

  async function envoyerProgression(key, value) {
    if (getContext()?.preview === true) return null;
    const lectureId = getLectureId();
    if (!lectureId || !shouldSend(key, value)) return null;

    const context = getContext();
    const blockKey = context?.content_block_key || null;
    const endpoint = blockKey ? "/scorm/save-block-progress" : "/scorm/save-progress";
    const body = { lecture_id: lectureId, scorm_key: key, scorm_value: value };
    if (blockKey) body.content_block_key = blockKey;

    try {
      const res = await fetch(endpoint, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": window.parent?.document.querySelector('meta[name="csrf-token"]')?.content || ""
        },
        credentials: "same-origin",
        keepalive: true,
        body: JSON.stringify(body)
      });

      const data = await res.json().catch(() => null);

      // Si le backend confirme la complétion, on affiche le bouton
      if (data?.lesson_status === "completed" || isDoneStatus(data?.scorm_lesson_status)) {
        afficherBoutonSuivantDepuisIframe();
      }

      return data;
    } catch (err) {
      console.error("❌ Erreur SCORM Onéduc:", err);
      return null;
    }
  }

  // ----------------------------
  // 3) API SCORM 1.2
  // ----------------------------
  const cmiStore = Object.create(null);

  const API = {
    LMSInitialize: function () {
      console.log("✅ LMSInitialize Onéduc");
      
      // CRITIQUE : Si le PHP nous dit que c'est déjà fini, on affiche le bouton direct
      const ctx = getContext();
      if (ctx && ctx.is_already_done === true) {
          afficherBoutonSuivantDepuisIframe();
      }
      
      return "true";
    },

    LMSGetValue: function (name) {
      return cmiStore[name] ?? "";
    },

    LMSSetValue: function (name, value) {
      cmiStore[name] = value;
      envoyerProgression(name, value);

      // Détection immédiate du succès (Storyline/iSpring SCORM 1.2/2004)
      if (
        (name === "cmi.core.lesson_status" ||
         name === "cmi.completion_status" ||
         name === "cmi.success_status") &&
        isDoneStatus(value)
      ) {
        afficherBoutonSuivantDepuisIframe();
      }

      return "true";
    },

    LMSCommit: function () { return "true"; },
    LMSFinish: function () {
      // Fallback robuste: certains Storyline n'envoient jamais "completed/passed"
      // mais appellent LMSFinish en fin de session.
      envoyerProgression("cmi.core.lesson_status", "completed");
      afficherBoutonSuivantDepuisIframe();
      return "true";
    },
    LMSGetLastError: function () { return "0"; },
    LMSGetErrorString: function () { return ""; },
    LMSGetDiagnostic: function () { return ""; }
  };

  window.API = API;
  console.log("✅ API Onéduc initialisée");
})();
