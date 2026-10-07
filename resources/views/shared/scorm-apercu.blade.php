<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Aperçu — {{ $lecture->lecture_title }}</title>
  <style>html,body,iframe{width:100%;height:100%;margin:0;border:0;display:block}</style>
</head>
<body>
  <script>window.SCORM_CONTEXT = { preview: true, embedded: true, lecture_id: {{ (int) $lecture->id }} };</script>
  <iframe src="{{ $scormUrl }}" title="Aperçu du contenu SCORM" allowfullscreen></iframe>
</body>
</html>
