<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\Importable;
use Carbon\Carbon;

class TransactionImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures, Importable;

    private int $rowCount = 0;
    private array $unmatchedItems = [];

    public function model(array $row)
    {
        $itemName = trim($row['nama_barang']);
        $item = Item::where('name', $itemName)->first();

        if (!$item) {
            $this->unmatchedItems[] = $itemName;
            return null;
        }

        // Parse date (support d/m/Y or Y-m-d)
        $date = $this->parseDate($row['tanggal']);

        $this->rowCount++;

        return new Transaction([
            'item_id'     => $item->id,
            'user_id'     => auth()->id(),
            'date'        => $date,
            'type'        => strtolower(trim($row['jenis'])),
            'quantity'    => (int) $row['jumlah'],
            'price'       => (int) ($row['harga'] ?? 0),
            'description' => trim($row['keterangan'] ?? ''),
            'status'      => 'pending',
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_barang' => 'required|string',
            'tanggal'     => 'required',
            'jenis'       => 'required|in:in,out,IN,OUT,In,Out',
            'jumlah'      => 'required|integer|min:1',
            'harga'       => 'nullable|integer|min:0',
            'keterangan'  => 'nullable|string|max:500',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'nama_barang.required' => 'Kolom nama_barang wajib diisi.',
            'tanggal.required'     => 'Kolom tanggal wajib diisi.',
            'jenis.required'       => 'Kolom jenis wajib diisi (in/out).',
            'jenis.in'             => 'Kolom jenis harus in atau out.',
            'jumlah.required'      => 'Kolom jumlah wajib diisi.',
            'jumlah.min'           => 'Jumlah minimal 1.',
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
}
