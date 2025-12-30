@extends('layouts.app')

@section('content')
<v-app>
    <div class="container">
        @can('is-gestore')
            <schedule-dashboard></schedule-dashboard>
        @else
            <operator-dashboard></operator-dashboard>
        @endcan
    </div>
</v-app>
@endsection
