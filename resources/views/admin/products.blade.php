@extends('layouts.master')

@section('content')
    <Allproducts :user='{!! json_encode(Auth::user()) !!}'>
    </Allproducts>
@endsection
