@extends('layouts.app')
@section('title', 'Ürün Düzenle: '.$product->name)

@section('content')
    <form method="POST" action="{{ route('urunler.update', $product) }}">
        @csrf @method('PUT')
        @include('products._form')
    </form>
@endsection
