import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function chargerApi(preview) {
  const envois = [];
  const window = { parent: {
    SCORM_CONTEXT: { preview, embedded: true, lecture_id: 42 },
    document: { querySelector: () => ({ content: 'jeton-test' }) },
  } };
  vm.runInNewContext(readFileSync(new URL('../../public/scorm_core/js/API.js', import.meta.url), 'utf8'), {
    window, console: { log() {}, warn() {}, error() {} }, setTimeout, clearTimeout,
    fetch: async (url, options) => {
      envois.push({ url, body: JSON.parse(options.body) });
      return { json: async () => ({ success: true }) };
    },
  });
  return { api: window.API, envois };
}

test('l’aperçu SCORM accepte les appels du contenu sans envoyer de progression', async () => {
  const { api, envois } = chargerApi(true);
  assert.equal(api.LMSInitialize(''), 'true');
  assert.equal(api.LMSSetValue('cmi.core.score.raw', '100'), 'true');
  api.LMSFinish('');
  await Promise.resolve();
  assert.deepEqual(envois, []);
});

test('le runtime habituel continue à envoyer les valeurs SCORM', async () => {
  const { api, envois } = chargerApi(false);
  api.LMSSetValue('cmi.core.score.raw', '75');
  await Promise.resolve();
  assert.equal(envois.length, 1);
  assert.equal(envois[0].url, '/scorm/save-progress');
  assert.deepEqual(envois[0].body, { lecture_id: 42, scorm_key: 'cmi.core.score.raw', scorm_value: '75' });
});
