@extends('layouts.app')

@section('content')
<login-form
    login-route="{{ route('login') }}"
    csrf-token="{{ csrf_token() }}"
    old-email="{{ old('email', '') }}"
    :has-errors="{{ $errors->any() ? 'true' : 'false' }}"
    error-message="{{ $errors->first() }}"
></login-form>
@endsection
