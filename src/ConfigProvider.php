<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization;

use Mauricek\PsrAuthorization\ResourceOwnerAssertion\ResourceOwnerPluginManager;
use Psr\Container\ContainerInterface;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'invokables' => [
                Middleware\PsrAuthCredentialsTranslatorMiddleware::class => Middleware\PsrAuthCredentialsTranslatorMiddleware::class
            ],
            'factories' => [
                Middleware\AuthorizationMiddleware::class => Middleware\AuthorizationFactory::class,
                ResourceOwnerPluginManager::class => function(ContainerInterface $container, $requestedName) {
                    return new ResourceOwnerPluginManager($container, [
                        'abstract_factories' => [
                        ],
                        'aliases' => [
                            //List all classes that validates resource ownership

                        ]
                    ]);
                },
                AssertionPluginManager::class => function(ContainerInterface $container, $requestedName) {
                    return new AssertionPluginManager($container, [
                        'factories' => [
                            ResourceOwnerAssertion\IsResourceOwnerAssertion::class => ResourceOwnerAssertion\IsResourceOwnerAssertionFactory::class
                        ]
                    ]);
                }
            ]
        ];
    }
}
