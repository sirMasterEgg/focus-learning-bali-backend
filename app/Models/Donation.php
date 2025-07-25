<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Donation extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'human_readable_id',
        'title',
        'recipient',
        'description',
        'thumbnail',
        'program_image',
        'current_donation',
        'target',
        'is_active',
    ];

    protected $casts = [
        'current_donation' => 'double',
        'target' => 'double',
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_donations')->using(UserDonation::class);
    }

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
        $prefix = 'DONATION';
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

    public function getRouteKeyName(): string
    {
        return 'human_readable_id';
    }
}
