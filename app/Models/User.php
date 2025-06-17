<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, HasUuids;

    protected $fillable = [
        'human_readable_id',
        'name',
        'title',
        'avatar',
        'email',
        'password',
        'email_verified_at',
        'role',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function savedCards(): HasMany
    {
        return $this->hasMany(SavedCard::class);
    }

    public function donations(): BelongsToMany
    {
        return $this->belongsToMany(Donation::class, 'users_donations')->using(UserDonation::class);
    }

    public function userOauth(): HasOne
    {
        return $this->hasOne(UserOauth::class);
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
        $prefix = 'USER';
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
}
