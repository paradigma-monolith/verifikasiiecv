<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verifikasi Keaslian E-ICV — BKK Kelas I Bandung | Kemenkes RI</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logokemenkes.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased selection:bg-teal-100 selection:text-teal-900">
    <div id="app" data-base-url="{{ rtrim(url('/'), '/') }}"></div>
</body>
</html>
