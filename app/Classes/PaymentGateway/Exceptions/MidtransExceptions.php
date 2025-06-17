<?php

namespace App\Classes\PaymentGateway\Exceptions;

use Exception;
use Throwable;

class MidtransExceptions extends Exception
{
    protected $data;

    public function __construct(string $message = "", array|null|object $data = null, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    public function getData(): array|null|object
    {
        return $this->data;
    }
}
