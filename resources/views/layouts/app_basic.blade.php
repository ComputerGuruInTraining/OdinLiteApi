<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ODIN') }}</title>

    <!-- Styles -->
    <link href="/css/app.css" rel="stylesheet">

    <!-- Scripts -->
    <script>
        window.Laravel = <?php echo json_encode([
            'csrfToken' => csrf_token(),
        ]); ?>
    </script>
</head>
<body style="background-color: #ffffff;">
    <div id="app">

        @yield('content')
        {{--<div style="position: absolute; bottom:50px; right:15px;">--}}
        	@include('footer')
        {{--</div>--}}
    </div>

    <!-- Scripts -->
    <script src="/js/app.js"></script>
</body>
</html>