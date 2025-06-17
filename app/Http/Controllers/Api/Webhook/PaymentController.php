<?php

namespace App\Http\Controllers\Api\Webhook;

use App\Classes\PaymentGateway\Implementation\MidtransPaymentGateway;
use App\Classes\PaymentGateway\PaymentGateway;
use App\Classes\ResponseBuilder;
use App\Loggable;
use App\Models\Donation;
use App\Models\SavedCard;
use App\Models\UserDonation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    use Loggable;

    private PaymentGateway $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    private function isValidSignature(array $payload, string $hashed): bool
    {
        if (
            !isset($payload['order_id'], $payload['status_code'], $payload['gross_amount'])
        ) {
            return false;
        }
        $signatureKey = config('services.midtrans.server_key');
        $hash = hash('sha512', $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $signatureKey);
        return $hash === $hashed;
    }

    public function paymentNotification(Request $request)
    {
        $payload = $request->only(['order_id', 'status_code', 'gross_amount']);

        if (
            !$this->isValidSignature(
                [
                    'order_id' => $payload['order_id'],
                    'status_code' => $payload['status_code'],
                    'gross_amount' => $payload['gross_amount'],
                ],
                $request->input('signature_key')
            )
        ) {
            $this->logWarning('Invalid signature on initial notification', [
                'payload' => $payload,
                'provided_signature' => $request->input('signature_key'),
            ]);

            return ResponseBuilder::build(
                result: null,
                message: 'Invalid signature notification',
                code: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            \DB::beginTransaction();

            $currentStatus = $this->paymentGateway->getPaymentDetails($payload['order_id']);
            $paymentStatus = MidtransPaymentGateway::getStatusPayment($currentStatus['transaction_status'], $currentStatus['fraud_status']);

            if (
                !$this->isValidSignature(
                    [
                        'order_id' => $currentStatus['order_id'],
                        'status_code' => $currentStatus['status_code'],
                        'gross_amount' => $currentStatus['gross_amount'],
                    ],
                    $currentStatus['signature_key']
                )
            ) {
                $this->logWarning('Invalid signature on get status', [
                    'payload' => $currentStatus,
                    'provided_signature' => $currentStatus['signature_key'],
                ]);

                return ResponseBuilder::build(
                    result: null,
                    message: 'Invalid signature get status notification',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            $userDonation = UserDonation::lockForUpdate()
                ->where('human_readable_id', $request->input('order_id'))
                ->first();

            $userDonation->update([
                'payment_status' => $paymentStatus,
                'updated_at' => now(),
            ]);

            $donation = Donation::lockForUpdate()
                ->where('id', $userDonation->donation_id)
                ->first();

            if ($paymentStatus === 'success') {
                \Mail::to($userDonation->donation_email)->send(new \App\Mail\InvoiceMail($userDonation));

                $donation->update([
                    'current_donation' => $donation->current_donation + $userDonation->amount,
                ]);

                $this->logInfo('Donation successfully paid', [
                    'donation_id' => $donation->id,
                    'user_donation_id' => $userDonation->id,
                    'payment_status' => $paymentStatus,
                ]);
            }

            if (
                $request->has('saved_token_id') &&
                $request->has('saved_token_id_expired_at') &&
                $request->has('masked_card')
            ) {
                $savedCard = SavedCard::lockForUpdate()
                    ->where('user_id', $userDonation->user_id)
                    ->where('masked_card', $request->input('masked_card'))
                    ->first();

                if ($savedCard) {
                    $savedCard->update([
                        'card_token' => $request->input('saved_token_id'),
                        'card_expiration' => $request->input('saved_token_id_expired_at'),
                        'updated_at' => now(),
                    ]);

                    $this->logInfo('Saved card updated for user', [
                        'user_id' => $userDonation->user_id,
                        'masked_card' => $request->input('masked_card'),
                    ]);
                } else {
                    SavedCard::create([
                        'user_id' => $userDonation->user_id,
                        'card_token' => $request->input('saved_token_id'),
                        'masked_card' => $request->input('masked_card'),
                        'card_expiration' => $request->input('saved_token_id_expired_at'),
                    ]);

                    $this->logInfo('New saved card created for user', [
                        'user_id' => $userDonation->user_id,
                        'masked_card' => $request->input('masked_card'),
                    ]);
                }
            }

            $this->logInfo('Webhook fully processed', [
                'donation_id' => $donation->id,
                'user_donation_id' => $userDonation->id,
                'payment_status' => $paymentStatus,
            ]);


            \DB::commit();
            return ResponseBuilder::build(
                result: null,
                message: 'Webhook processed successfully',
            );
        } catch (\Exception $e) {
            $this->logError('Exception occurred during webhook processing', [
                'order_id' => $payload['order_id'] ?? null,
                'error_message' => $e->getMessage(),
            ], $e);
            
            \DB::rollBack();
            return ResponseBuilder::build(
                result: null,
                message: 'Failed to process webhook',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
