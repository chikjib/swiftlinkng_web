@extends('layouts.master')

@section('content')
    <Farmer-Agent-Details :id={{ $id }} :agent='{!! json_encode(Auth::user()) !!}' :uid={{ $uid }}>
    </Farmer-Agent-Details>
@endsection
