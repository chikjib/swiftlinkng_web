@extends('layouts.master')

@section('content')
    <allcommissions :user='{!! json_encode(Auth::user()) !!}'>
    </allcommissions>
@endsection
