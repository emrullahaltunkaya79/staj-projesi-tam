<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>3D Secure Doğrulama — HepsiBirarada</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, sans-serif; }
        body { background: #f1f5f9; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .kart { background: #fff; border-radius: 16px; box-shadow: 0 20px 50px rgba(15,23,42,.15); max-width: 430px; width: 100%; overflow: hidden; }
        .baslik { background: linear-gradient(100deg, var(--marka, #6366f1) 0%, #1a1a2e 100%); color: #fff; padding: 20px 24px; }
        .baslik h1 { font-size: 17px; font-weight: 800; }
        .baslik p { font-size: 12px; opacity: .8; margin-top: 3px; }
        .govde { padding: 24px; }
        .tutar { display: flex; justify-content: space-between; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; font-size: 14px; }
        .tutar b { font-size: 16px; }
        label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; margin: 12px 0 5px; }
        input { width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 12px; font-size: 15px; letter-spacing: .04em; }
        input:focus { outline: 2px solid #6366f1; border-color: transparent; }
        .satir { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .sms { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 12px; font-size: 13px; color: #92400e; margin-top: 16px; }
        .sms code { font-weight: 800; font-size: 15px; letter-spacing: .15em; }
        button { width: 100%; margin-top: 20px; background: #0f172a; color: #fff; border: 0; border-radius: 999px; padding: 13px; font-size: 14px; font-weight: 800; cursor: pointer; }
        button:hover { background: #1e293b; }
        .hata { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 8px; padding: 10px 12px; font-size: 13px; margin-bottom: 14px; }
        .not { font-size: 11px; color: #94a3b8; margin-top: 14px; text-align: center; }
    </style>
</head>
<body>
    <div class="kart">
        <div class="baslik">
            <h1>3D Secure Doğrulama</h1>
            <p>Sipariş: {{ $siparis->order_no }}</p>
        </div>
        <form class="govde" method="POST" action="{{ url('/odeme/3ds/' . $token) }}">
            @csrf
            <div class="tutar">
                <span>Ödenecek tutar</span>
                <b>{{ number_format((float) $siparis->total, 2, ',', '.') }} ₺</b>
            </div>
            @if ($hata)
                <div class="hata">{{ $hata }}</div>
            @endif
            <label for="kart_no">Kart Numarası</label>
            <input id="kart_no" name="kart_no" inputmode="numeric" maxlength="16" placeholder="0000 0000 0000 0000"
                   value="{{ old('kart_no') }}" required pattern="[0-9 ]{15,19}">
            <div class="satir">
                <div>
                    <label for="son_kullanma">Son Kul. (AA/YY)</label>
                    <input id="son_kullanma" name="son_kullanma" placeholder="12/28" maxlength="5"
                           value="{{ old('son_kullanma') }}" required>
                </div>
                <div>
                    <label for="cvv">CVV</label>
                    <input id="cvv" name="cvv" inputmode="numeric" maxlength="4" placeholder="000"
                           value="{{ old('cvv') }}" required>
                </div>
            </div>
            <label for="otp">SMS Onay Kodu</label>
            <input id="otp" name="otp" inputmode="numeric" maxlength="6" placeholder="000000"
                   value="{{ old('otp') }}" required>
            @if ($smsKodu)
                <div class="sms">
                    Sandbox: telefonunuza gelen kod <code>{{ $smsKodu }}</code>
                </div>
            @endif
            <button type="submit">🔒 Onayla ve Ödemeyi Tamamla</button>
            <p class="not">Bu sayfa bir 3D Secure doğrulama ekranıdır — kart bilgileriniz mağazada saklanmaz.</p>
        </form>
    </div>
</body>
</html>
