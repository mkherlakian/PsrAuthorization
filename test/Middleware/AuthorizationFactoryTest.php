<?php

use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Mauricek\PsrAuthorization\AssertionPluginManager;
use Mauricek\PsrAuthorization\Middleware\AuthorizationFactory;
use Mauricek\PsrAuthorization\Middleware\AuthorizationMiddleware;

class AuthorizationFactoryTest extends TestCase
{
    private $container;

    public function setUp()
    {
        $testConfig = [
            'permissions' => [
                'roles' => [
                    'admin'  => [],
                    'member' => [
                        'parents' => 'admin',
                        'assertions' => [
                            'perRoleAssertion1'
                            //Assertion for own id
                        ]
                    ],
                    'anonymous' => [
                        'parents' => 'member'
                    ]
                ],
                'permissions' => [
                    'anonymous' => [
                        'api.login' => [],
                    ],
                    'member' => [
                        'api.friends'              => [],
                        'api.statistics'       => [
                            'assertions_include'        => [],
                            'assertions_exclude'        => ['perRoleAssertion1']
                        ],
                        'api.balance'                => [
                            'assertions_include'        => ['perPermissionAssertion1'],
                            'assertions_exclude'        => []
                        ],
                    ],
                    'admin' => [
                        'api.users' => []
                    ]
                ]
            ]
        ];

        $container = $this->prophesize(ContainerInterface::class);
        $container->get('config')->willReturn($testConfig);

        $assertionPM = $this->prophesize(AssertionPluginManager::class);

        $container->get(AssertionPluginManager::class)->willReturn($assertionPM->reveal());

        $this->container = $container->reveal();
    }

    public function testGet()
    {
        $factory = new AuthorizationFactory();
        $authorizationAction = $factory($this->container);

        $this->assertInstanceOf(AuthorizationMiddleware::class, $authorizationAction);
    }
}
