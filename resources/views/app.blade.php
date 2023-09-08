<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css'>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Roboto:wght@100&amp;display=swap'>
    <link rel="icon" href="https://image.flaticon.com/icons/svg/2983/2983687.svg">
    @vite('resources/scss/app.scss')
    <title>{{ env('APP_NAME' )}}</title>
    <script src="https://cdn.jsdelivr.net/npm/ismobilejs@1/dist/isMobile.min.js"></script>
</head>
<body>
<div id="app">
    <app></app>
</div>
@vite('resources/js/app.js')
</body>
</html>