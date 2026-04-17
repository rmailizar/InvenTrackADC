<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'unit',
        'min_stock',
    ];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getCurrentStockAttribute(): int
    {
        $masuk = $this->transactions()
            ->where('type', 'masuk')
            ->where('status', 'approved')
            ->sum('quantity');

        $keluar = $this->transactions()
            ->where('type', 'keluar')
            ->where('status', 'approved')
            ->sum('quantity');

        return $masuk - $keluar;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    public function scopeLowStock($query)
    {
        return $query->get()->filter(function ($item) {
            return $item->is_low_stock;
        });
    }
}
