<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneVerification extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function verifyCode($code, $user){
        return self::create([
            'code' => $code,
            'user_id' => $user->id
        ]);
    }
}
