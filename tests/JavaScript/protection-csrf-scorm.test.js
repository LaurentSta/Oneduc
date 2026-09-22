import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const script = readFileSync(new URL('../../public/scorm_core/js/API_evaluation.js', import.meta.url), 'utf8');

test('les évaluations SCORM transmettent le jeton parent pendant la session et à la fermeture', async () => {
    const requetes = [];
    let fermeture;
    const window = {
        parent: {
            SCORM_CONTEXT: { post_url: '/scorm/evaluation-progress', evaluation_id: 42 },
            document: { querySelector: () => ({ content: 'jeton-parent' }) },
        },
        addEventListener: (nom, rappel) => { if (nom === 'beforeunload') fermeture = rappel; },
    };
    vm.runInNewContext(script, {
        window,
        document: { querySelector: () => null },
        fetch: async (url, options) => { requetes.push({ url, options }); return { ok: true }; },
        setInterval: () => 1,
        clearInterval: () => {},
    });

    await window.EVAL_API.status12('completed');
    await window.EVAL_API.status2004('completed');
    fermeture();

    assert.equal(requetes.length, 3);
    for (const { url, options } of requetes) {
        assert.equal(url, '/scorm/evaluation-progress');
        assert.equal(options.headers['X-CSRF-TOKEN'], 'jeton-parent');
        assert.equal(options.credentials, 'same-origin');
        assert.equal(options.keepalive, true);
        assert.equal(JSON.parse(options.body).evaluation_id, 42);
    }
    assert.equal(JSON.parse(requetes[2].options.body).scorm_key, 'terminate');
});
