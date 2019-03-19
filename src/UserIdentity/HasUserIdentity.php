<?php

declare(strict_types=1);

namespace Mauricek\PsrAuthorization\UserIdentity;

interface HasUserIdentity
{
    public function userId() : string;
    public function role() : string;
}
