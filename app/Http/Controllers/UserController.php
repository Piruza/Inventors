<?php

namespace App\Http\Controllers;

use \App\Models\User;
use \App\Models\BankAccount;
use \App\Models\PhoneVerification;
use \App\Models\SmsNotification;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct(){
        // $this->middleware('guest');
    }

    public function register(Request $request){

        $validator = Validator::make($request->all(),[
            'firstname' => 'required|max:255',
            'lastname' => 'required|max:255',
            'email' => 'required|email|unique:users|max:255',
            'phone' => 'required|unique:users|max:255',
            'isPhysical' => 'required|integer|max:255',
            'type_id' => 'required|integer|max:255',
            'password' => 'required|min:4|max:255'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422); 
        }

        //Send sms text
        $code = rand(1000, 9999);
        $text = "Code: ".$code;
        
        //Create User
        $user = User::create([
            'firstname' => $request['firstname'],
            'lastname' => $request['lastname'],
            'email' => $request['email'],
            'phone' => $request['phone'],
            'isPhysical' => $request['isPhysical'],
            'type_id' => $request['type_id'],
            'password' => Hash::make($request['password']),
        ]);

        //Send SMS
        SmsNotification::sendSMS($request['phone'], $text, $user);

        //Store Verify Code
        PhoneVerification::verifyCode($code, $user);

        return $user;
    }

    public function login(Request $request){

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|min:4|max:255'
        ]);

        if(Auth::attempt(['email' =>  $data['email'], 'password' =>  $data['password']])){
            $user = Auth::user();

            return response()->json($user);
        }

        return response()->json(['msg' => 'Invalid credentials'], 401);
    }

    public function changePassword(Request $request){

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|min:4|max:255',
            'newPassword' => 'required|min:4|max:255'
        ]);

        if(Auth::attempt(['email' =>  $data['email'], 'password' =>  $data['password']])){
            $user = Auth::user();

            $user->password = Hash::make($data['newPassword']);

            $user->save();

            return response()->json(['msg' => 'Password changed!']);
        }
    }


    public function addBankAccount(Request $request){

        $data = $request->validate([
            'acc_number' => 'required|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|min:4|max:255'
        ]);

        if(Auth::attempt(['email' =>  $data['email'], 'password' =>  $data['password']])){
            $user = Auth::user();

            $bankAcc = $user->bankAccount()->create([
                'acc_number' => $data['acc_number']
            ]);

            return response()->json($bankAcc);
        }
    }

    public function removeBankAccount($id){
        $bankAccount = BankAccount::find($id);
        if($bankAccount){

            $bankAccount->delete();
            return response()->json(['msg' => 'Bank Account Removed!']);
        }
        return response()->json(['msg' => 'Bank Account not found!']);
    }

    public function phoneVerify(Request $request){
        $validator = Validator::make($request->all(), [
            'code' => 'required|digits:4',
            'email' => 'required|email|max:255',
            'password' => 'required|min:4|max:255'
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422); 
        }

        if(Auth::attempt(['email' =>  $request['email'], 'password' =>  $request['password']])){
            $user = Auth::user();

            $verifyCode = PhoneVerification::where([
                                    ['code', '=', $request['code']],
                                    ['user_id', '=', $user->id],
                                    ['has_used', '=', 0]
                                ])->first();

            if(is_null($verifyCode)) return response()->json(['msg' => 'Invalid code'], 400);

            //Verify user
            $user->isApproved = true;
            $user->update();

            $verifyCode->has_used = true;
            $verifyCode->update();

            return response()->json(['msg' => 'Your phone is verified!'], 200);
            
        }
    }
}
