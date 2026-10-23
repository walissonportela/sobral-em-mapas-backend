<?php

namespace App\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Layer extends Model
{
    use HasFactory;

    protected $table = 'layers';

    protected $fillable = [
        'name',
        'layer_name',
        'crs',
        'legend_url',
        'type',
        'description',
        'order',
        'subcategory',
        'image_path',
        'max_scale',
        'symbol',
        'wms_link_id',
        'isPublic',
        'is_active',
        'source_format',
        'geojson_path',
    ];

    protected $casts = [
        'order' => 'integer',
        'subcategory' => 'integer',
        'wms_link_id' => 'integer',
        'max_scale' => 'float',
        'isPublic' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'subcategory');
    }

    public function wmsLink(): BelongsTo
    {
        return $this->belongsTo(WmsLink::class, 'wms_link_id');
    }
}