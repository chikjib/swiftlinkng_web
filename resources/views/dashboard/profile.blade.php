@extends('layouts.master')

@section('content')
    <Profile :user='{!! json_encode(Auth::user()) !!}'>
    </Profile>
@endsection
