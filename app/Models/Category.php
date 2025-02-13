<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasUuids, SoftDeletes, HasFactory;

    protected $fillable = [
        'name',
        'icon',
    ];

    public function donations(): BelongsToMany
    {
        return $this->belongsToMany(Donation::class, 'donations_categories');
    }
}
