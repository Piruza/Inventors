<?php

namespace App\Http\Controllers;


use \App\Models\SmsNotification;
use \App\Models\PhoneVerification;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmsNotificationController extends Controller
{
    public function sendSMS(Request $request){ 
        $validator = Validator::make($request->all(),[
            'phone' => 'required|digits:9',
            'text' => 'required|max:170'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $opts = array('http'=>array(
                'method'=>"GET",
                'header'=>"MSG_HEADER: 5TJps5Wj"
                )
            );
          
        $context = stream_context_create($opts);
        $randNum = rand(1000, 9999);

        $myvars = [
            'to'         => '995'.$request->phone,
            'text'       => $request->text,
            'service_id' => 2183,               
            'client_id'  => 650,                
            'password'   => 'a5G6wSP9hRWjr',  
            'username'   => 'inventor'   
        ];
        
        $file = self::curl_get_file_contents('http://bi.msg.ge/sendsms.php?' . self::myUrlEncode($myvars), false, $context);
          
        $result = explode('-', $file);

        if(count($result) != 2) {
            $err = json_encode($result);
            return $this->toJson(null, 400, ['msg' => "Something went wront, error: {$err}"]);
        }

        SmsNotification::create([
            'phone' => $request->phone,
            'text' => $request->text,
            'status_code' => $result[0],
            'receiver_id' => $result[1]
        ]);

        return $this->toJson(['msg' => "SMS sent"], 200);
    }

    public function sendVerificationCode(Request $request){
        $validator = Validator::make($request->all(),[
            'phone' => 'required|digits:9'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $opts = array('http'=>array(
                'method'=>"GET",
                'header'=>"MSG_HEADER: 5TJps5Wj"
                )
            );
          
        $context = stream_context_create($opts);
        $randNum = rand(1000, 9999);
        $text = "Code: {$randNum}";

        $myvars = [
            'to'         => '995'.$request->phone,
            'text'       => $text,
            'service_id' => 2183,               
            'client_id'  => 650,                
            'password'   => 'a5G6wSP9hRWjr',  
            'username'   => 'inventor'   
        ];
        
        $file = self::curl_get_file_contents('http://bi.msg.ge/sendsms.php?' . self::myUrlEncode($myvars), false, $context);
          
        $result = explode('-', $file);

        if(count($result) != 2) {
            $err = json_encode($result);
            return $this->toJson(null, 400, ['msg' => "Something went wront, error: {$err}"]);
        }

        SmsNotification::create([
            'phone' => $request->phone,
            'text' => $text,
            'status_code' => $result[0],
            'receiver_id' => $result[1]
        ]);

        PhoneVerification::create([
            'phone' => $request->phone,
            'code' => $randNum
        ]);

        return $this->toJson(['msg' => "Code sent", 'code' => $randNum], 200);
        
    }

    private static function myUrlEncode($arr) {
        $res = '';
        foreach($arr as $k=>$v) {
            $res .= $k .'='. urlencode($v) .'&';
        }
        return substr($res, 0, -1);
    }
    
    private static function curl_get_file_contents($URL)
    {
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $URL);
        $contents = curl_exec($c);
        curl_close($c);
    
        if ($contents) return $contents;
        else return FALSE;
    }
}
