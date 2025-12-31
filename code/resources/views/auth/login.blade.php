@extends('layouts.app')

@section('content')
<v-container class="fill-height" fluid>
  <v-row align="center" justify="center">
    <v-col cols="12" sm="8" md="4">
      <v-card class="elevation-12">
        <v-toolbar color="primary" dark flat>
          <v-toolbar-title>{{ __('Login') }}</v-toolbar-title>
        </v-toolbar>
        <v-card-text>
          <form method="POST" action="{{ route('login') }}">
            @csrf
            <v-text-field
              label="{{ __('Email Address') }}"
              name="email"
              prepend-icon="mdi-account"
              type="email"
              :value="old('email')"
              required
              autofocus
              :error-messages="$errors->first('email')"
            ></v-text-field>
            <v-text-field
              label="{{ __('Password') }}"
              name="password"
              prepend-icon="mdi-lock"
              type="password"
              required
              :error-messages="$errors->first('password')"
            ></v-text-field>
            <v-checkbox
              name="remember"
              id="remember"
              label="{{ __('Remember Me') }}"
              :checked="old('remember')"
            ></v-checkbox>
            <v-btn type="submit" color="primary" block class="mt-4">{{ __('Login') }}</v-btn>
          </form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          @if (Route::has('password.request'))
            <a class="btn btn-link" href="{{ route('password.request') }}">
              {{ __('Forgot Your Password?') }}
            </a>
          @endif
        </v-card-actions>
      </v-card>
    </v-col>
  </v-row>
</v-container>
@endsection
