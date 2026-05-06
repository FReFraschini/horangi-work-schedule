<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Gestione Turni') }}</title>
    <meta name="theme-color" content="#1A73E8">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/logo.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon-180x180.png">
    <link rel="manifest" href="/build/manifest.webmanifest">
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Google+Sans:400,500,700|Nunito:400,500,600" rel="stylesheet">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <v-app>
            <v-app-bar elevation="0" border="b">
                <template v-slot:prepend>
                    <v-app-bar-nav-icon>
                        <v-icon color="primary">mdi-calendar-clock</v-icon>
                    </v-app-bar-nav-icon>
                </template>

                <v-app-bar-title>
                    <a href="{{ url('/') }}" style="text-decoration:none; color:inherit; font-weight:500;">
                        {{ config('app.name', 'Gestione Turni') }}
                    </a>
                </v-app-bar-title>

                <template v-slot:append>
                    <!-- Toggle tema -->
                    <v-btn icon variant="text" @click="toggleTheme">
                        <v-icon v-if="isDark">mdi-weather-sunny</v-icon>
                        <v-icon v-else>mdi-weather-night</v-icon>
                    </v-btn>

                    @auth
                        <v-menu location="bottom end">
                            <template v-slot:activator="{ props }">
                                <v-btn variant="text" v-bind="props" class="ml-1">
                                    <v-avatar color="primary" size="32" class="mr-2">
                                        <span class="text-body-2 font-weight-bold" style="color:white;">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </span>
                                    </v-avatar>
                                    {{ Auth::user()->name }}
                                    <v-icon end size="small">mdi-chevron-down</v-icon>
                                </v-btn>
                            </template>
                            <v-list rounded="lg" elevation="3" min-width="180">
                                <v-list-item
                                    prepend-icon="mdi-logout"
                                    title="Logout"
                                    href="{{ route('logout') }}"
                                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                ></v-list-item>
                            </v-list>
                        </v-menu>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    @endauth

                    @guest
                        <v-btn variant="text" href="{{ route('login') }}">Login</v-btn>
                    @endguest
                </template>
            </v-app-bar>

            <v-main>
                <v-container fluid class="pa-4 pa-md-6">
                    @yield('content')
                </v-container>
            </v-main>
        </v-app>
    </div>
</body>
</html>
