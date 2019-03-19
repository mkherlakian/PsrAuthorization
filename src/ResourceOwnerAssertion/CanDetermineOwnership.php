<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization\ResourceOwnerAssertion;

interface CanDetermineOwnership
{
    public function isOwn(string $resourceId, string $authenticatedUserId) : bool;
}
