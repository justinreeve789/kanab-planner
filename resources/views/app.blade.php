<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
{{--    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css'>--}}
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Roboto:wght@100&amp;display=swap'>
    <link href="https://fonts.googleapis.com/css2?family=Satisfy&display=block" rel="stylesheet">
    <link rel="icon" href="https://image.flaticon.com/icons/svg/2983/2983687.svg">
{{--    <link rel="stylesheet" href="https://unpkg.com/swiper@10.3.1/swiper-bundle.min.css"/>--}}
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
{{--    <script src="https://cdn.jsdelivr.net/npm/ismobilejs@1/dist/isMobile.min.js"></script>--}}
    <title>Itinerary</title>
</head>
<body class="bg-gray-300">
<div id="app" class="container mx-auto">
    <app></app>
</div>
<script src="{{ mix('js/app.js') }}" defer></script>
</body>
</html>