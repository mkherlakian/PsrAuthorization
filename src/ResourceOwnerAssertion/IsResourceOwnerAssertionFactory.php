<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization\ResourceOwnerAssertion;

use Psr\Container\ContainerInterface;
use Mauricek\PsrAuthorization\AssertionContext;

class IsResourceOwnerAssertionFactory
{
    public function __invoke(ContainerInterface $container, string $requestedName, array $options)
    {
        if(!array_key_exists('assertion_context', $options))
        {
            throw new \RuntimeException('Must supply assertion_context of type '.AssertionContext::class);
        }

        if(!$options['assertion_context'] instanceof AssertionContext::class)
        {
            throw new \RuntimeException('assertion_context must be of type '.AssertionContext::class);
        }

        return new IsResourceOwnerAssertion(
            $container->get(ResourceOwnerPluginManager::class),
            $options['assertion_context']
        );
    }
}
