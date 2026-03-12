<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockHistory extends Model
{
    const TYPE = ['plus', 'minus'];

    const SOURCE = ['order', 'manual', '1c'];

    protected $fillable = [
        'stock_id',
        'type',
        'quantity',
        'previous_quantity',
        'difference',
        'updated_by',
        'user_id',
        'order_id',
        'source',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(BotUser::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
