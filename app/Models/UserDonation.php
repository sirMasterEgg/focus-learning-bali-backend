<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;


/**
 * @extends \Illuminate\Database\Eloquent\Relations\Pivot
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class UserDonation extends Pivot
{
    use HasUuids;

    protected $table = 'users_donations';

    protected $fillable = [
        'user_id',
        'donation_id',
        'human_readable_id',
        'donation_name',
        'donation_email',
        'first_name',
        'last_name',
        'phone_number',
        'amount',
        'payment_id',
        'payment_method',
        'payment_status',
        'payment_response',
    ];

    protected $casts = [
        'payment_response' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];


    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->human_readable_id)) {
                $model->human_readable_id = self::generateHumanReadableId();
            }
        });
    }

    public static function generateHumanReadableId(): string
    {
        $prefix = 'DONATIONRECEIPT';
        $date = now()->format('Ymd');

        $lastRecord = self::where('human_readable_id', 'like', $prefix . $date . '%')
            ->orderBy('human_readable_id', 'desc')
            ->lockForUpdate()
            ->first();

        if ($lastRecord) {
            $lastSequence = (int)substr($lastRecord->human_readable_id, -5);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        $sequenceStr = str_pad((string)$nextSequence, 5, '0', STR_PAD_LEFT);

        return $prefix . $date . $sequenceStr;
    }

    // Define relationship to the User model
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define relationship to the Donation model
    public function donation(): BelongsTo
    {
        return $this->belongsTo(Donation::class, 'donation_id');
    }
}
