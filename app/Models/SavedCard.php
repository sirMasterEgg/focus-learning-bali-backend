<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavedCard extends Model
{
    use SoftDeletes, HasUuids;

    protected $fillable = [
        'user_id',
        'card_token',
        'masked_card',
        'card_expiration',
    ];


    protected function casts(): array
    {
        return [
            'card_expiration' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
