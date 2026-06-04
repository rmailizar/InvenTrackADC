<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Carbon\Carbon;

class TransactionImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use SkipsFailures, Importable;

    private int $rowCount = 0;
    private array $unmatchedItems = [];

    public function __construct(private ?User $user = null)
    {
    }

    public function model(array $row)
    {
        $itemName = $this->cellString($row['nama_barang'] ?? '');
        $noNormalisasi = $this->cellString($row['no_normalisasi'] ?? '');
        $item = $this->resolveItem($itemName, $noNormalisasi);

        if (!$item) {
            $this->unmatchedItems[] = $noNormalisasi ?: $itemName;
            return null;
        }

        // Parse date (support d/m/Y or Y-m-d)
        $date = $this->parseDate($row['tanggal']);
        $isTeknik = $item->bidang === 'teknik';
        $quantity = (int) ($row['jumlah'] ?? $row['volume']);
        $price = $this->cellString($row['harga'] ?? '') === ''
            ? null
            : (int) $row['harga'];
        $shipUnloader = $isTeknik
            ? ($this->normalizeShipUnloader($row['ship_unloader'] ?? '') ?: $item->ship_unloader)
            : null;

        $this->rowCount++;

        return new Transaction([
            'item_id'     => $item->id,
            'user_id'     => $this->user?->id ?? auth()->id(),
            'bidang'      => $item->bidang,
            'no_normalisasi' => $isTeknik ? ($noNormalisasi ?: $item->no_normalisasi) : $item->no_normalisasi,
            'lokasi'      => $isTeknik ? ($this->cellString($row['lokasi'] ?? '') ?: $item->lokasi) : $item->lokasi,
            'volume'      => $isTeknik ? $item->volume : null,
            'ship_unloader' => $shipUnloader,
            'date'        => $date,
            'type'        => strtolower($this->cellString($row['jenis'] ?? '')),
            'quantity'    => $quantity,
            'price'       => $isTeknik ? null : $price,
            'description' => $this->cellString($row['keterangan'] ?? ''),
            'status'      => $item->bidang === 'teknik' ? 'approved' : 'pending',
            'approved_by' => $item->bidang === 'teknik' ? ($this->user?->id ?? auth()->id()) : null,
            'approved_at' => $item->bidang === 'teknik' ? now() : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_barang' => 'required_without:no_normalisasi|nullable|max:255',
            'no_normalisasi' => 'required_without:nama_barang|nullable|max:255',
            'tanggal'     => 'required',
            'jenis'       => 'required|in:in,out,IN,OUT,In,Out',
            'jumlah'      => 'required_without:volume|nullable|integer|min:1',
            'harga'       => 'nullable|integer|min:0',
            'keterangan'  => 'nullable|max:500',
            'ship_unloader' => 'nullable|max:20',
            'lokasi'      => 'nullable|max:255',
            'volume'      => 'required_without:jumlah|nullable|integer|min:1',
            'satuan'      => 'nullable|max:50',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_barang.required' => 'Kolom nama_barang wajib diisi.',
            'nama_barang.required_without' => 'Kolom nama_barang atau no_normalisasi wajib diisi.',
            'no_normalisasi.required_without' => 'Kolom no_normalisasi atau nama_barang wajib diisi.',
            'tanggal.required'     => 'Kolom tanggal wajib diisi.',
            'jenis.required'       => 'Kolom jenis wajib diisi (in/out).',
            'jenis.in'             => 'Kolom jenis harus in atau out.',
            'jumlah.required_without' => 'Kolom jumlah atau volume wajib diisi.',
            'jumlah.min'           => 'Jumlah minimal 1.',
            'volume.required_without' => 'Kolom volume atau jumlah wajib diisi.',
            'volume.min'           => 'Volume minimal 1.',
        ];
    }

    private function parseDate($value): string
    {
        if (is_numeric($value)) {
            // Excel serial date number
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value))->format('Y-m-d');
        }

        // Try various formats
        foreach (['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getUnmatchedItems(): array
    {
        return array_unique($this->unmatchedItems);
    }

    private function resolveItem(string $itemName, string $noNormalisasi): ?Item
    {
        $query = Item::visibleFor($this->user);

        if ($noNormalisasi !== '') {
            $item = (clone $query)->where('no_normalisasi', $noNormalisasi)->first();
            if ($item) {
                return $item;
            }
        }

        if ($itemName === '') {
            return null;
        }

        return $query->where('name', $itemName)->first();
    }

    private function normalizeShipUnloader($value): ?string
    {
        $value = str_replace(['.', ';', '-', ' '], ',', $this->cellString($value));

        $ships = collect(explode(',', $value))
            ->map(fn($ship) => trim($ship))
            ->filter(fn($ship) => in_array($ship, ['1', '2', '3', '4'], true))
            ->unique()
            ->sort()
            ->values();

        return $ships->isEmpty() ? null : $ships->implode(',');
    }

    private function cellString($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_float($value) && floor($value) === $value) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }
}
