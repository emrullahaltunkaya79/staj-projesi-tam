Merhaba {{ $ad }},

Sepetinizde bekleyen ürünler var:

@foreach ($ogeler as $oge)
- {{ $oge['ad'] }} × {{ $oge['adet'] }} adet
@endforeach

Siparişinizi tamamlamak için: {{ $baglanti }}

İyi alışverişler dileriz,
HepsiBirarada — Teknoloji & Ofis
