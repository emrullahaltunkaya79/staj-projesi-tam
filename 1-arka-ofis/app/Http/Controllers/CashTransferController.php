<?php

namespace App\Http\Controllers;

use App\Models\CashTransfer;
use Illuminate\Http\Request;

class CashTransferController extends Controller
{
    public function index()
    {
        return view('transfers.index', [
            'transfers' => CashTransfer::latest('transfer_date')->latest('id')->paginate(15),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'direction' => ['required', 'in:nakit_havale,havale_nakit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:500'],
            'transfer_date' => ['required', 'date'],
        ], [
            'direction.in' => 'Transfer yönü geçersiz.',
            'amount.required' => 'Tutar zorunludur.',
            'amount.min' => 'Tutar sıfırdan büyük olmalıdır.',
            'transfer_date.required' => 'Tarih zorunludur.',
        ]);

        CashTransfer::create($validated);

        $label = $validated['direction'] === 'nakit_havale' ? 'Kasa → Banka' : 'Banka → Kasa';

        return redirect()->route('virmanlar.index')
            ->with('success', "{$label} transferi kaydedildi: ".number_format($validated['amount'], 2, ',', '.').' ₺');
    }
}
