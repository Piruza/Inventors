<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;
    
    protected $primaryKey = null;
    public $incrementing = false;
    protected $table = 'catalog_brands';
    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];

}
