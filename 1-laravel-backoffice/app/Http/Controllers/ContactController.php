<?php

namespace App\Http\Controllers;

use App\Exports\ContactsExport;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::query()->orderBy('name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        return view('contacts.index', [
            'contacts' => $query->paginate(15)->withQueryString(),
            'filters' => ['search' => $search, 'type' => $type],
        ]);
    }

    /**
     * Cari Detay ve Ekstre: carinin tüm bilgileri, bakiye özeti,
     * ona kesilen geçmiş faturalar (vade + gecikme dahil) ve tahsilat geçmişi.
     */
    public function show(Contact $contact)
    {
        $invoices = $contact->invoices()->latest('id')->paginate(10, ['*'], 'sayfa');
        $payments = $contact->payments()->latest('id')->paginate(10, ['*'], 'sayfa2');

        // Tahsilatları en eski faturadan başlayarak (FIFO) dağıt: durum, kalan tutar ve gecikme
        // Yalnızca tahsilat yönü ödemeler dağıtılır; iade edilmiş faturalar kalan bırakmaz.
        $refundedIds = $contact->invoices()
            ->whereNotNull('refunded_invoice_id')
            ->pluck('refunded_invoice_id')
            ->flip();

        $remainingPaid = (float) $contact->payments()->where('direction', 'tahsilat')->sum('amount');
        $statuses = [];
        $aging = ['vadesi_gecmedi' => 0.0, 'gun_1_30' => 0.0, 'gun_31_60' => 0.0, 'gun_60_ustu' => 0.0];

        foreach ($contact->invoices()->orderBy('issue_date')->orderBy('id')->get() as $invoice) {
            $applied = min((float) $invoice->total, $remainingPaid);
            $remainingPaid = round($remainingPaid - $applied, 2);

            if ($refundedIds->has($invoice->id)) {
                $applied = (float) $invoice->total; // iade ile tamamen kapatıldı sayılır
            }
            $remaining = round((float) $invoice->total - $applied, 2);

            if ($applied <= 0.001) {
                $status = 'acik';
            } elseif ($applied + 0.001 >= (float) $invoice->total) {
                $status = 'kapali';
            } else {
                $status = 'kismi';
            }

            $daysOverdue = 0;
            if ($remaining > 0.001 && $invoice->due_date && $invoice->due_date->endOfDay()->isPast()) {
                // Carbon 3 diffInDays işaretli döndürdüğü için mutlak değer alınır
                $daysOverdue = (int) ceil(abs(now()->diffInDays($invoice->due_date)));
            }

            $statuses[$invoice->id] = [
                'status' => $status,
                'remaining' => $remaining,
                'days_overdue' => $daysOverdue,
            ];

            if ($remaining > 0.001) {
                if ($daysOverdue === 0) {
                    $aging['vadesi_gecmedi'] = round($aging['vadesi_gecmedi'] + $remaining, 2);
                } elseif ($daysOverdue <= 30) {
                    $aging['gun_1_30'] = round($aging['gun_1_30'] + $remaining, 2);
                } elseif ($daysOverdue <= 60) {
                    $aging['gun_31_60'] = round($aging['gun_31_60'] + $remaining, 2);
                } else {
                    $aging['gun_60_ustu'] = round($aging['gun_60_ustu'] + $remaining, 2);
                }
            }
        }

        $summary = [
            'invoice_count' => (int) $contact->invoices()->count(),
            'invoice_total' => (float) $contact->invoices()->sum('total'),
            'payment_total' => (float) $contact->payments()->sum('amount'),
            'last_invoice_date' => $contact->invoices()->max('issue_date'),
            'aging' => $aging,
        ];

        return view('contacts.show', compact('contact', 'invoices', 'payments', 'statuses', 'summary'));
    }

    public function create()
    {
        return view('contacts.create', ['contact' => new Contact(['type' => 'musteri'])]);
    }

    /**
     * Cari listesini Excel (.xlsx) olarak indirir.
     */
    public function export()
    {
        return (new ContactsExport)->download('cariler-'.now()->format('Ymd-His').'.xlsx');
    }

    public function store(ContactRequest $request)
    {
        $contact = Contact::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => "«{$contact->name}» carisi oluşturuldu."]);
        }

        return redirect()->route('cariler.index')
            ->with('success', 'Cari kaydı oluşturuldu.');
    }

    public function edit(Contact $contact)
    {
        return view('contacts.edit', compact('contact'));
    }

    public function update(ContactRequest $request, Contact $contact)
    {
        $contact->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => "«{$contact->name}» güncellendi."]);
        }

        return redirect()->route('cariler.index')
            ->with('success', 'Cari kaydı güncellendi.');
    }

    public function destroy(Request $request, Contact $contact)
    {
        if (! $request->user()->can('delete', $contact)) {
            return redirect()->route('cariler.index')
                ->with('error', 'Silme işlemi için yönetici yetkisi gerekiyor.');
        }

        if ($contact->invoices()->exists()) {
            return redirect()->route('cariler.index')
                ->with('error', 'Bu cariye ait faturalar bulunduğundan silinemez.');
        }

        $contact->delete();

        return redirect()->route('cariler.index')
            ->with('success', 'Cari kaydı silindi.');
    }
}
