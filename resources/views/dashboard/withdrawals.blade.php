@extends('layouts.master')

@section('content')
    <allwithdrawals :user='{!! json_encode(Auth::user()) !!}'>
    </allwithdrawals>
@endsection
