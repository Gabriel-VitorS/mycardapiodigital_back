<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Session;

class verifyTokenJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {   

        try {
            
            if($request->bearerToken() == '' || $request->bearerToken() == null){
                return response()->json(['Unauthorized access'=> 'E_UNAUTHORIZED_ACCESS'], 401);
            }
            
            $key = env('JWT_KEY');
            $decoded = JWT::decode($request->bearerToken(), new Key($key, 'HS256'));

            Session::put('id', $decoded->id);

            return $next($request);

        } catch (\Throwable $th) {
            return response()->json(['Unauthorized access'=> 'E_UNAUTHORIZED_ACCESS'], 401);
        }
    }
}
