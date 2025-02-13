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
        'title',
        'description',
        'recipient',
        'quote',
        'target',
        'banner',
        'accept_donation',
        'category_id',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'users_donations');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'donations_categories');
    }
}
