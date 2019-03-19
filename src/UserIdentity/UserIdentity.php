<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization\UserIdentity;

final class UserIdentity implements HasUserIdentity
{
    private $userId;
    private $role;

    private function __construct(string $userId, string $role)
    {
        $this->userId = $userId;
        $this->role   = $role;
    }

    public static function with(string $userId, string $role)
    {
        return new self($userId, $role);
    }

    public function userId() : string
    {
        return $this->userId;
    }

    public function role() : string
    {
        return $this->role;
    }
}
