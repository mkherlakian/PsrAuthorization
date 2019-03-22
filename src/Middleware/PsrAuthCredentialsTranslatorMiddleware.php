<?php

namespace Mauricek\PsrAuthorization\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Mauricek\PsrAuthentication\Credentials;
use Mauricek\PsrAuthorization\UserIdentity\UserIdentity;
use Mauricek\PsrAuthorization\UserIdentity\HasUserIdentity;

class PsrAuthCredentialsTranslatorMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $delegate) : ResponseInterface
    {
        if($creds = $request->getAttribute(Credentials::class, false)) {
            $ui = UserIdentity::with(
                $creds->memberId(),
                $creds->role()
            );

            $request = $request->withAttribute(HasUserIdentity::class, $ui);
        }

        return $delegate->handle($request);
    }
}
