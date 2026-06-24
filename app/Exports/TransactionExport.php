<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected $year = null,
        protected $month = null,
        protected $category = null,
        protected $type = null,
        protected $priceFilter = null,
        protected $sort = 'latest'
    ) {
        $this->sort = $sort === 'oldest' ? 'oldest' : 'latest';
    }

    public function query()
    {
        $query = Transaction::with(['item', 'user', 'approver'])
            ->visibleFor(auth()->user())
            ->approved();

        if ($this->year) {
            $query->whereYear('date', $this->year);
        }

        if ($this->month) {
            $query->whereMonth('date', $this->month);
        }

        if ($this->category) {
            $query->whereHas('item', fn($q) => $q->where($this->isTeknikReport() ? 'component' : 'category', $this->category));
        }

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if (!$this->isTeknikReport() && $this->priceFilter && $this->year) {
            $operator = $this->priceFilter === 'tertinggi' ? 'MAX' : null;
            $operator = $this->priceFilter === 'terendah' ? 'MIN' : $operator;

            if ($operator) {
                $query->whereIn('id', function ($sub) use ($operator) {
                    $sub->select('t2.id')
                        ->from('transactions as t2')
                        ->whereYear('t2.date', $this->year)
                        ->whereNotNull('t2.price')
                        ->whereColumn('t2.item_id', 'transactions.item_id')
                        ->whereRaw("t2.price = (
                            SELECT {$operator}(t3.price)
                            FROM transactions t3
                            WHERE t3.item_id = t2.item_id
                            AND t3.price IS NOT NULL
                            AND YEAR(t3.date) = ?
                        )", [$this->year]);
                });
            }
        }

        $direction = $this->sort === 'oldest' ? 'asc' : 'desc';

        return $query
            ->orderBy('date', $direction)
            ->orderBy('created_at', $direction)
            ->orderBy('id', $direction);
    }

    public function headings(): array
    {
        if ($this->isTeknikReport()) {
            return [
                'No',
                'Tanggal',
                'Jenis',
                'No Normalisasi',
                'Nama Barang',
                'Komponen',
                'Ship Unloader',
                'Lokasi',
                'Volume',
                'Jumlah',
                'Satuan',
                'User',
                'Status',
            ];
        }

        return [
            'No',
            'Tanggal',
            'Nama Barang',
            'Kategori',
            'Jenis',
            'Jumlah',
            'Satuan',
            'Harga Satuan',
            'User',
            'Keterangan',
            'Status',
            'Disetujui Oleh',
            'Tanggal Approval',
        ];
    }

    public function map($transaction): array
    {
        static $no = 0;
        $no++;

        if ($this->isTeknikReport()) {
            return [
                $no,
                $transaction->date->format('d/m/Y'),
                $transaction->type_label,
                $transaction->no_normalisasi ?: ($transaction->item->no_normalisasi ?? '-'),
                $transaction->item->name ?? '-',
                $transaction->item->component ?? '-',
                $transaction->ship_unloader_label,
                $transaction->lokasi ?: ($transaction->item->lokasi ?? '-'),
                $transaction->volume ?? '-',
                $transaction->quantity,
                $transaction->item->unit ?? '-',
                $transaction->user->name ?? '-',
                'Auto Approve',
            ];
        }

        return [
            $no,
            $transaction->date->format('d/m/Y'),
            $transaction->item->name ?? '-',
            $transaction->item->category ?? '-',
            strtoupper($transaction->type),
            $transaction->quantity,
            $transaction->item->unit ?? '-',
            $transaction->price === null ? '-' : 'Rp ' . number_format($transaction->price, 0, ',', '.'),
            $transaction->user->name ?? '-',
            $transaction->description ?? '-',
            strtoupper($transaction->status),
            $transaction->approver->name ?? '-',
            $transaction->approved_at ? $transaction->approved_at->format('d/m/Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '10B981'],
                ],
            ],
        ];
    }

    private function isTeknikReport(): bool
    {
        return auth()->user()?->bidang === 'teknik';
    }
}
