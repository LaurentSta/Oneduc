<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title','Évaluation SCORM')</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900">
    <a href="#contenu" class="sr-only focus:not-sr-only focus:block px-3 py-2">Aller au contenu</a>

    {{-- Header optionnel (désactivé par défaut). 
         Pour l’afficher dans une vue enfant : @php($showHeader = true) --}}
    @if(!empty($showHeader))
        @include('stagiaire.formations.body_formations.header')
    @endif

    {{-- Zone principale 100% largeur, sans sidebar, pensée pour un iframe plein écran --}}
    <main id="contenu" class="w-full mx-auto px-0 md:px-0 py-0">
        @yield('content')
    </main>
</body>
</html>
