<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $dateFrom;
    protected $dateTo;
    protected $category;
    protected $type;
    protected $year;
    protected $priceFilter;
    protected $sort;

    public function __construct(
        $dateFrom = null,
        $dateTo = null,
        $category = null,
        $type = null,
        $year = null,
        $priceFilter = null,
        $sort = 'latest'
    ) {
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->category = $category;
        $this->type = $type;
        $this->year = $year;
        $this->priceFilter = $priceFilter;
        $this->sort = $sort === 'oldest' ? 'oldest' : 'latest';
    }

    public function query()
    {
        $query = Transaction::with(['item', 'user', 'approver'])
            ->visibleFor(auth()->user())
            ->approved();

        if ($this->dateFrom) {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        if ($this->year) {
            $query->whereYear('date', $this->year);
        }

        if ($this->category) {
            $query->whereHas('item', function ($q) {
                $q->where('category', $this->category);
            });
        }

        if ($this->type) {
            $query->where('type', $this->type);
        }

        // 🔥 FILTER HARGA (SAMA DENGAN CONTROLLER)
        if ($this->priceFilter && $this->year) {

            if ($this->priceFilter == 'tertinggi') {
                $query->whereIn('id', function ($sub) {
                    $sub->select('t2.id')
                        ->from('transactions as t2')
                        ->whereYear('t2.date', $this->year)
                        ->whereNotNull('t2.price')
                        ->whereColumn('t2.item_id', 'transactions.item_id')
                        ->whereRaw('t2.price = (
                            SELECT MAX(t3.price)
                            FROM transactions t3
                            WHERE t3.item_id = t2.item_id
                            AND t3.price IS NOT NULL
                            AND YEAR(t3.date) = ?
                        )', [$this->year]);
                });
            }

            if ($this->priceFilter == 'terendah') {
                $query->whereIn('id', function ($sub) {
                    $sub->select('t2.id')
                        ->from('transactions as t2')
                        ->whereYear('t2.date', $this->year)
                        ->whereNotNull('t2.price')
                        ->whereColumn('t2.item_id', 'transactions.item_id')
                        ->whereRaw('t2.price = (
                            SELECT MIN(t3.price)
                            FROM transactions t3
                            WHERE t3.item_id = t2.item_id
                            AND t3.price IS NOT NULL
                            AND YEAR(t3.date) = ?
                        )', [$this->year]);
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
                'Harga Satuan',
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
                $transaction->item->category ?? '-',
                $transaction->ship_unloader_label,
                $transaction->lokasi ?: ($transaction->item->lokasi ?? '-'),
                $transaction->quantity,
                $transaction->price === null ? '-' : 'Rp ' . number_format($transaction->price, 0, ',', '.'),
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
