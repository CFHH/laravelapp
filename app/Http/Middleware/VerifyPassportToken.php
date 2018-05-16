<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;


class VerifyPassportToken extends Authenticate
{
    protected function authenticate(array $guards)
    {
        if ($this->auth->guard('api')->check())
			return $this->auth->shouldUse('api');
        else
			throw new UnauthorizedHttpException('', 'Unauthenticated');
    }
}
