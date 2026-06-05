<?php

namespace App\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Models\Subcategory;
use App\Infrastructure\Models\Category; // IMPORTANTE: Importe o modelo de categoria
use App\Infrastructure\Models\WmsLink;

class Layer extends Model
{
    use HasFactory;

    protected $table = 'layers';

    protected $fillable = [
        'name', 'layer_name', 'crs', 'legend_url', 'type', 'description', 
        'order', 'subcategory', 'image_path', 'max_scale', 'symbol', 
        'wms_link_id', 'isPublic'
    ];

    protected $dates = ['created_at', 'updated_at'];

    // Relacionamento direto com Subcategory
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory');
    }

    // ATALHO: Isso permite que você acesse layer->subcategory->category
    // O Laravel já entende esse caminho naturalmente se a subcategory tiver a relação com category
    
    // Relacionamento com WmsLink
    public function wmsLink()
    {
        return $this->belongsTo(WmsLink::class, 'wms_link_id');
    }
}