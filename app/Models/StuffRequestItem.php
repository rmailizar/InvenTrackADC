<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StuffRequestItem extends Model
{
    use HasFactory;

    protected $table = 'stuff_request_items';

    protected $fillable = [
        'stuff_request_id',
        'item_id',
        'quantity',
    ];

    public function stuffRequest(): BelongsTo
    {
        return $this->belongsTo(StuffRequest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class)->withTrashed();
    }
}
