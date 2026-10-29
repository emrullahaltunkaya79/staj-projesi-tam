<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Http\Request;

/**
 * Geri dönüşüm kutusu: soft delete ile silinen kayıtları listeler ve
 * yöneticinin geri yüklemesine izin verir.
 */
class RecycleBinController extends Controller
{
    private const TYPES = [
        'contact' => ['label' => 'Cariler', 'class' => Contact::class],
        'product' => ['label' => 'Ürünler', 'class' => Product::class],
        'expense' => ['label' => 'Giderler', 'class' => Expense::class],
        'quote' => ['label' => 'Teklifler', 'class' => Quote::class],
    ];

    public function index(Request $request)
    {
        abort_unless($request->user()->can('manage', \App\Models\User::class), 403);

        $type = $request->get('type', 'contact');
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $class = self::TYPES[$type]['class'];

        $items = $class::onlyTrashed()->latest('deleted_at')->paginate(15)->withQueryString();

        return view('recycle.index', [
            'type' => $type,
            'typeLabel' => self::TYPES[$type]['label'],
            'types' => self::TYPES,
            'items' => $items,
        ]);
    }

    public function restore(Request $request, string $type, int $id)
    {
        abort_unless($request->user()->can('manage', \App\Models\User::class), 403);
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $class = self::TYPES[$type]['class'];
        $model = $class::onlyTrashed()->where('company_id', $request->user()->company_id)->findOrFail($id);

        $model->restore(); // restored olayı denetim kaydı oluşturur

        return redirect()->route('geri-donusum.index', ['type' => $type])
            ->with('success', 'Kayıt geri yüklendi.');
    }
}
