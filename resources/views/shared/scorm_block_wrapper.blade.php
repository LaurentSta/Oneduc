<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Contenu SCORM</title>
  <style>
    html, body { margin: 0; padding: 0; height: 100%; }
    iframe { display: block; width: 100%; height: 100%; border: 0; }
  </style>
</head>
<body>
  {{--
    Contexte isolé pour ce bloc SCORM : défini sur CETTE fenêtre (le wrapper), pas sur
    celle de la leçon parente. Vu depuis le paquet SCORM (dans l'iframe interne),
    window.parent pointe vers ce wrapper, donc API.js lit ce contexte-ci et non celui,
    déjà présent, de la page de leçon englobante.
  --}}
  <script>
    window.SCORM_CONTEXT = {
      embedded: true,
      lecture_id: {{ (int) $lectureId }},
      content_block_key: @json($contentBlockKey),
      is_already_done: {{ $isAlreadyDone ? 'true' : 'false' }},
    };
  </script>
  <iframe src="{{ $scormUrl }}" title="Contenu SCORM" allowfullscreen></iframe>
</body>
</html>
