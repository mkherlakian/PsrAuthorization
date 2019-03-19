<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization\ResourceOwnerAssertion;

use Zend\Permissions\Rbac\AssertionInterface;
use Zend\Permissions\Rbac\Rbac;
use Zend\Permissions\Rbac\RoleInterface;
use Mauricek\PsrAuthorization\AssertionContext;

class IsResourceOwnerAssertion extends AssertionInterface
{
    protected $roPluginManager;
    protected $authenticatedUserId;
    protected $resourceId;

    public function __construct(ResourceOwnerPluginManager $pluginManager, AssertionContext $context)
    {
        $this->roPluginManager      = $pluginManager;
        $this->authenticatedUserId  = $context->authenticatedUserId();
        $this->resourceId           = $context->requestedResourceId();
    }

    public function assert(Rbac $rbac, RoleInterface $role, string $permission)
    {
        $isOwnPlugin = $this->roPluginManager->get($permission);
        return $isOwnPlugin->isOwn($this->resourceId, $this->authenticatedUserId);
    }
}
