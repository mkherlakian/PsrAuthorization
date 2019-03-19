<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization\ResourceOwnerAssertion;

use Zend\ServiceManager\AbstractPluginManager;

class ResourceOwnerPluginManager extends AbstractPluginManager
{
    protected $instanceOf = CanDetermineOwnership::class;
}
