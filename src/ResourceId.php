<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization;

class ResourceId
{
    private $resourceId;

    public static function from(string $resourceId) : self
    {
        return new self($resourceId);
    }

    private function __construct(string $resourceId)
    {
        $this->resourceId = $resourceId;
    }

    public function toString()
    {
        return $this->resourceId;
    }
}
