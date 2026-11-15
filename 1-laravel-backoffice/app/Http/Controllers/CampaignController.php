<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;

/** Panel: paket kampanyaları — "3 al 2 öde" ve "Eşik üstü yarı fiyat". */
class CampaignController extends Controller
{
    public function index()
    {
        $kampanyalar = Campaign::with('urun:id,name')->orderByDesc('id')->get();

        return view('campaigns.index', compact('kampanyalar'));
    }

    public function store(Request $request)
    {
        $veri = $request->validate([
            'ad' => ['required', 'string', 'max:100'],
            'tur' => ['required', 'in:x_al_y_ode,esik_yarim'],
            'urun_id' => ['nullable', 'integer', 'exists:products,id'],
            'al_adet' => ['nullable', 'integer', 'min:2', 'max:10'],
            'ode_adet' => ['nullable', 'integer', 'min:1', 'max:9'],
            'esik' => ['nullable', 'numeric', 'min:0'],
            'aktif' => ['nullable', 'boolean'],
        ]);

        Campaign::create([
            'company_id' => 1,
            'ad' => $veri['ad'],
            'tur' => $veri['tur'],
            'urun_id' => $veri['urun_id'] ?? null,
            'al_adet' => $veri['al_adet'] ?? 3,
            'ode_adet' => $veri['ode_adet'] ?? 2,
            'esik' => $veri['esik'] ?? 0,
            'aktif' => (bool) ($veri['aktif'] ?? true),
        ]);

        return redirect()->route('kampanyalar.index')->with('success', 'Kampanya oluşturuldu.');
    }

    public function toggle(Campaign $campaign)
    {
        $campaign->update(['aktif' => ! $campaign->aktif]);

        return redirect()->route('kampanyalar.index')->with('success', 'Kampanya durumu güncellendi.');
    }

    public function destroy(Campaign $campaign)
    {
        $campaign->delete();

        return redirect()->route('kampanyalar.index')->with('success', 'Kampanya silindi.');
    }
}
