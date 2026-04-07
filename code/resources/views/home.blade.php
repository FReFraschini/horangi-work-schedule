@extends('layouts.app')

@section('content')
    @can('is-gestore')
        <schedule-dashboard></schedule-dashboard>
    @else
        <operator-dashboard :current-user-id="{{ auth()->id() }}"></operator-dashboard>
    @endcan
@endsection
