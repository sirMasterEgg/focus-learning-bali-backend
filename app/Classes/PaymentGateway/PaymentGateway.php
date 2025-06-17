<?php

namespace App\Classes\PaymentGateway;

interface PaymentGateway
{
    public function charge(string $invoiceNumber, float $amount, ?array $otherData = null): array|null;

    public function getPaymentDetails(string $invoiceNumber): array|null;

    public function withQris(): PaymentGateway;

    public function withCard(string $cardToken, bool $saveCard): PaymentGateway;

}
