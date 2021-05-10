<?php

namespace App\Models;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements  JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    public function setPasswordAttribute($value){

        $this->attributes['password'] = Hash::make($value);
    }

    public function showUserDetails(){
        return [
            'fullName' => $this->fullName,
            'email' => $this->email,
            'personalNumber' => $this->personalNumber,
            'isPhysical' => $this->isPhysical,
            'legalName' => $this->legalName,
            'legalContactFullName' => $this->legalContactFullName,
            'clientLegalTypeId' => $this->clientLegalTypeId,
            'typeId' => $this->typeId,
            'phone' => $this->phone,
            'legalPersonalNumber' => $this->legalPersonalNumber,
            'legalPersonalNumber' => $this->legalPersonalNumber,
        ];
    }


    public function userType(){
        return $this->hasOne('App\Models\Usertypes', 'foreign_key', 'typeId');
    }

    public function additionals(){
        return $this->hasOne('App\Models\UserAdditional');
    }

    public function bankAccount(){
        return $this->hasMany('App\Models\BankAccount');
    }

    public function getJWTIdentifier(){
        return $this->getKey();
    }

    public function getJWTCustomClaims(){
        return [];
    }

    public function cars()
    {
        return $this->hasMany('App\Models\UserCar');
    }

    public function orders()
    {
        return $this->hasMany('App\Models\Order');
    }

    public function offers()
    {
        return $this->hasMany('App\Models\Offer');
    }
}
