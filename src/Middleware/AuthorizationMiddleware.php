<?php

namespace Mauricek\PsrAuthorization\Middleware;

use Mauricek\PsrAuthorization\AssertionPluginManager;
use Mauricek\PsrAuthorization\AssertionContext;
use Mauricek\PsrAuthorization\ResourceId;
use Mauricek\PsrAuthorization\UserIdentity\HasUserIdentity;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Permissions\Rbac\Rbac;
use Zend\Expressive\Router\RouteResult;

class AuthorizationMiddleware implements MiddlewareInterface
{
    private $rbac;
    private $assertionPluginManager;
    private $perRoleAssertions;
    private $perPermissionsAssertions;

    public function __construct(
        Rbac $rbac,
        AssertionPluginManager $assertionPluginManager,
        array $perRoleAssertions,
        array $perPermissionsAssertions
    ) {
        $this->rbac                     = $rbac;
        $this->assertionPluginManager   = $assertionPluginManager;
        $this->perRoleAssertions        = $perRoleAssertions;
        $this->perPermissionsAssertions = $perPermissionsAssertions;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $delegate
    ) : ResponseInterface {
        $identity = $request->getAttribute(HasUserIdentity::class, false);
        $role = $identity && $identity instanceof HasUserIdentity ? $identity->role(): 'anonymous';

        $route     = $request->getAttribute(RouteResult::class);
        $routeName = $route->getMatchedRoute()->getName();

        $assertions = [];
        $assertions = array_merge($this->perRoleAssertions[$role] ?? [], $assertions);
        $assertions = array_merge($this->perPermissionsAssertions[$role][$routeName]['include'] ?? [], $assertions);
        $assertions = array_reverse(array_unique($assertions));

        //Exclusions
        $exclusions = $this->perPermissionsAssertions[$role][$routeName]['exclude'] ?? [];
        $assertions = array_diff($assertions, $exclusions);

        $granted = $this->rbac->isGranted($role, $routeName);

        if(!empty($assertions) && $granted) {
            $resourceId = $request->getAttribute(ResourceId::class);
            if(!$resourceId instanceof ResourceId) {
                throw new \RuntimeException(sprintf('Requires a %s in request attributes', ResourceId::class));
            }

            $assertionContext = AssertionContext::withData(
                $identity->userId(),
                $resourceId->toString()
            );

            foreach($assertions as $assertionClass) {
                $assertion = $this->assertionPluginManager->build($assertionClass, ['context' => $assertionContext]);
                $granted = $granted && $this->rbac->isGranted($role, $routeName, $assertion);

                if(!$granted) {
                    break;
                }
            }
        }

        if(!$granted) {
            return new EmptyResponse(403);
        }

        return $delegate->handle($request);
    }
}
