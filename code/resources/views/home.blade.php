@extends('layouts.app')

@section('content')
    @can('is-gestore')
        <schedule-dashboard></schedule-dashboard>
    @else
        <operator-dashboard></operator-dashboard>
    @endcan
@endsection
