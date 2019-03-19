<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization\Middleware;

use Psr\Container\ContainerInterface;
use Mauricek\PsrAuthorization\AssertionPluginManager;
use Zend\Permissions\Rbac\Rbac;
use Zend\Permissions\Rbac\Role;

class AuthorizationFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config')['permissions'];

        if(!isset($config['roles'])) {
            throw new \Exception('Rbac roles are not configured');
        }

        if(!isset($config['permissions'])) {
            throw new \Exception('Rbac permissions are not configured');
        }

        $rbac = new Rbac();
        $rbac->setCreateMissingRoles(true);

        $perRoleAssertions = [];
        $perPermissionAssertions = [];

        foreach($config['roles'] as $role => $options)
        {
            $rbac->addRole($role, $options['parents'] ?? null);
            $perRoleAssertions[$role] = $options['assertions'] ?? [];
        }

        foreach($config['permissions'] as $role => $permissions)
        {
            foreach($permissions as $permission => $options)
            {
                $rbac->getRole($role)->addPermission($permission);
                $perPermissionsAssertions[$role]['include'] = $options['assertions_include'] ?? [];
                $perPermissionsAssertions[$role]['exclude'] = $options['assertions_exclude'] ?? [];
            }
        }

        return new AuthorizationMiddleware(
            $rbac,
            $container->get(AssertionPluginManager::class),
            $perRoleAssertions,
            $perPermissionsAssertions
        );
    }
}
