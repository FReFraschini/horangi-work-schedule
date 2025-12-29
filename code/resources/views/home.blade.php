@extends('layouts.app')

@section('content')
<div class="container">
    @can('is-gestore')
        <schedule-dashboard></schedule-dashboard>
    @else
        <operator-dashboard></operator-dashboard>
    @endcan
</div>
@endsection
