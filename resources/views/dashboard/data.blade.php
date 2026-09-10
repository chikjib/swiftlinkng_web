@extends('layouts.master')

@section('content')
    <Buydata :user='{!! json_encode(Auth::user()) !!}'>
    </Buydata>
@endsection
