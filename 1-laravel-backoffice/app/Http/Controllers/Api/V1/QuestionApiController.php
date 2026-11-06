<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Ürün soru-cevap: müşteri sorar, satıcı panelden yanıtlar; yalnız cevaplananlar mağazada görünür.
 */
class QuestionApiController extends Controller
{
    /** GET /api/v1/products/{id}/sorular — cevaplanmış sorular (e-postalar gizli) */
    public function index(int $id): JsonResponse
    {
        $sorular = DB::table('product_questions')
            ->where('product_id', $id)
            ->whereNotNull('cevaplandi_at')
            ->orderByDesc('cevaplandi_at')
            ->limit(30)
            ->get(['id', 'ad', 'soru', 'cevap', 'created_at', 'cevaplandi_at']);

        return response()->json([
            'data' => $sorular->map(fn ($s) => [
                'id' => $s->id,
                // Gizlilik: yalnızca ad ve soyadın baş harfi
                'ad' => $this->kisaltilmisAd($s->ad),
                'soru' => $s->soru,
                'cevap' => $s->cevap,
                'tarih' => $s->created_at,
            ]),
        ]);
    }

    /** POST /api/v1/products/{id}/sorular { ad, email, soru } */
    public function store(Request $request, int $id): JsonResponse
    {
        $urun = Product::findOrFail($id);

        $veri = $request->validate([
            'ad' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:120'],
            'soru' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'soru.min' => 'Sorunuz en az 10 karakter olmalıdır.',
            'email.email' => 'Geçerli bir e-posta adresi girin.',
        ]);

        DB::table('product_questions')->insert($veri + [
            'company_id' => $urun->company_id,
            'product_id' => $urun->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Sorunuz satıcıya iletildi. Yanıtlandığında e-posta ile haber vereceğiz.'], 201);
    }

    /** GET /api/v1/sorular — satıcı paneli: tüm sorular, bekleyenler önce */
    public function satici(): JsonResponse
    {
        $sorular = DB::table('product_questions')
            ->join('products', 'products.id', '=', 'product_questions.product_id')
            ->orderByRaw('product_questions.cevaplandi_at IS NOT NULL')
            ->orderByDesc('product_questions.created_at')
            ->limit(200)
            ->get([
                'product_questions.id', 'product_questions.ad', 'product_questions.email',
                'product_questions.soru', 'product_questions.cevap', 'product_questions.created_at',
                'product_questions.cevaplandi_at', 'products.id as urun_id', 'products.name as urun_adi',
            ]);

        return response()->json(['data' => $sorular]);
    }

    /** PATCH /api/v1/sorular/{id}/cevap { cevap } */
    public function cevapla(Request $request, int $id): JsonResponse
    {
        $veri = $request->validate([
            'cevap' => ['required', 'string', 'min:2', 'max:2000'],
        ], ['cevap.required' => 'Cevap boş olamaz.']);

        $soru = DB::table('product_questions')->where('id', $id)->first();
        abort_unless($soru, 404, 'Soru bulunamadı.');

        DB::table('product_questions')->where('id', $id)->update([
            'cevap' => $veri['cevap'],
            'cevaplandi_at' => now(),
            'updated_at' => now(),
        ]);

        $urunAdi = Product::withTrashed()->find($soru->product_id)?->name ?? 'ürün';
        Mail::raw(
            "Merhaba {$soru->ad},\n\n\"{$urunAdi}\" hakkındaki sorunuz yanıtlandı:\n\n".
            "Soru: {$soru->soru}\nCevap: {$veri['cevap']}\n\nHepsiBirarada",
            fn ($m) => $m->to($soru->email)->subject("Sorunuz yanıtlandı — {$urunAdi}"),
        );

        return response()->json(['message' => 'Cevap yayınlandı ve müşteriye e-posta gönderildi.']);
    }

    /** DELETE /api/v1/sorular/{id} */
    public function sil(int $id): JsonResponse
    {
        abort_if(DB::table('product_questions')->where('id', $id)->delete() === 0, 404, 'Soru bulunamadı.');

        return response()->json(['message' => 'Soru silindi.']);
    }

    private function kisaltilmisAd(string $ad): string
    {
        $parcalar = preg_split('/\s+/u', trim($ad)) ?: [$ad];
        $ilk = array_shift($parcalar);

        return $parcalar ? $ilk.' '.mb_substr(end($parcalar), 0, 1).'.' : $ilk;
    }
}
