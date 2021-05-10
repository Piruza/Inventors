<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private $refreshToken = null;

    public function __construct(Request $request){
        auth()->setDefaultDriver('api');

        if($request->has('refreshToken'))
            $this->refreshToken = $request->refreshToken;
    } 

    public function toJson($data, $code , $err = null){
        return json_encode([
            'data' => $data,
            'errors' => $err,
            'statusCode' => $code,
            // 'refreshToken' => $this->refreshToken
        ]);
    }
}
