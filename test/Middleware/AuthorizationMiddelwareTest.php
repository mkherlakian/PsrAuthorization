<?php

namespace MauricekTest\PermissionTest\Middleware;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Permissions\Rbac\Rbac;
use Zend\ServiceManager\AbstractPluginManager;
use Zend\Expressive\Router\RouteResult;
use Zend\Expressive\Router\Route;
use Zend\Permissions\Rbac\AssertionInterface;
use Mauricek\PsrAuthorization\Middleware\AuthorizationMiddleware;
use Mauricek\PsrAuthorization\AssertionPluginManager;
use Mauricek\PsrAuthorization\AssertionContext;
use Mauricek\PsrAuthorization\UserIdentity\HasUserIdentity;
use Mauricek\PsrAuthorization\ResourceId;

class AuthorizationTest extends TestCase
{
    protected $assertionPluginManager;

    protected $rbac;

    protected $perRoleAssertions;

    protected $perPermissionAssertions;

    protected function setUp()
    {
        $this->assertionPluginManager = $this->prophesize(AssertionPluginManager::class);

        $this->rbac = new Rbac();
        $this->rbac->addRole('administrator');
        $this->rbac->addRole('member', ['administrator']);
        $this->rbac->addRole('anonymous', ['member']);

        $this->rbac->getRole('anonymous')->addPermission('api.login');

        $this->rbac->getRole('member')->addPermission('api.friends');
        $this->rbac->getRole('member')->addPermission('api.statistics');
        $this->rbac->getRole('member')->addPermission('api.balance');

        $this->rbac->getRole('administrator')->addPermission('api.users');

        $this->perRoleAssertions = ['member' => ['perRoleAssertion1']];

        $this->perPermissionsAssertions = [
            'member' => [
                'api.statistics' => [
                    'exclude' => ['perRoleAssertion1']
                ],
                'api.friends' => [
                    'include' => ['perPermissionAssertion1']
                ],
            ],
            'administrator' => [
                'api.users' => ['perPermissionAssertion2']
            ]
        ];
    }

    protected function prophesizeServerRequestInterface($userRole, $routeName, $resourceId)
    {
        $routeProphecy = $this->prophesize(Route::class);
        $routeProphecy->getName()->willReturn($routeName);

        $rrProphecy = $this->prophesize(RouteResult::class);
        $rrProphecy->getMatchedRoute()->willReturn($routeProphecy->reveal());

        $userIdentity = $this->prophesize(HasUserIdentity::class);
        $userIdentity->userId()->willReturn('user12345');
        $userIdentity->role()->willReturn($userRole);

        $serverReqProphecy = $this->prophesize(ServerRequestInterface::class);
        $serverReqProphecy->getAttribute(HasUserIdentity::class, false)->willReturn($userIdentity->reveal());
        $serverReqProphecy->getAttribute(RouteResult::class)->willReturn($rrProphecy->reveal());
        $serverReqProphecy->getAttribute(ResourceId::class)->willReturn(ResourceId::from($resourceId));

        return $serverReqProphecy->reveal();
    }

