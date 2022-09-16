<?php

namespace TryAgainLater\MediaConvertAppApi\Persistence;

use Exception;

class PersistenceException extends Exception
{
    public function __construct()
    {
        parent::__construct('Failed to persist a record.');
    }
}
