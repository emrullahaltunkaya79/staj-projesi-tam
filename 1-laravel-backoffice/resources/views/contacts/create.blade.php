@extends('layouts.app')
@section('title', 'Yeni Cari')

@section('content')
    <form method="POST" action="{{ route('cariler.store') }}">
        @csrf
        @include('contacts._form')
    </form>
@endsection
