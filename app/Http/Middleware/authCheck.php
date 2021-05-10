<?php

namespace App\Http\Middleware;

use JWTAuth;
use Closure;
use Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use \Tymon\JWTAuth\Exceptions\TokenInvalidException;

class authCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            try
            {
              $refreshed = JWTAuth::refresh(JWTAuth::getToken());
              $user = JWTAuth::setToken($refreshed)->toUser();
              $request->headers->set('Authorization','Bearer '.$refreshed);
              $request->merge(['refreshToken' => $refreshed]);
            }catch (JWTException $e){
                return response()->json([
                    'data' => null,
                    'errors' => ['msg' => 'Token cannot be refreshed, please Login again'],
                    'statusCode' => 103
                ], 103);
            }
        }catch(TokenInvalidException $e){
            $status     = 401;
            $message    = 'This token is invalid. Please Login';

            return response()->json([
                'data' => null,
                'errors' => ['msg' => $message],
                'statusCode' => 401
            ], 401);
        }

        Log::info($request);


        return $next($request);

        // try{
        //     $user = JWTAuth::toUser();
        //     return $next($request);
        // }catch(\Tymon\JWTAuth\Exceptions\JWTException $e){
        //     return response()->json([
        //         'data' => null,
        //         'errors' => ['msg' => 'Not Authorized'],
        //         'statusCode' => 401
        //     ], 401);
        // }
    }


}
