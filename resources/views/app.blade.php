<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Roboto:wght@100&amp;display=swap'>
    <link href="https://fonts.googleapis.com/css2?family=Satisfy&display=block" rel="stylesheet">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="https://image.flaticon.com/icons/svg/2983/2983687.svg">
{{--    <link rel="stylesheet" href="https://unpkg.com/swiper@10.3.1/swiper-bundle.min.css"/>--}}
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
{{--    <script src="https://cdn.jsdelivr.net/npm/ismobilejs@1/dist/isMobile.min.js"></script>--}}
    <title>Itinerary</title>
</head>
<body class="bg-gray-300">
<script>
  if (typeof navigator.serviceWorker !== 'undefined') {
    navigator.serviceWorker.register('sw.js')
  }
</script>
<div id="app">
    <app :itinerary-id="'{{ $id ?? null }}'"></app>
</div>
<script src="{{ mix('js/app.js') }}" defer></script>
</body>
</html>