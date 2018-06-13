<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyPassportTokenEx extends VerifyPassportToken
{
    public function handle($request, Closure $next, ...$guards)
    {
        $bearer_access_token = $request->input('token');
        if (is_null($bearer_access_token))
        {
            return $this->buildErrorResponse();
        }
        $request->headers->set('Authorization', 'Bearer ' . $bearer_access_token);
        return parent::handle($request, $next, ...$guards);
    }
}
