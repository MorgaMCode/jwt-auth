<?php

namespace App\Http\Middleware;

use Closure;


use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;


class JWTMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try
        {
                $user = JWTAuth::parseToken()->authenticate();
        }
        catch (\Tymon\JWTAuth\Exceptions\TokenBlacklistedException $e)
        {
            return response(['status'=>'token invalido'],401);
        }
        catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e)
        {
            return response(['status'=>'token expirado'],401);
        }
        catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e)
        {
            return response(['status'=>'token invalido '],401);
        }
        catch (\Tymon\JWTAuth\Exceptions\JWTException $e)
        {
            return response(['status'=>'token no encontrado'],401);
        }
        return $next($request);
    }
}
