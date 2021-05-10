<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandModelPart extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $hidden = ['modelId', 'catalogId', 'created_at', 'updated_at'];
    
    public function carParts()
    {
        return $this->hasMany(CarPart::class, 'brand_model_part_id', 'id');
    }
}
