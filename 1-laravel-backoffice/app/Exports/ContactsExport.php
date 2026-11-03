<?php

namespace App\Exports;

use App\Models\Contact;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContactsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    // Global scope (şirket ayrımı) indirme isteği sırasında çalışır
    public function query()
    {
        return Contact::query()->orderBy('name');
    }

    public function headings(): array
    {
        return ['ID', 'Tip', 'Ünvan', 'Telefon', 'E-posta', 'Vergi No', 'Bakiye (₺)'];
    }

    public function map($contact): array
    {
        return [
            $contact->id,
            $contact->typeLabel(),
            $contact->name,
            $contact->phone,
            $contact->email,
            $contact->tax_number,
            (float) $contact->balance,
        ];
    }
}
