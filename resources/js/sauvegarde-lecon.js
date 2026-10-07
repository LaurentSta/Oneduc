export function creerSauvegardeLecon({ envoyer, notifier, delai = 800 }) {
  let revision = 0;
  let revisionEnregistree = 0;
  let donnees = null;
  let minuterie = null;
  let enCours = null;

  function estModifiee() {
    return revision !== revisionEnregistree;
  }

  function enregistrer() {
    clearTimeout(minuterie);
    if (enCours) return enCours;
    if (!estModifiee()) return Promise.resolve();

    // Une seule requête à la fois ; les changements pendant l'envoi sont
    // regroupés dans la prochaine requête avec le dernier contenu disponible.
    enCours = (async () => {
      while (estModifiee()) {
        const revisionEnvoyee = revision;
        const contenuEnvoye = donnees;
        notifier('saving');
        await envoyer(contenuEnvoye);
        revisionEnregistree = revisionEnvoyee;
      }
      notifier('saved');
    })().catch((erreur) => {
      notifier('error');
      throw erreur;
    }).finally(() => { enCours = null; });

    return enCours;
  }

  function planifier(contenu) {
    donnees = contenu;
    revision += 1;
    notifier('unsaved');
    clearTimeout(minuterie);
    minuterie = setTimeout(() => enregistrer().catch(() => {}), delai);
  }

  function detruire() {
    clearTimeout(minuterie);
  }

  return { planifier, enregistrer, estModifiee, detruire };
}
