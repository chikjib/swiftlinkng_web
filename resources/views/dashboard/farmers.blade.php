@extends('layouts.master')

@section('content')
    <findfarmer :agent='{!! json_encode(Auth::user()) !!}'>
    </findfarmer>
@endsection
