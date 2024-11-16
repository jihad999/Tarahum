<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if($request->header('authorization')){
            if($request->header('authorization') == env('API_TOKEN')){
                return $next($request);
            }
            return response()->json([
                'status' => 404,
                "msg" => "The token is not identical",
                "data" => null,
            ]);
        }
        
        return response()->json([
            'status' => 404,
            "msg" => "The token not found",
            "data" => null,
        ]);
    }
}
