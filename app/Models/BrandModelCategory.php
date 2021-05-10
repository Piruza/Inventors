<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandModelCategory extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $hidden = ['carId', 'modelId', 'catalogId', 'created_at', 'updated_at'];
}
