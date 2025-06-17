<?php

namespace App\Classes\PaymentGateway\Implementation;

use App\Classes\PaymentGateway\Exceptions\MidtransExceptions;
use App\Classes\PaymentGateway\PaymentGateway;
use App\Loggable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class MidtransPaymentGateway implements PaymentGateway
{
    use Loggable;

    private string $url;
    private string $server_key;
    private string $client_key;
    private PendingRequest $http;
    private array $payload;

    public function __construct()
    {
        $this->url = config('services.midtrans.url');
        $this->server_key = config('services.midtrans.server_key');
        $this->client_key = config('services.midtrans.client_key');

        $this->http = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($this->server_key . ':'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
        $this->logInfo('MidtransPaymentGateway initialized');
    }

    public function charge(string $invoiceNumber, float $amount, ?array $otherData = null): array|null
    {
        $this->payload['transaction_details'] = [
            'order_id' => $invoiceNumber,
            'gross_amount' => $amount,
        ];

        if (!empty($otherData)) {
            $this->payload = array_merge($this->payload, $otherData);
        }

        try {
            $this->logInfo('Charging payment', [
                'invoice_number' => $invoiceNumber,
                'amount' => $amount,
                'other_data' => $otherData,
            ]);

            $response = $this->http->post($this->url . '/v2/charge', $this->payload);

            if ($response['status_code'] >= 400 && $response['status_code'] < 500) {
                throw new MidtransExceptions('Failed to charge payment', $response->json());
            }

            if ($response['status_code'] >= 500) {
                throw new MidtransExceptions('Server error', $response->json());
            }

            $this->logInfo('Payment charged successfully', [
                'invoice_number' => $invoiceNumber,
                'response' => $response->json(),
            ]);

            return $response->json();
        } catch (ConnectionException $e) {
            $this->logError('Failed to connect to payment gateway', [
                'invoice_number' => $invoiceNumber,
                'error_message' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to connect to payment gateway');
        } catch (MidtransExceptions $e) {
            $this->logError('Midtrans exception occurred', [
                'invoice_number' => $invoiceNumber,
                'error_message' => $e->getMessage(),
                'response' => $e->getResponse(),
            ]);
            throw $e;
        }
    }

    public function getPaymentDetails(string $invoiceNumber): array|null
    {
        try {
            $this->logInfo('Retrieving payment details', [
                'invoice_number' => $invoiceNumber,
            ]);

            $response = $this->http->get($this->url . '/v2/' . $invoiceNumber . '/status');

            $this->logInfo('Payment details retrieved successfully', [
                'invoice_number' => $invoiceNumber,
                'response' => $response->json(),
            ]);

            return $response->json();
        } catch (ConnectionException $e) {
            $this->logError('Failed to connect to payment gateway', [
                'invoice_number' => $invoiceNumber,
                'error_message' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to connect to payment gateway');
        }
    }

    public function withQris(): MidtransPaymentGateway
    {
        $this->payload['payment_type'] = 'qris';

        $this->logInfo('Payment type set to QRIS', [
            'payload' => $this->payload,
        ]);

        return $this;
    }

    public function withCard(string $cardToken, bool $saveCard): MidtransPaymentGateway
    {
        $this->payload['payment_type'] = 'credit_card';
        $this->payload['credit_card'] = [
            'token_id' => $cardToken,
            'authentication' => true,
        ];

        if ($saveCard) {
            $this->payload['credit_card']['save_token_id'] = true;
        }

        $this->logInfo('Payment type set to Credit Card', [
            'card_token' => $cardToken,
            'save_card' => $saveCard,
            'payload' => $this->payload,
        ]);

        return $this;
    }

    public static function getStatusPayment(string $transactionStatus, string $fraudStatus): string
    {
        if ($transactionStatus === 'capture') {
            if ($fraudStatus === 'challenge') {
                return 'challenge';
            } else if ($fraudStatus === 'accept') {
                return 'success';
            }
        } else if ($transactionStatus === 'settlement') {
            return 'success';
        } else if ($transactionStatus === 'cancel' || $transactionStatus === 'deny' || $transactionStatus === 'expire') {
            return 'failed';
        } else if ($transactionStatus === 'pending') {
            return 'pending';
        } else if ($transactionStatus === 'authorize') {
            return 'authorize';
        }

        return 'failed';
    }
}
