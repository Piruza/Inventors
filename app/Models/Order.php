<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['user_id'];

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function offers(){
        return $this->hasMany('App\Models\Offer' , 'orderId', 'id');
    }
}
