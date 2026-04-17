<?php

namespace App\Imports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;

class ItemImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures, Importable;

    private int $rowCount = 0;
    private int $skipCount = 0;

    public function model(array $row)
    {
        // Skip if item with same name already exists
        $existing = Item::where('name', trim($row['nama_barang']))->first();
        if ($existing) {
            $this->skipCount++;
            return null;
        }

        $this->rowCount++;

        return new Item([
            'name'      => trim($row['nama_barang']),
            'category'  => trim($row['kategori']),
            'unit'      => trim($row['satuan']),
            'min_stock' => (int) ($row['min_stok'] ?? 0),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_barang' => 'required|string|max:255',
            'kategori'    => 'required|string|max:255',
            'satuan'      => 'required|string|max:50',
            'min_stok'    => 'nullable|integer|min:0',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_barang.required' => 'Kolom nama_barang wajib diisi.',
            'kategori.required'    => 'Kolom kategori wajib diisi.',
            'satuan.required'      => 'Kolom satuan wajib diisi.',
        ];
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function getSkipCount(): int
    {
        return $this->skipCount;
    }
}
