<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Cari içe aktarma şablonu: beklenen sütunları ve örnek satırı içerir.
 */
class ContactsImportTemplate implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['Tip (Müşteri/Tedarikçi)', 'Ünvan', 'Telefon', 'E-posta', 'Vergi No', 'Adres'];
    }

    public function array(): array
    {
        return [
            ['Müşteri', 'Örnek Market', '0532 000 00 00', 'ornek@market.com', '1234567890', 'Örnek Mah. No:1 İstanbul'],
            ['Tedarikçi', 'Örnek Tedarik A.Ş.', '0216 000 00 00', 'info@ornektedarik.com', '0987654321', 'Örnek Sanayi Sitesi No:5'],
        ];
    }
}
