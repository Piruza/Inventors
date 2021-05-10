<?php

namespace App\Http\Controllers;

use \App\Models\User;
use \App\Models\BankAccount;
use \App\Models\PhoneVerification;
use \App\Models\SmsNotification;
Use \App\Models\ResetPassword;
use \App\Models\LegalType;
use JWTAuth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct(){
        $this->middleware('authCheck', ['except' => 
            [
                'login', 'register', 'loginLegalUser', 'userExists', 'phoneVerify', 'resetPassword', 'confirmResetCode', 'setNewPassword',
                'legalTypes', 'addLegalType'
            ]
        ]);
    }

    public function register(Request $request){

        $validator = Validator::make($request->all(),[
            'fullName' => 'max:255',
            'personalNumber' => 'unique:users|digits:11',
            'email' => 'unique:users|email',
            'phone' => 'required|unique:users|max:255',
            'isPhysical' => 'required|integer|max:255',
            'typeId' => 'required|integer|max:255',
            'password' => 'required|min:4|max:255',
            'legalType' => 'integer',
            'legalName' => 'string|max:255',
            'legalContactFullName' => 'string|max:255',
            'legalPersonalNumber' => 'unique:users|max:255',
            'social_auth' => 'max:255',
            'clientLegalTypeId' => 'integer'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $request->password = Hash::make($request->password);

        //Send sms text
        $code = rand(1000, 9999);
        $text = "Code: ".$code;
        
        //Create User
        $user = User::create($request->except([
            'fullAddress',
            'city',
            'street',
            'flat',
            'accountNumber'
        ]));

        $user->additionals()->create([
            'fullAddress' => $request['fullAddress'],
            'city' => $request['city'],
            'street' => $request['street'],
            'flat' => $request['flat']
        ]);

        //Check if is Seller
        if($user->typeId == 2){
            if(!is_null($request->accountNumber)){
                $user->bankAccount()->create([
                    'accountNumber' => $request->accountNumber
                ]);
            }
        }

        return $this->toJson($user->showUserDetails(), 200);
    }

    public function login(Request $request){

        $validator = Validator::make($request->all(),[
            'personalNumber' => 'nullable|digits:11',
            'email' => 'nullable|email',
            'password' => 'required|min:4|max:255'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        
        if($request->has('email')){
            $credentials = $request->only(['email', 'password']);
        }else{
            $credentials = $request->only(['personalNumber', 'password']);
        }
        

        if (!$token = JWTAuth::attempt($credentials)) {
            return $this->toJson(null, 401, ['msg' => 'Invalid Credentials']);
        }
        
        return $this->toJson([
            'token' => $token,
            'user' => auth()->user()->showUserDetails()
        ], 200);
    }

    public function userExists(Request $request){
        $validator = Validator::make($request->all(),[
            'personalNumber' => 'nullable|min:11',
            'email' => 'nullable|email',
            'phone' => 'nullable|digits:9'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $req = array_filter($request->all());

        if(empty($req)) return $this->toJson(null, 401, ['msg' => 'Fields are empty']);

        $keys = array_keys($req);

        $msg = [];

        foreach($keys as $key => $param){
            $exists = User::where($param, $req[$param])->first();
            if($exists){
                array_push($msg, [$param => 'Already Used']);
            }
        }

        if(!empty($msg)) return $this->toJson(null, 422, $msg);

        return $this->toJson(['msg' => 'OK'], 200);

    }

    public function loginLegalUser(Request $request){

        $validator = Validator::make($request->all(),[
            'legalPersonalNumber' => 'required',
            'password' => 'required|min:4|max:255'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $credentials = $request->only(['legalPersonalNumber', 'password']);

        if (!$token = JWTAuth::attempt($credentials)) {
            return $this->toJson(null, 401, ['msg' => 'Invalid Credentials']);
        }
        
        return $this->toJson([
            'token' => $token,
            'user' => auth()->user()->showUserDetails()
        ], 200);
    }

    public function updateAddress(Request $request){
        $validator = Validator::make($request->all(), [
            'fullAddress' => 'max:255',
            'city'  => 'max:255',
            'street'  => 'max:255',
            'flat'  => 'max:255'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        auth()->user()->additionals()->update(array_filter($request->all()));

        return $this->toJson([
            'msg' => 'Address updated'
        ], 200);
    }

    public function me(){
        return $this->toJson([
            'user' => auth()->user()->showUserDetails(),
            'token' => JWTAuth::refresh()
        ], 200);
    }

    public function changePassword(Request $request){

        $validator = Validator::make($request->all(),[
            'newPassword' => 'required|min:4|max:255'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        auth()->user()->update([
            'password' => $request['newPassword']
        ]);

        return $this->toJson([
            'msg' => 'Password changed successfully'
        ], 200);
    }

    public function resetPassword(Request $request){

        $validator = Validator::make($request->all(),[
            'phone' => 'required|digits:9'
        ]);
        
        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }
        
        $user = User::where('phone', $request->phone)->first();

        if(!$user)
            return $this->toJson(null, 422, ['msg' => 'User with this phone number not found']);

        $code = rand(100000, 999999);

        while(ResetPassword::where('code', $code)->first() != null){
            $code = rand(100000, 999999);        
        } 

        ResetPassword::create([
            'phone' => $request->phone,
            'code' => $code
        ]);

        SmsNotification::sendSMS($request->phone, "Code: {$code}");

        return $this->toJson([
            'msg' => "Password reseted successfully, Code: {$code}"
        ], 200);
    }

    public function confirmResetCode(Request $request){

        $validator = Validator::make($request->all(),[
            'code' => 'required|digits:6'
        ]);
        
        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }
        
        $data = ResetPassword::where([
            ['code', '=', $request->code],
        ])->first();

        if(!$data)
            return $this->toJson(null, 422, ['msg' => 'Invalid reset code']);
        
        if($data->isConfirmed)
            return $this->toJson(null, 422, ['msg' => 'reset code already used']);
        
        $data->update([
            'isConfirmed' => true
        ]);

        return $this->toJson([
            'msg' => "Reset code is correct"
        ], 200);
    }

    public function setNewPassword(Request $request){
        $validator = Validator::make($request->all(),[
            'password' => 'required|min:4|max:255',
            'phone' => 'required|digits:9'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        $user = User::where('phone', $request->phone)->first();

        if(!$user)
            return $this->toJson(null, 422, ['msg' => 'User with this phone number not found']);

        $user->update([
            'password' => $request->password
        ]);

        return $this->toJson([
            'msg' => 'Password changed successfully'
        ], 200);
    }
    

    public function addBankAccount(Request $request){

        $validator = Validator::make($request->all(),[
            'accountNumber' => 'max:255',
            'nameOnCard' => 'max:255',
            'cardNumber' => 'max:255',
            'ccv' => 'digits:3',
            'exp_date' => 'digits:5'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        auth()->user()->bankAccount()->create(array_filter($request->all()));

        return $this->toJson([
            'msg' => 'Bank acount details added'
        ], 200);
        
    }

    public function removeBankAccount($id){
        $bankAccount = BankAccount::find($id);

        if($bankAccount){

            if(!$bankAccount->belongsToUser()){
                return $this->toJson(null, [
                    'msg' => 'Bank account does not belongs to user'
                ], 200);
            }
    

            $bankAccount->delete();

            return $this->toJson([
                'msg' => 'Bank account details removed'
            ], 200);
        }

        return $this->toJson(null, [
            'msg' => 'Bank account not found'
        ], 200);
    }

    public function phoneVerify(Request $request){
        $validator = Validator::make($request->all(),[
            'code' => 'required|digits:4',
            'phone' => 'required|digits:9'
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }


        $verifyCode = PhoneVerification::where([
                                ['code', '=', $request->code],
                                ['phone', '=', $request->phone],
                                ['verified', '=', 0]
                            ])->first();

        if(is_null($verifyCode)) return $this->toJson(null, 400, ['msg' => 'Invalid code']);


        $verifyCode->verified = true;
        $verifyCode->update();

        return $this->toJson(['msg' => 'Phone number is verified!'], 200);
            
    }

    public function addLegalType(Request $request){
        $validator = Validator::make($request->all(),[
            'title' => 'required',
        ]);

        if($validator->fails()){
            return $this->toJson(null, 422, $validator->errors());
        }

        LegalType::create([
            'title' =>  $request->title
        ]);

        return $this->toJson([
            'msg' => 'Legal type created'
        ], 200);
    }

    public function legalTypes(){
        $types = LegalType::all();

        return $this->toJson($types, 200);
    }

    public function refreshToken(){
        $user = auth()->user();

        return $this->toJson([
            'token' => JWTAuth::refresh()
        ], 200);
    }
}
