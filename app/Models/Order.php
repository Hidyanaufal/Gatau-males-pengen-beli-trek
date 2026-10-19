<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'status', 'payment_status', 'fulfillment_type',
        'total', 'address', 'scheduled_at', 'notes', 'ordered_at',
    ];

    protected static function booted(): void
    {
        // Setiap pesanan baru otomatis dapat token acak untuk QR invoice.
        // public_token sengaja tidak masuk $fillable supaya tidak bisa diisi dari input pengguna.
        static::creating(function (Order $order) {
            if (empty($order->public_token)) {
                $order->public_token = Str::random(40);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'total' => 'decimal:2',
            'scheduled_at' => 'datetime',
            'ordered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}