<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;


class SmsNotification extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function sendSMS($phone, $text){
        $opts = array('http'=>array(
                'method'=>"GET",
                'header'=>"MSG_HEADER: 5TJps5Wj"
                )
            );
          
        $context = stream_context_create($opts);
        $randNum = rand(1000, 9999);

        $myvars = [
            'to'         => '995'.$phone,
            'text'       => $text,
            'service_id' => 2183,               
            'client_id'  => 650,                
            'password'   => 'a5G6wSP9hRWjr',  
            'username'   => 'inventor'   
        ];
        
        $file = file_get_contents('http://bi.msg.ge/sendsms.php?' . self::myUrlEncode($myvars), false, $context);
          
        $result = explode('-', $file);

        if(count($result) != 2) {
            $err = json_encode($result);
            return responce()->json(['msg' => "Something went wront, error: {$err}"], 400);
        }

        self::create([
            'phone' => $phone,
            'text' => $text,
            'status_code' => $result[0],
            'receiver_id' => $result[1]
        ]);
    }

    private static function myUrlEncode($arr) {
        $res = '';
        foreach($arr as $k=>$v) {
            $res .= $k .'='. urlencode($v) .'&';
        }
        return substr($res, 0, -1);
    }
}
