<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCar extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $hidden = ['user_id', 'created_at', 'updated_at'];

}
