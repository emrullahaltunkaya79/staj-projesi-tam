<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Contact;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService)
    {
    }

    public function index()
    {
        return view('payments.index', [
            'payments' => Payment::with('contact')->latest('id')->paginate(9),
        ]);
    }

    public function create(Request $request)
    {
        // JS tarafına bakiye bilgisi taşınan sadeleştirilmiş cari listesi
        $contacts = Contact::where('type', 'musteri')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'balance' => (float) $c->balance,
            ]);

        return view('payments.create', [
            'contacts' => $contacts,
            // Cari detay sayfasından gelindiyse cari önceden seçili gelir
            'selectedContactId' => old('contact_id', $request->query('contact_id')),
        ]);
    }

    public function createSupplier(Request $request)
    {
        $suppliers = Contact::where('type', 'tedarikci')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'balance' => (float) $c->balance, // negatif = borcumuz
            ]);

        return view('payments.create_supplier', [
            'contacts' => $suppliers,
            'selectedContactId' => old('contact_id', $request->query('contact_id')),
        ]);
    }

    public function storeSupplier(StorePaymentRequest $request)
    {
        $payment = $this->paymentService->paySupplier(
            $request->user()->company_id,
            $request->validated()
        );

        $message = sprintf(
            'Ödeme kaydedildi — %s tedarikçisine olan borç %s ₺ azaldı.',
            $payment->contact->name,
            number_format((float) $payment->amount, 2, ',', '.')
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('cariler.show', $payment->contact_id)
            ->with('success', $message);
    }

    public function store(StorePaymentRequest $request)
    {
        $payment = $this->paymentService->recordPayment(
            $request->user()->company_id,
            $request->validated()
        );

        $message = sprintf(
            'Tahsilat kaydedildi — %s bakiyesi %s ₺ azaltıldı.',
            $payment->contact->name,
            number_format((float) $payment->amount, 2, ',', '.')
        );

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('cariler.show', $payment->contact_id)
            ->with('success', $message);
    }
}
