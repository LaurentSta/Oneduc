/**
 * Moteur réutilisable "points interactifs sur une image", partagé par toutes
 * les activités de ce type (bureau Windows, et les suivantes).
 *
 * Usage dans une activité : voir index.html de bureau-windows pour un exemple
 * complet. En résumé, le HTML doit fournir la structure data-hotspots-*
 * (zone, compteur, panneau...) et appeler :
 *
 *   initActiviteHotspots({
 *     selecteur: '[data-hotspots]',
 *     points: [{ x: 50, y: 30, titre: '...', description: '...' }, ...],
 *   });
 *
 * x/y sont des pourcentages (0-100) de la zone image, pour rester corrects
 * quelle que soit la taille d'écran.
 */
(function () {
  "use strict";

  function trouverApiScorm() {
    let fenetre = window;
    for (let i = 0; i < 8 && fenetre; i += 1) {
      if (fenetre.API) return fenetre.API;
      if (fenetre === fenetre.parent) break;
      fenetre = fenetre.parent;
    }
    return null;
  }

  function signalerCompletionScorm() {
    const api = trouverApiScorm();
    if (!api) return;
    try {
      if (typeof api.LMSInitialize === "function") api.LMSInitialize("");
      if (typeof api.LMSSetValue === "function") api.LMSSetValue("cmi.core.lesson_status", "completed");
      if (typeof api.LMSFinish === "function") api.LMSFinish("");
    } catch (erreur) {
      console.error("Signal de complétion SCORM impossible :", erreur);
    }
  }

  function initActiviteHotspots(config) {
    const conteneur = document.querySelector(config.selecteur || "[data-hotspots]");
    if (!conteneur) return;

    const zone = conteneur.querySelector("[data-hotspots-zone]");
    const compteurTexte = conteneur.querySelector("[data-hotspots-compteur]");
    const messageFin = conteneur.querySelector("[data-hotspots-fin]");
    const panneau = conteneur.querySelector("[data-hotspots-panneau]");
    const panneauTitre = panneau?.querySelector("[data-hotspots-panneau-titre]");
    const panneauTexte = panneau?.querySelector("[data-hotspots-panneau-texte]");
    const panneauFermer = panneau?.querySelector("[data-hotspots-panneau-fermer]");

    if (!zone || !compteurTexte) return;

    const decouverts = new Set();
    const total = config.points.length;
    let boutonActif = null;

    function majCompteur() {
      compteurTexte.textContent = `Éléments découverts : ${decouverts.size} / ${total}`;
      if (decouverts.size === total && messageFin) {
        messageFin.hidden = false;
        signalerCompletionScorm();
      }
    }

    function fermerPanneau() {
      if (!panneau) return;
      panneau.hidden = true;
      boutonActif?.focus();
      boutonActif = null;
    }

    function ouvrirPanneau(point, boutonOrigine) {
      if (!panneau || !panneauTitre || !panneauTexte) return;
      boutonActif = boutonOrigine;
      panneauTitre.textContent = point.titre;
      panneauTexte.textContent = point.description;
      panneau.hidden = false;
      panneauFermer?.focus();
    }

    panneauFermer?.addEventListener("click", fermerPanneau);
    panneau?.addEventListener("keydown", (evenement) => {
      if (evenement.key === "Escape") fermerPanneau();
    });
    panneau?.addEventListener("click", (evenement) => {
      if (evenement.target === panneau) fermerPanneau();
    });

    config.points.forEach((point, index) => {
      const bouton = document.createElement("button");
      bouton.type = "button";
      bouton.className = "hotspot";
      bouton.style.left = `${point.x}%`;
      bouton.style.top = `${point.y}%`;
      bouton.setAttribute("aria-pressed", "false");
      bouton.setAttribute("aria-label", point.titre);
      bouton.innerHTML = '<span class="hotspot__halo" aria-hidden="true"></span>';
      bouton.addEventListener("click", () => {
        if (!decouverts.has(index)) {
          decouverts.add(index);
          bouton.setAttribute("aria-pressed", "true");
          bouton.classList.add("hotspot--decouvert");
          majCompteur();
        }
        ouvrirPanneau(point, bouton);
      });
      zone.appendChild(bouton);
    });

    majCompteur();
  }

  window.initActiviteHotspots = initActiviteHotspots;
})();
