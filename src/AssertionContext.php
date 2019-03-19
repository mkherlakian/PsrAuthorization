<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization;

class AssertionContext
{
    private $authenticatedUserId;
    private $requestedResourceId;

    public static function withData($authId, $resourceId)
    {
        return new self($authId, $resourceId);
    }

    private function __construct($authId, $resourceId)
    {
        $this->authenticatedUserId = $authId;
        $this->requestedResourceId = $resourceId;
    }

    public function authenticatedUserId() : string
    {
        return $this->authenticatedUserId;
    }

    public function requestedResourceId() : string
    {
        return $this->requestedResourceId;
    }
}
