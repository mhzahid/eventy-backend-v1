<?php

namespace App\Services\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Contracts\SnsCapable;
use App\DTOs\PaymentResult;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BkashGateway implements PaymentGatewayInterface, SnsCapable
{
    private string $username;
    private string $password;
    private string $appKey;
    private string $appSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->username  = config('services.bkash.username');
        $this->password  = config('services.bkash.password');
        $this->appKey    = config('services.bkash.app_key');
        $this->appSecret = config('services.bkash.app_secret');
        $this->baseUrl   = config('services.bkash.sandbox')
            ? 'https://tokenized.sandbox.bka.sh/v1.2.0-beta'
            : 'https://tokenized.pay.bka.sh/v1.2.0-beta';
    }

    private const ID_TOKEN_KEY      = 'bkash:id_token';
    private const REFRESH_TOKEN_KEY = 'bkash:refresh_token';
    private const TOKEN_TTL         = 3300; // 55 min — 5 min buffer before bKash's 1 h expiry

    private function getToken(): string
    {
        $cached = Cache::get(self::ID_TOKEN_KEY);
        if ($cached) {
            return $cached;
        }

        $refreshToken = Cache::get(self::REFRESH_TOKEN_KEY);
        if ($refreshToken) {
            $token = $this->refreshToken($refreshToken);
            if ($token) {
                return $token;
            }
        }

        return $this->grantToken();
    }

    private function grantToken(): string
    {
        $response = Http::withHeaders([
            'username'     => $this->username,
            'password'     => $this->password,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/tokenized/checkout/token/grant", [
            'app_key'    => $this->appKey,
            'app_secret' => $this->appSecret,
        ]);

        $data = $response->json();

        // Log::channel('bkash')->info('bKash token granted', ['status' => $data['statusCode'] ?? null]);

        Cache::put(self::ID_TOKEN_KEY, $data['id_token'], self::TOKEN_TTL);
        Cache::put(self::REFRESH_TOKEN_KEY, $data['refresh_token'], now()->addDays(30));

        return $data['id_token'];
    }

    private function refreshToken(string $refreshToken): ?string
    {
        $response = Http::withHeaders([
            'username'     => $this->username,
            'password'     => $this->password,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/tokenized/checkout/token/refresh", [
            'app_key'       => $this->appKey,
            'app_secret'    => $this->appSecret,
            'refresh_token' => $refreshToken,
        ]);

        $data = $response->json();

        if (($data['statusCode'] ?? '') !== '0000') {
            Log::channel('bkash')->warning('bKash token refresh failed, falling back to grant', ['status' => $data['statusCode'] ?? null]);
            return null;
        }

        // Log::channel('bkash')->info('bKash token refreshed', ['status' => $data['statusCode']]);

        Cache::put(self::ID_TOKEN_KEY, $data['id_token'], self::TOKEN_TTL);
        Cache::put(self::REFRESH_TOKEN_KEY, $data['refresh_token'], now()->addDays(30));

        return $data['id_token'];
    }

    public function initiate(array $payload): PaymentResult
    {
        $token = $this->getToken();

        $headers = [
            'Authorization' => $token,
            'X-APP-Key'     => $this->appKey,
        ];

        $url = "{$this->baseUrl}/tokenized/checkout/create";

        $body = [
            'mode'                  => '0011',
            'payerReference'        => $payload['customer']['phone'],
            'callbackURL'           => $payload['success_url'],
            'amount'                => (string) $payload['amount'],
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'merchantInvoiceNumber' => $payload['order_id'],
        ];

        $response = Http::withHeaders($headers)->post($url, $body);

        $data = $response->json();

        Log::channel('bkash')->info('Create API', ["url" => $url, "header" => $headers, "body" => $body, "api response" => $data]);

        if (($data['statusCode'] ?? '') !== '0000') {
            return new PaymentResult(
                success: false,
                gateway: 'bkash',
                message: $data['statusMessage'] ?? 'Failed to initiate bKash payment.',
            );
        }

        return new PaymentResult(
            success: true,
            gateway: 'bkash',
            redirectUrl: $data['bkashURL'],
            transactionId: $data['paymentID'],
        );
    }

    public function getOrderId(array $payload): ?string
    {
        // Callback: paymentID from user redirect
        if ($payload['paymentID'] ?? null) {
            $orderId = Order::select('id')->where('gateway_payment_id', $payload['paymentID'])->value('id');

            return $orderId ?? null;
        }

        // IPN: SNS Notification — order ID is inside the nested Message JSON
        if (($payload['Type'] ?? '') === 'Notification') {
            $message = json_decode($payload['Message'] ?? '{}', true);
            return $message['merchantInvoiceNumber'] ?? null;
        }

        // Callback redirect — bKash passes merchantInvoiceNumber in query string
        return $payload['merchantInvoiceNumber'] ?? null;
    }

    public function verify(array $payload): PaymentResult
    {
        // IPN path: AWS SNS Notification envelope
        if (($payload['Type'] ?? '') === 'Notification') {
            return $this->verifyWebhookNotification($payload);
        }

        // Callback path: paymentID from user redirect
        $paymentId = $payload['paymentID'] ?? null;

        if (!$paymentId) {
            return new PaymentResult(
                success: false,
                gateway: 'bkash',
                message: 'Missing payment ID.',
            );
        }

        $token = $this->getToken();

        $headers = [
            'Authorization' => $token,
            'X-APP-Key'     => $this->appKey,
        ];

        $url = "{$this->baseUrl}/tokenized/checkout/execute";

        $body = [
            'paymentID' => $paymentId,
        ];

        $response = Http::withHeaders($headers)->post($url, $body);

        if (empty($response)) {
            // if execute api failed to response
            sleep(1);
            $response = $this->queryPayment($paymentId);
        }

        $data = $response->json();

        Log::channel('bkash')->info('Execute API', ["url" => $url, "header" => $headers, "body" => $body, "api response" => $data]);

        if (($data['statusCode'] ?? '') === '0000' && ($data['transactionStatus'] ?? '') === 'Completed') {
            return new PaymentResult(
                success: true,
                gateway: 'bkash',
                transactionId: $data['trxID'],
            );
        }

        return new PaymentResult(
            success: false,
            gateway: 'bkash',
            message: $data['statusMessage'] ?? 'Payment execution failed.',
        );
    }

    private function queryPayment(string $paymentId)
    {
        $token = $this->getToken();

        $response = Http::withHeaders([
            'Authorization' => $token,
            'X-APP-Key'     => $this->appKey,
        ])->post("{$this->baseUrl}/tokenized/checkout/payment/status", [
            'paymentID' => $paymentId,
        ]);

        return $response;
    }

    public function refund(string $paymentId, string $trxId, float $amount): PaymentResult
    {
        $token = $this->getToken();

        $url = "{$this->baseUrl}/tokenized/checkout/payment/refund";

        $headers = [
            'Authorization' => $token,
            'X-APP-Key'     => $this->appKey,
        ];

        $body = [
            'paymentID' => $paymentId,
            'trxID'     => $trxId,
            'amount'    => number_format($amount, 2, '.', ''),
            'reason'    => 'Refund',
            'sku'       => 'ticket',
        ];

        $response = Http::withHeaders($headers)->post($url, $body);

        $data = $response->json();

        Log::channel('bkash')->info('Refund API', ['url' => $url, 'body' => $body, 'api response' => $data]);

        if (isset($data['refundTrxID'])) {
            return new PaymentResult(
                success: true,
                gateway: 'bkash',
                transactionId: $data['refundTrxID'],
            );
        }

        return new PaymentResult(
            success: false,
            gateway: 'bkash',
            message: $data['statusMessage'] ?? 'Refund failed.',
        );
    }

    private function verifyWebhookNotification(array $payload): PaymentResult
    {
        if (!$this->verifySnsSignature($payload)) {
            return new PaymentResult(
                success: false,
                gateway: 'bkash',
                message: 'Invalid webhook signature.',
            );
        }

        $message = json_decode($payload['Message'] ?? '{}', true);

        if (($message['transactionStatus'] ?? '') !== 'Completed') {
            return new PaymentResult(
                success: false,
                gateway: 'bkash',
                message: 'Transaction not completed.',
            );
        }

        return new PaymentResult(
            success: true,
            gateway: 'bkash',
            transactionId: $message['trxID'],
        );
    }

    private function verifySnsSignature(array $payload): bool
    {
        $signingCertURL = $payload['SigningCertURL'] ?? '';

        // Only trust certs hosted on AWS SNS domains
        $certUrlValidation = $this->validateUrl($signingCertURL);

        if (!$certUrlValidation) {
            return false;
        }

        $cert = Http::get($signingCertURL)->body();

        // $key = openssl_get_publickey($cert);
        // if (!$key) {
        //     return false;
        // }

        $stringToSign = $this->buildSnsStringToSign($payload);

        if (empty($stringToSign)) {
            return false;
        }

        $signature    = base64_decode($payload['Signature'] ?? '');

        try {
            return openssl_verify($stringToSign, $signature, $cert, OPENSSL_ALGO_SHA1) === 1;
        } catch (\Exception $e) {
            Log::channel('bkashIPN')->error('openssl verify exception', ['message' => $e->getMessage(), 'stack' => $e->getTraceAsString()]);

            return false;
        }
    }

    private function validateUrl($url)
    {
        $defaultHostPattern = '/^sns\.[a-zA-Z0-9\-]{3,}\.amazonaws\.com(\.cn)?$/';
        $parsed = parse_url($url);

        if (empty($parsed['scheme']) || empty($parsed['host']) || $parsed['scheme'] !== 'https' || substr($url, -4) !== '.pem' || !preg_match($defaultHostPattern, $parsed['host'])) {
            return false;
        } else {
            return true;
        }
    }

    private function buildSnsStringToSign(array $payload): string
    {
        if ($payload['SignatureVersion'] !== '1') {
            $errorLog =  "The SignatureVersion \"{$payload['SignatureVersion']}\" is not supported.";
            Log::channel('bkashIPN')->error('SignatureVersion-Error', ['message' => $errorLog]);
            return '';
        }

        // AWS SNS signing fields in canonical order (Subject only included when present)
        $fields = ['Message', 'MessageId', 'Subject', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];
        $stringToSign  = '';

        foreach ($fields as $field) {
            if (isset($payload[$field])) {
                if (is_array($payload[$field])) {
                    $stringToSign .= "{$field}\n" . implode("\n", $payload[$field]) . "\n";
                } else {
                    $stringToSign .= "{$field}\n{$payload[$field]}\n";
                }
            }
        }

        return $stringToSign;
    }

    public function confirmSnsSubscription(array $payload): ?string
    {
        // Log::channel('bkashIPN')->info('SNS webhook payload', ['payload' => $payload]);

        $subscribeUrl = $payload['SubscribeURL'] ?? null;

        $this->verifySnsSignature($payload);

        $response = Http::get($subscribeUrl)->body();

        Log::channel('bkashIPN')->info('SNS subscription confirmed', ['response' => $response]);

        return $response;
    }

    public function searchTransaction(array $request)
    {
        $token = $this->getToken();

        $url = "{$this->baseUrl}/tokenized/checkout/general/searchTransaction";

        $headers = [
            'Authorization' => $token,
            'X-APP-Key'     => $this->appKey,
        ];

        $body = [
            'trxID'     => $request['trxId'],
        ];

        $response = Http::withHeaders($headers)->post($url, $body);

        $data = $response->json();

        return $data;
    }
}
