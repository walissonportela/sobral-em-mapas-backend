<?php

namespace App\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WmsLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'version',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function layers(): HasMany
    {
        return $this->hasMany(Layer::class, 'wms_link_id');
    }
}