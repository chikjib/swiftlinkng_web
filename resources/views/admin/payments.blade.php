@extends('layouts.master')

@section('content')
    <allpayments :user='{!! json_encode(Auth::user()) !!}'>
    </allpayments>
@endsection
