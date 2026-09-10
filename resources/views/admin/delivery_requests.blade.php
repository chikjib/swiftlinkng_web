@extends('layouts.master')

@section('content')
    <Alldeliveries :user='{!! json_encode(Auth::user()) !!}'>
    </Alldeliveries>
@endsection
