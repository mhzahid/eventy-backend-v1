<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentResult;
use Illuminate\Support\Facades\Http;

class SslcommerzGateway implements PaymentGatewayInterface
{
    private string $storeId;
    private string $storePassword;
    private string $baseUrl;

    public function __construct()
    {
        $this->storeId       = config('services.sslcommerz.store_id');
        $this->storePassword = config('services.sslcommerz.store_password');
        $this->baseUrl       = config('services.sslcommerz.sandbox')
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    public function initiate(array $payload): PaymentResult
    {
        $response = Http::asForm()->post("{$this->baseUrl}/gwprocess/v4/api.php", [
            'store_id'         => $this->storeId,
            'store_passwd'     => $this->storePassword,
            'total_amount'     => $payload['amount'],
            'currency'         => $payload['currency'] ?? 'BDT',
            'tran_id'          => $payload['order_id'],
            'success_url'      => $payload['success_url'],
            'fail_url'         => $payload['fail_url'],
            'cancel_url'       => $payload['cancel_url'],

            # CUSTOMER INFORMATION
            'cus_name'         => $payload['customer']['name'],
            'cus_email'        => $payload['customer']['email'],
            'cus_phone'        => $payload['customer']['phone'],
            'cus_add1'         => 'Bangladesh',
            'cus_city'         => 'Dhaka',
            'cus_country'      => 'Bangladesh',

            'shipping_method'  => 'NO',
            'product_name'     => 'Event Ticket',
            'product_category' => 'Ticket',
            'product_profile'  => 'general',
        ]);

        $data = $response->json();

        if (($data['status'] ?? '') !== 'SUCCESS') {
            return new PaymentResult(
                success: false,
                gateway: 'sslcommerz',
                message: $data['failedreason'] ?? 'Failed to initiate SSLCommerz payment.',
            );
        }

        return new PaymentResult(
            success: true,
            gateway: 'sslcommerz',
            redirectUrl: $data['GatewayPageURL'],
        );
    }

    public function getOrderId(array $payload): ?string
    {
        return $payload['tran_id'] ?? null;
    }

    public function verify(array $payload): PaymentResult
    {
        $valId = $payload['val_id'] ?? null;

        if (!$valId) {
            return new PaymentResult(
                success: false,
                gateway: 'sslcommerz',
                message: 'Missing validation ID.',
            );
        }

        $response = Http::get("{$this->baseUrl}/validator/api/validationserverAPI.php", [
            'val_id'       => $valId,
            'store_id'     => $this->storeId,
            'store_passwd' => $this->storePassword,
            'v'            => 1,
            'format'       => 'json',
        ]);

        $data = $response->json();

        if (!in_array($data['status'] ?? '', ['VALID', 'VALIDATED'])) {
            return new PaymentResult(
                success: false,
                gateway: 'sslcommerz',
                message: 'Payment validation failed.',
            );
        }

        return new PaymentResult(
            success: true,
            gateway: 'sslcommerz',
            transactionId: $data['bank_tran_id'],
        );
    }

    public function transactionStatus(string $merchantTransId): PaymentResult
    {
        $response = Http::get("{$this->baseUrl}/validator/api/merchantTransIDvalidationAPI.php", [
            'merchant_trans_id' => $merchantTransId,
            'store_id'          => $this->storeId,
            'store_passwd'      => $this->storePassword,
            'v'                 => 1,
            'format'            => 'json',
        ]);

        $data = $response->json();

        if (!in_array($data['status'] ?? '', ['VALID', 'VALIDATED'])) {
            return new PaymentResult(
                success: false,
                gateway: 'sslcommerz',
                message: 'Transaction not found or invalid.',
            );
        }

        return new PaymentResult(
            success: true,
            gateway: 'sslcommerz',
            transactionId: $data['bank_tran_id'] ?? null,
            data: $data,
        );
    }

    public function refund(string $bankTransId, float $amount, string $remarks = ''): PaymentResult
    {
        $response = Http::asForm()->post("{$this->baseUrl}/validator/api/merchantTransIDvalidationAPI.php", [
            'bank_tran_id'   => $bankTransId,
            'store_id'       => $this->storeId,
            'store_passwd'   => $this->storePassword,
            'refund_amount'  => $amount,
            'refund_remarks' => $remarks,
            'v'              => 1,
            'format'         => 'json',
        ]);

        $data = $response->json();

        if (($data['APIConnect'] ?? '') !== 'DONE' || empty($data['bank_tran_id'])) {
            return new PaymentResult(
                success: false,
                gateway: 'sslcommerz',
                message: $data['errorReason'] ?? 'Refund initiation failed.',
            );
        }

        return new PaymentResult(
            success: true,
            gateway: 'sslcommerz',
            transactionId: $data['bank_tran_id'],
            data: $data,
        );
    }

    public function refundStatus(string $refundRefId): PaymentResult
    {
        $response = Http::asForm()->post("{$this->baseUrl}/validator/api/merchantTransIDvalidationAPI.php", [
            'refund_ref_id' => $refundRefId,
            'store_id'      => $this->storeId,
            'store_passwd'  => $this->storePassword,
            'v'             => 1,
            'format'        => 'json',
        ]);

        $data = $response->json();

        if (($data['APIConnect'] ?? '') !== 'DONE') {
            return new PaymentResult(
                success: false,
                gateway: 'sslcommerz',
                message: 'Refund status check failed.',
            );
        }

        return new PaymentResult(
            success: true,
            gateway: 'sslcommerz',
            data: $data,
        );
    }
}
