<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Request;

class Transaction
{
   
    public function handle($request, Closure $next, $guard = null)
    {
        $unique=uniqid();
        $request->merge(['unique' => $unique]);
        return $next($request);
    }
}
