@extends('layouts.app')
@section('title', 'Yeni Ürün')

@section('content')
    <form method="POST" action="{{ route('urunler.store') }}">
        @csrf
        @include('products._form')
    </form>
@endsection
