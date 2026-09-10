@extends('layouts.master')

@section('content')
    <Allorders :user='{!! json_encode(Auth::user()) !!}'>
    </Allorders>
@endsection
