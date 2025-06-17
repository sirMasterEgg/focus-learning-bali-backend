<?php

namespace App\Http\Controllers\Api\Public;

use App\Classes\PaymentGateway\Exceptions\MidtransExceptions;
use App\Classes\PaymentGateway\Implementation\MidtransPaymentGateway;
use App\Classes\PaymentGateway\PaymentGateway;
use App\Classes\ResponseBuilder;
use App\Http\Requests\Donate\DonateRequest;
use App\Loggable;
use App\Models\Donation;
use App\Models\UserDonation;
use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class DonateController extends Controller
{
    use Loggable;

    private PaymentGateway $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function donate(DonateRequest $request)
    {

        try {
            \DB::beginTransaction();
            $donation = Donation::lockForUpdate()
                ->where('human_readable_id', $request->input('donation_id'))
                ->first();

            if (!$donation) {
                $this->logWarning('Donation not found', ['donation_id' => $request->input('donation_id')]);
                return ResponseBuilder::build(
                    result: null,
                    message: 'Donation not found',
                    code: Response::HTTP_NOT_FOUND
                );
            }
            if (!$donation->is_active) {
                $this->logWarning('Donation is not active', ['donation_id' => $donation->id]);
                return ResponseBuilder::build(
                    result: null,
                    message: 'Donation is not active',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            switch ($request->input('payment_method')) {
                case 'card':
                    $this->logInfo('Processing card payment');

                    $token = $request->input('token_id');
                    $saveCard = $request->input('save_card');
                    $userDonations = UserDonation::create([
                        'user_id' => auth()->id(),
                        'donation_id' => $donation->id,
                        'donation_name' => $request->input('name'),
                        'donation_email' => $request->input('email'),
                        'amount' => $request->input('amount'),
                        'payment_method' => $request->input('payment_method'),
                        'first_name' => $request->input('customer_details.first_name'),
                        'last_name' => $request->input('customer_details.last_name'),
                        'phone_number' => $request->input('customer_details.phone_number'),
                    ]);

                    $response = $this->paymentGateway->withCard($token, $saveCard)->charge($userDonations->human_readable_id, $userDonations->amount, [
                        'customer_details' => [
                            'first_name' => $userDonations->first_name,
                            'last_name' => $userDonations->last_name,
                            'phone' => $userDonations->phone_number,
                        ]
                    ]);

                    $userDonations->update([
                        'payment_id' => $response['transaction_id'],
                        'payment_status' => MidtransPaymentGateway::getStatusPayment($response['transaction_status'], $response['fraud_status']),
                        'payment_response' => $response,
                    ]);

                    \DB::commit();

                    $this->logInfo('Card payment successful', ['payment_id' => $response['transaction_id']]);

                    return ResponseBuilder::build(
                        result: [
                            'payment_method' => 'card',
                            'payment_id' => $response['transaction_id'],
                            'redirect_url' => $response['redirect_url'],
                            'expired_at' => Carbon::parse($response['transaction_time'])->addMinutes(10)->format('Y-m-d H:i:s'),
                            'amount' => $response['gross_amount'],
                        ],
                        message: 'Card payment successfully created',
                    );
                case 'qris':
                    $this->logInfo('Processing QRIS payment');

                    $userDonations = UserDonation::create([
                        'user_id' => auth()->id(),
                        'donation_id' => $donation->id,
                        'donation_name' => $request->input('name'),
                        'donation_email' => $request->input('email'),
                        'amount' => $request->input('amount'),
                        'payment_method' => $request->input('payment_method'),
                    ]);
                    $response = $this->paymentGateway->withQris()->charge($userDonations->human_readable_id, $userDonations->amount);

                    $userDonations->update([
                        'payment_id' => $response['transaction_id'],
                        'payment_status' => MidtransPaymentGateway::getStatusPayment($response['transaction_status'], $response['fraud_status']),
                        'payment_response' => $response,
                    ]);

                    \DB::commit();

                    $this->logInfo('QRIS payment successful', ['payment_id' => $response['transaction_id']]);

                    $actionsQrObject = array_filter($response['actions'], function ($action) {
                        return $action['name'] === 'generate-qr-code';
                    });

                    $redirectUrl = !empty($actionsQrObject) ? array_values($actionsQrObject)[0]['url'] : null;

                    return ResponseBuilder::build(
                        result: [
                            'payment_method' => 'qris',
                            'payment_id' => $response['transaction_id'],
                            'qris_url' => $redirectUrl,
                            'expired_at' => $response['expiry_time'],
                            'amount' => $response['gross_amount'],
                        ],
                        message: 'QRIS payment successfully created',
                    );
                default:
                    $this->logWarning('Unsupported payment method', ['payment_method' => $request->input('payment_method')]);

                    return ResponseBuilder::build(
                        result: null,
                        message: 'Payment method not found',
                        code: Response::HTTP_BAD_REQUEST
                    );
            }
        } catch (MidtransExceptions $e) {
            \DB::rollBack();
            $this->logError('Midtrans error during donation', [], $e);

            return ResponseBuilder::build(
                result: $e->getData(),
                message: $e->getMessage(),
                code: Response::HTTP_BAD_REQUEST
            );
        } catch (\Exception $e) {
            \DB::rollBack();
            $this->logCritical('Unexpected error during donation', [], $e);

            return ResponseBuilder::build(
                result: null,
                message: $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function donateNotification(string $userDonation)
    {
        $userDonation = UserDonation::where('payment_id', $userDonation)->first();

        if (!$userDonation) {
            $this->logWarning('User donation not found for notification', ['payment_id' => $userDonation]);
            return ResponseBuilder::build(
                result: null,
                message: 'User donation not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        $this->logInfo('Processing payment notification', [
            'payment_id' => $userDonation->payment_id,
            'status' => $userDonation->payment_status,
        ]);
        
        return match ($userDonation->payment_status) {
            'success' => ResponseBuilder::build(result: 'success', message: null),
            'pending' => ResponseBuilder::build(result: 'pending', message: null),
            default => ResponseBuilder::build(result: 'failed', message: null, code: Response::HTTP_NOT_FOUND),
        };
    }
}
