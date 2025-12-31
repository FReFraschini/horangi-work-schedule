<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <v-app>
            <v-app-bar app color="primary" dark>
                <v-toolbar-title>
                    <a href="{{ url('/') }}" style="color: white; text-decoration: none;">
                        {{ config('app.name', 'Laravel') }}
                    </a>
                </v-toolbar-title>
                <v-spacer></v-spacer>
                @guest
                    @if (Route::has('login'))
                        <v-btn text href="{{ route('login') }}">{{ __('Login') }}</v-btn>
                    @endif
                    @if (Route::has('register'))
                        <v-btn text href="{{ route('register') }}">{{ __('Register') }}</v-btn>
                    @endif
                @else
                    <v-menu offset-y>
                        <template v-slot:activator="{ props }">
                            <v-btn text v-bind="props">
                                {{ Auth::user()->name }}
                                <v-icon right>mdi-menu-down</v-icon>
                            </v-btn>
                        </template>
                        <v-list>
                            <v-list-item href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <v-list-item-title>{{ __('Logout') }}</v-list-item-title>
                            </v-list-item>
                        </v-list>
                    </v-menu>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                @endguest
            </v-app-bar>

            <v-main>
                @yield('content')
            </v-main>
        </v-app>
    </div>
</body>
</html>
