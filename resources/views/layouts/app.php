<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'JosBook')</title>
    <link rel="stylesheet" href="{{ asset('css/josbook.css') }}">
</head>
<body>
    @yield('content')
    <script src="{{ asset('js/josbook.js') }}"></script>
</body>
</html>
