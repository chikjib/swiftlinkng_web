@extends('layouts.master')

@section('content')
    <Airtime2Cash :user='{!! json_encode(Auth::user()) !!}'>
    </Airtime2Cash>
@endsection
