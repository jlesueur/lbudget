<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>LBudget</title>

        <!-- Styles -->
		<link href="/css/app.css" rel="stylesheet" type="text/css">

    </head>
    <body>
    <header>
        <div class="top-right links">
            @if (Auth::check())
                <a href="{{ url('/home') }}">Home</a>
                <a href="{{ url('/account') }}">Settings</a>
                <a href="{{ url('/logout') }}">Logout</a>
            @else
                <a href="{{ url('/login') }}">Login</a>
                <a href="{{ url('/register') }}">Register</a>
            @endif
        </div>
        @yield('header')
    </header>
		@yield('content')
	<footer>
<script>
    window.Laravel = {!! json_encode([
        'csrfToken' => csrf_token(),
    ]) !!};
</script>
		<script src='/js/app.js'></script>
		@yield('pagejs')
	</footer>
	</body>
</html>
