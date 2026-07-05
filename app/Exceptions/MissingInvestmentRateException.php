<?php

namespace App\Exceptions;

use Exception;

class MissingInvestmentRateException extends Exception
{
    public function __construct(int $year)
    {
        parent::__construct("No investment rate configured for {$year}. Set a company-wide default rate or a per-investment override before posting interest.");
    }
}
