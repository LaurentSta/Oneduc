// Minimal clone du flux "leçons" vers /scorm/evaluation-progress
(function () {
  const ctx = window.parent?.SCORM_CONTEXT || window.SCORM_CONTEXT || {};
  if (!ctx || !ctx.post_url || !ctx.evaluation_id) return;

  function postKV(k, v) {
    return fetch(ctx.post_url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': window.parent?.document.querySelector('meta[name="csrf-token"]')?.content
          || document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      credentials: 'same-origin',
      body: JSON.stringify({
        evaluation_id: ctx.evaluation_id,
        scorm_key: String(k),
        scorm_value: v != null ? String(v) : null,
        user_id: ctx.user_id || null
      }),
      keepalive: true
    }).catch(()=>{});
  }

  // API publique appelée par le paquet
  window.EVAL_API = {
    setValue: postKV,                // EVAL_API.setValue('cmi.core.score.raw', 85)
    commit:   () => postKV('commit','1'),
    terminate:() => postKV('terminate','1'),

    // Helpers courants (SCORM 1.2 & 2004)
    scoreRaw:   v => postKV('cmi.core.score.raw', v),
    scoreScaled:v => postKV('cmi.score.scaled', v), // 0..1
    status12:   s => postKV('cmi.core.lesson_status', s),     // completed|incomplete|passed|failed
    status2004: s => postKV('cmi.completion_status', s),      // completed|incomplete
    success2004:s => postKV('cmi.success_status', s),         // passed|failed|unknown
    sessionTime:t => postKV('cmi.core.session_time', t),      // HH:MM:SS ou PT#H#M#S

    // Interactions (appeler au fil de l’eau)
    interaction: (i, data) => {
      if (!i) return;
      if (data.id)               postKV(`cmi.interactions.${i}.id`, data.id);
      if (data.type)             postKV(`cmi.interactions.${i}.type`, data.type);
      if (data.weighting!=null)  postKV(`cmi.interactions.${i}.weighting`, data.weighting);
      if (data.response!=null)   postKV(`cmi.interactions.${i}.student_response`, data.response); // 1.2
      if (data.learner_response!=null) postKV(`cmi.interactions.${i}.learner_response`, data.learner_response); // 2004
      if (data.correct)          postKV(`cmi.interactions.${i}.correct_responses.0.pattern`, data.correct);
      if (data.latency)          postKV(`cmi.interactions.${i}.latency`, data.latency);
      if (data.time)             postKV(`cmi.interactions.${i}.time`, data.time);
      if (data.result!=null)     postKV(`cmi.interactions.${i}.result`, data.result); // correct|wrong|incorrect|x.y
    }
  };

  // Filet: commit périodique et terminate à la fermeture
  let _t = setInterval(()=>window.EVAL_API.commit(), 15000);
  window.addEventListener('beforeunload', ()=> {
    clearInterval(_t);
    window.EVAL_API.terminate();
  });
})();
