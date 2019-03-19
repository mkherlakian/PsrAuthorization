<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization;

use Zend\Permissions\Rbac\AssertionInterface;
use Zend\ServiceManager\AbstractPluginManager;

class AssertionPluginManager extends AbstractPluginManager
{
    protected $instanceOf = AssertionInterface::class;
}
