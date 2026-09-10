@extends('layouts.master')

@section('content')
    <Allusers :role="{{ $role }}">
    </Allusers>
@endsection
