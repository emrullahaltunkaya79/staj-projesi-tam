<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Panel: iletişim formundan gelen müşteri mesajları. */
class ContactMessageController extends Controller
{
    public function index()
    {
        $mesajlar = DB::table('contact_messages')
            ->orderByRaw('okundu_at IS NULL DESC, created_at DESC')
            ->paginate(20);

        return view('mesajlar.index', compact('mesajlar'));
    }

    public function okundu(Request $request, int $id)
    {
        DB::table('contact_messages')->where('id', $id)->update(['okundu_at' => now()]);

        return redirect()->route('mesajlar.index')->with('success', 'Mesaj okundu olarak işaretlendi.');
    }

    public function destroy(int $id)
    {
        DB::table('contact_messages')->where('id', $id)->delete();

        return redirect()->route('mesajlar.index')->with('success', 'Mesaj silindi.');
    }
}
