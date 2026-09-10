@extends('layouts.master')

@section('content')
    <Airtime :user='{!! json_encode(Auth::user()) !!}'>
    </Airtime>
@endsection
