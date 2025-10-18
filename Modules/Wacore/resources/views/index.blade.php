@extends('wacore::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('wacore.name') !!}</p>
@endsection