    protected function prophesizeDelegateShouldBeCalled($request)
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request)->shouldBeCalledTimes(1);
        return $handler->reveal();
    }

    protected function prophesizeDelegateShouldNotBeCalled($request)
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler->handle($request)->shouldNotBeCalled();
        return $handler->reveal();
    }

    protected function defaultAuthorizationAction($assertionPluginManager = null)
    {
        $authorizationAction = new AuthorizationMiddleware(
            $this->rbac,
            $assertionPluginManager ?? $this->assertionPluginManager->reveal(),
            $this->perRoleAssertions,
            $this->perPermissionsAssertions
        );

        return $authorizationAction;
    }

    protected function prophesizeAssertionWithReturn($bool, $times)
    {
        $assertion = $this->prophesize(AssertionInterface::class);
        $assertion
            ->assert(Argument::Any(), Argument::Any(), Argument::Any())
            ->shouldBeCalledTimes($times)
            ->willReturn($bool);
        return $assertion->reveal();
    }

    public function testUnauthorizedRouteReturns403NoExtraAssertions()
    {
        $authorizationAction = $this->defaultAuthorizationAction();

        $request    = $this->prophesizeServerRequestInterface('anonymous', 'api.friends', '123456');
        $delegate   = $this->prophesizeDelegateShouldNotBeCalled($request);

        $response = $authorizationAction->process($request, $delegate);

        $this->assertInstanceOf(EmptyResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAuthorizedRouteNoExtraAssertion()
    {
        $authorizationAction = $this->defaultAuthorizationAction();

        $request    = $this->prophesizeServerRequestInterface('anonymous', 'api.login', '123456');
        $delegate   = $this->prophesizeDelegateShouldBeCalled($request);

        $authorizationAction->process($request, $delegate);
    }

    public function testAuthorizedWithExtraRoleAssertionNoExclusion()
    {
        $assertionPluginManager = $this->prophesize(AssertionPluginManager::class);
        $assertionContext = AssertionContext::withData('user12345', '123456');

        $assertionPluginManager
            ->build('perRoleAssertion1', ['context' => $assertionContext])
            ->shouldBeCalledTimes(1)
            ->willReturn($this->prophesizeAssertionWithReturn(true, 1));

        $authorizationAction = $this->defaultAuthorizationAction($assertionPluginManager->reveal());

        $request    = $this->prophesizeServerRequestInterface('member', 'api.balance', '123456');
        $delegate   = $this->prophesizeDelegateShouldBeCalled($request);

        $authorizationAction->process($request, $delegate);
    }

    public function testAuthorizedWithExtraRoleAndPermAssertionNoExclusion()
    {
        $assertionPluginManager = $this->prophesize(AssertionPluginManager::class);
        $assertionContext = AssertionContext::withData('user12345', '123456');

        $assertionPluginManager
            ->build('perRoleAssertion1', ['context' => $assertionContext])
            ->shouldBeCalledTimes(1)
            ->willReturn($this->prophesizeAssertionWithReturn(true, 1));

        $assertionPluginManager
            ->build('perPermissionAssertion1', ['context' => $assertionContext])
            ->shouldBeCalledTimes(1)
            ->willReturn($this->prophesizeAssertionWithReturn(true, 1));

        $authorizationAction = $this->defaultAuthorizationAction($assertionPluginManager->reveal());

        $request    = $this->prophesizeServerRequestInterface('member', 'api.friends', '123456');
        $delegate   = $this->prophesizeDelegateShouldBeCalled($request);

        $authorizationAction->process($request, $delegate);
    }

    public function testNotAuthorizedWithExtraRoleAndPermAssertion()
    {
        $assertionPluginManager = $this->prophesize(AssertionPluginManager::class);
        $assertionContext = AssertionContext::withData('user12345', '123456');

        $assertionPluginManager
            ->build('perRoleAssertion1', ['context' => $assertionContext])
            ->shouldNotBeCalled();

        $assertionPluginManager
            ->build('perPermissionAssertion1', ['context' => $assertionContext])
            ->shouldNotBeCalled();

        $authorizationAction = $this->defaultAuthorizationAction($assertionPluginManager->reveal());

        $request    = $this->prophesizeServerRequestInterface('member', 'api.users', '123456');
        $delegate   = $this->prophesizeDelegateShouldNotBeCalled($request);

        $response = $authorizationAction->process($request, $delegate);

        $this->assertInstanceOf(EmptyResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAuthorizedWithExtraRoleAndPermAssertionAndExclusion()
    {
        $assertionPluginManager = $this->prophesize(AssertionPluginManager::class);
        $assertionContext = AssertionContext::withData('user12345', '123456');

        $assertionPluginManager
            ->build('perRoleAssertion1', ['context' => $assertionContext])
            ->shouldBeCalledTimes(0)
            ->willReturn($this->prophesizeAssertionWithReturn(true, 0));

        $authorizationAction = $this->defaultAuthorizationAction($assertionPluginManager->reveal());

        $request    = $this->prophesizeServerRequestInterface('member', 'api.statistics', '123456');
        $delegate   = $this->prophesizeDelegateShouldBeCalled($request);

        $authorizationAction->process($request, $delegate);
    }
}
