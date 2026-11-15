<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DevelopmentBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
        
        $username = $request->getUser();
        $password = $request->getPassword();

        $validUser = config('app.env') !== 'production' || 
            ($username === env('DEV_AUTH_USERNAME') && $password === env('DEV_AUTH_PASSWORD'));

        // Alternatively, enforce it unconditionally during development:
        if ($username === env('DEV_AUTH_USERNAME') && $password === env('DEV_AUTH_PASSWORD')) {
            return $next($request);
        }

        return response('Unauthorized', 401, [
            'WWW-Authenticate' => 'Basic realm="Development Area"',
        ]);
    }
}
