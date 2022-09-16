<?php

declare(strict_types=1);

namespace TryAgainLater\MediaConvertAppApi\Domain;

use Exception;

class DomainRecordNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct('Record does not exist.');
    }
}
