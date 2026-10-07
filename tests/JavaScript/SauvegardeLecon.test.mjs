import test from 'node:test';
import assert from 'node:assert/strict';
import { creerSauvegardeLecon } from '../../resources/js/sauvegarde-lecon.js';

function attente() {
  let terminer;
  const promise = new Promise((resolve) => { terminer = resolve; });
  return { promise, terminer };
}

test('enregistre le dernier contenu avant de quitter sans attendre le délai automatique', async () => {
  const contenus = [];
  const etats = [];
  const sauvegarde = creerSauvegardeLecon({ envoyer: async (contenu) => contenus.push(contenu), notifier: (etat) => etats.push(etat) });
  sauvegarde.planifier('premier texte');
  sauvegarde.planifier('dernier texte');
  await sauvegarde.enregistrer();
  assert.deepEqual(contenus, ['dernier texte']);
  assert.equal(sauvegarde.estModifiee(), false);
  assert.equal(etats.at(-1), 'saved');
  sauvegarde.detruire();
});

test('ordonne les requêtes et attend les changements effectués pendant un envoi', async () => {
  const premiere = attente();
  const seconde = attente();
  const contenus = [];
  const sauvegarde = creerSauvegardeLecon({
    notifier: () => {},
    envoyer: (contenu) => {
      contenus.push(contenu);
      return contenus.length === 1 ? premiere.promise : seconde.promise;
    },
  });
  sauvegarde.planifier('version 1');
  const fin = sauvegarde.enregistrer();
  sauvegarde.planifier('version 2');
  assert.equal(sauvegarde.enregistrer(), fin);
  assert.deepEqual(contenus, ['version 1']);
  premiere.terminer();
  await Promise.resolve();
  assert.deepEqual(contenus, ['version 1', 'version 2']);
  assert.equal(sauvegarde.estModifiee(), true);
  seconde.terminer();
  await fin;
  assert.equal(sauvegarde.estModifiee(), false);
  sauvegarde.detruire();
});

test('conserve les changements après une erreur et permet une nouvelle tentative', async () => {
  let panne = true;
  const etats = [];
  const contenus = [];
  const sauvegarde = creerSauvegardeLecon({
    notifier: (etat) => etats.push(etat),
    envoyer: async (contenu) => {
      if (panne) throw new Error('réseau indisponible');
      contenus.push(contenu);
    },
  });
  sauvegarde.planifier('à conserver');
  await assert.rejects(sauvegarde.enregistrer(), /réseau indisponible/);
  assert.equal(sauvegarde.estModifiee(), true);
  assert.equal(etats.at(-1), 'error');
  panne = false;
  await sauvegarde.enregistrer();
  assert.deepEqual(contenus, ['à conserver']);
  assert.equal(sauvegarde.estModifiee(), false);
  sauvegarde.detruire();
});
