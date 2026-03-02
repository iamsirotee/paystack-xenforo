<?php

/**
 * Paystack Payment Gateway for XenForo 2.2
 *
 * @package     TheophilusA/Paystack
 * @author      Theophilus Adegbohungbe
 * @copyright   Copyright (c) 2026 Theophilus Adegbohungbe
 * @website     https://theophilusadegbohungbe.com
 * @license     GNU General Public License v3.0 (GPL-3.0)
 */

namespace TheophilusA\Paystack\Payment;

use XF\Entity\PaymentProfile;
use XF\Entity\PurchaseRequest;
use XF\Mvc\Controller;
use XF\Payment\AbstractProvider;
use XF\Payment\CallbackState;
use XF\Purchasable\Purchase;
use XF\Http\Request;

class Paystack extends AbstractProvider
{
    public function getTitle()
    {
        return 'Paystack';
    }

    protected function getPaymentParams(PurchaseRequest $purchaseRequest, Purchase $purchase)
    {
        $baseUrl = \XF::app()->options()->boardUrl;
        $callbackUrl = $baseUrl . '/payment_callback.php?_xfProvider=paystack';

        $params = [
            'email'        => $purchase->purchaser->email,
            'amount'       => (int) round($purchaseRequest->cost_amount * 100), // Convert to smallest unit (kobo/pesewa/cent)
            'currency'     => strtoupper($purchaseRequest->cost_currency),
            'reference'    => $this->generateReference($purchaseRequest),
            'callback_url' => $callbackUrl,

            // Customer info shown in Paystack dashboard
            'customer' => [
                'email'      => $purchase->purchaser->email,
                'first_name' => $purchase->purchaser->username,
            ],

            // Metadata - custom_fields are visible in Paystack dashboard, key/value pairs are returned in API response only
            'metadata' => [
                // Redirect user here if they cancel payment on Paystack checkout
                'cancel_action' => $baseUrl,

                // Internal key/value pairs - returned in API response but not shown on dashboard
                'request_key'      => $purchaseRequest->request_key,
                'purchasable_type' => $purchaseRequest->purchasable_type_id,
                'purchasable_id'   => $purchaseRequest->purchasable_id,
                'user_id'          => $purchase->purchaser->user_id,

                // custom_fields - displayed on the Paystack transaction dashboard
                'custom_fields' => [
                    [
                        'display_name'  => 'Product',
                        'variable_name' => 'product',
                        'value'         => $purchase->title,
                    ],
                    [
                        'display_name'  => 'Amount',
                        'variable_name' => 'amount',
                        'value'         => $purchaseRequest->cost_amount . ' ' . strtoupper($purchaseRequest->cost_currency),
                    ],
                    [
                        'display_name'  => 'Username',
                        'variable_name' => 'username',
                        'value'         => $purchase->purchaser->username,
                    ],
                    [
                        'display_name'  => 'User ID',
                        'variable_name' => 'user_id',
                        'value'         => $purchase->purchaser->user_id,
                    ],
                    [
                        'display_name'  => 'Site',
                        'variable_name' => 'site',
                        'value'         => \XF::app()->options()->boardTitle,
                    ],
                ]
            ]
        ];

        return $params;
    }

    protected function generateReference(PurchaseRequest $purchaseRequest)
    {
        return 'XF_' . $purchaseRequest->request_key . '_' . time();
    }

    public function renderConfig(PaymentProfile $profile)
    {
        $templater = \XF::app()->templater();
        
        // Generate URLs
        $baseUrl = \XF::app()->options()->boardUrl;
        $webhookUrl = $baseUrl . '/payment_callback.php?_xfProvider=paystack&type=webhook';
        $callbackUrl = $baseUrl . '/payment_callback.php?_xfProvider=paystack';
        
        $isTestMode = !empty($profile->options['test_mode']);
        
        return $templater->formCheckBoxRow([
        ], [
            [
                'name' => 'options[test_mode]',
                'value' => '1',
                'selected' => $isTestMode,
                'label' => 'Enable test mode',
                'data-xf-init' => 'paystack-mode-toggle'
            ]
        ], [
            'label' => 'Test Mode',
            'explain' => 'Toggle between test and live API keys. When enabled, test keys will be used. When disabled, live keys will be used for actual transactions.'
        ])
        . '<div class="formRow paystack-live-keys" style="display: ' . ($isTestMode ? 'none' : 'block') . ';">'
        . $templater->formTextBoxRow([
            'name' => 'options[live_secret_key]',
            'value' => $profile->options['live_secret_key'] ?? ''
        ], [
            'label' => 'Live Secret Key',
            'explain' => 'Your Paystack Live Secret Key (starts with sk_live_).'
        ])
        . '</div>'
        . '<div class="formRow paystack-live-keys" style="display: ' . ($isTestMode ? 'none' : 'block') . ';">'
        . $templater->formTextBoxRow([
            'name' => 'options[live_public_key]',
            'value' => $profile->options['live_public_key'] ?? ''
        ], [
            'label' => 'Live Public Key',
            'explain' => 'Your Paystack Live Public Key (starts with pk_live_).'
        ])
        . '</div>'
        . '<div class="formRow paystack-test-keys" style="display: ' . ($isTestMode ? 'block' : 'none') . ';">'
        . $templater->formTextBoxRow([
            'name' => 'options[test_secret_key]',
            'value' => $profile->options['test_secret_key'] ?? ''
        ], [
            'label' => 'Test Secret Key',
            'explain' => 'Your Paystack Test Secret Key (starts with sk_test_).'
        ])
        . '</div>'
        . '<div class="formRow paystack-test-keys" style="display: ' . ($isTestMode ? 'block' : 'none') . ';">'
        . $templater->formTextBoxRow([
            'name' => 'options[test_public_key]',
            'value' => $profile->options['test_public_key'] ?? ''
        ], [
            'label' => 'Test Public Key',
            'explain' => 'Your Paystack Test Public Key (starts with pk_test_).'
        ])
        . '</div>'
        . '<script>
        (function() {
            var checkbox = document.querySelector(\'input[name="options[test_mode]"]\');
            if (checkbox) {
                checkbox.addEventListener(\'change\', function() {
                    var liveKeys = document.querySelectorAll(\'.paystack-live-keys\');
                    var testKeys = document.querySelectorAll(\'.paystack-test-keys\');
                    
                    if (this.checked) {
                        liveKeys.forEach(function(el) { el.style.display = \'none\'; });
                        testKeys.forEach(function(el) { el.style.display = \'block\'; });
                    } else {
                        liveKeys.forEach(function(el) { el.style.display = \'block\'; });
                        testKeys.forEach(function(el) { el.style.display = \'none\'; });
                    }
                });
            }
        })();
        </script>'
        . $templater->formRow(
            '<div style="display:flex; gap:8px; align-items:center;">
                <input id="ps_callback_url" type="text" class="input" style="flex:1;" value="' . htmlspecialchars($callbackUrl) . '" readonly />
                <button type="button" class="button" onclick="
                    var el = document.getElementById(\'ps_callback_url\');
                    navigator.clipboard.writeText(el.value).then(function() {
                        var btn = el.nextElementSibling;
                        btn.textContent = \'Copied!\';
                        setTimeout(function() { btn.textContent = \'Copy\'; }, 2000);
                    });
                ">Copy</button>
            </div>',
            [
                'label' => 'Callback URL',
                'explain' => 'This is the redirect URL where users return after completing payment on Paystack. This is automatically configured when initiating payments.',
                'html' => ''
            ]
        )
        . $templater->formRow(
            '<div style="display:flex; gap:8px; align-items:center;">
                <input id="ps_webhook_url" type="text" class="input" style="flex:1;" value="' . htmlspecialchars($webhookUrl) . '" readonly />
                <button type="button" class="button" onclick="
                    var el = document.getElementById(\'ps_webhook_url\');
                    navigator.clipboard.writeText(el.value).then(function() {
                        var btn = el.nextElementSibling;
                        btn.textContent = \'Copied!\';
                        setTimeout(function() { btn.textContent = \'Copy\'; }, 2000);
                    });
                ">Copy</button>
            </div>',
            [
                'label' => 'Webhook URL',
                'explain' => 'Configure this webhook URL in your Paystack Dashboard (Settings > Webhooks) for reliable payment processing. Webhooks ensure payments are processed even if users close their browser after paying.',
                'html' => ''
            ]
        )
        . '<div style="margin-top:16px; padding:12px 16px; background:#f5f5f5; border-left:4px solid #00C3F7; border-radius:3px; font-size:12px; color:#555; line-height:1.6;">
            <strong style="color:#333; font-size:13px;">Paystack Payment Gateway</strong><br>
            Developed by <a href="https://theophilusadegbohungbe.com" target="_blank" style="color:#00C3F7; text-decoration:none; font-weight:bold;">Theophilus Adegbohungbe</a>
            &nbsp;&middot;&nbsp; Version 1.0.0
            &nbsp;&middot;&nbsp; &copy; ' . date('Y') . ' Theophilus Adegbohungbe. All rights reserved.<br>
            <span style="color:#e74c3c; font-weight:bold;">This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.</span>
        </div>';
    }

    public function verifyConfig(array &$options, &$errors = [])
    {
        $isTestMode = !empty($options['test_mode']);
        
        if ($isTestMode) {
            // Validate test keys when in test mode
            if (empty($options['test_secret_key'])) {
                $errors[] = 'Test Secret Key is required when test mode is enabled';
            } elseif (!$this->isTestKey($options['test_secret_key'], 'sk')) {
                $errors[] = 'Test Secret Key must start with sk_test_';
            }
            
            if (empty($options['test_public_key'])) {
                $errors[] = 'Test Public Key is required when test mode is enabled';
            } elseif (!$this->isTestKey($options['test_public_key'], 'pk')) {
                $errors[] = 'Test Public Key must start with pk_test_';
            }
        } else {
            // Validate live keys when in live mode
            if (empty($options['live_secret_key'])) {
                $errors[] = 'Live Secret Key is required';
            } elseif (!$this->isLiveKey($options['live_secret_key'], 'sk')) {
                $errors[] = 'Live Secret Key must start with sk_live_';
            }
            
            if (empty($options['live_public_key'])) {
                $errors[] = 'Live Public Key is required';
            } elseif (!$this->isLiveKey($options['live_public_key'], 'pk')) {
                $errors[] = 'Live Public Key must start with pk_live_';
            }
        }

        return empty($errors);
    }

    protected function isTestKey($key, $type)
    {
        return strpos($key, $type . '_test_') === 0;
    }

    protected function isLiveKey($key, $type)
    {
        return strpos($key, $type . '_live_') === 0;
    }

    public function initiatePayment(Controller $controller, PurchaseRequest $purchaseRequest, Purchase $purchase)
    {
        $profile = $purchaseRequest->PaymentProfile;
        $params = $this->getPaymentParams($purchaseRequest, $purchase);

        // Initialize Paystack transaction
        $result = $this->initializeTransaction($profile, $params);

        if (!$result || !isset($result['status']) || !$result['status']) {
            return $controller->error('Unable to initialize payment. Please try again.');
        }

        $authorizationUrl = $result['data']['authorization_url'];
        $reference = $result['data']['reference'];

        // Store reference for verification
        $purchaseRequest->provider_metadata = json_encode(['reference' => $reference]);
        $purchaseRequest->save();

        // Redirect to Paystack checkout
        return $controller->redirect($authorizationUrl);
    }

    protected function getActiveKeys(PaymentProfile $profile)
    {
        $isTestMode = !empty($profile->options['test_mode']);
        
        if ($isTestMode) {
            return [
                'secret_key' => $profile->options['test_secret_key'] ?? '',
                'public_key' => $profile->options['test_public_key'] ?? ''
            ];
        }
        
        return [
            'secret_key' => $profile->options['live_secret_key'] ?? '',
            'public_key' => $profile->options['live_public_key'] ?? ''
        ];
    }

    protected function initializeTransaction(PaymentProfile $profile, array $params)
    {
        $keys = $this->getActiveKeys($profile);
        $secretKey = $keys['secret_key'];
        $isTestMode = !empty($profile->options['test_mode']);
        
        // Validate key type matches mode
        if ($isTestMode && !$this->isTestKey($secretKey, 'sk')) {
            \XF::logError('Test mode is enabled but live secret key is configured');
            return null;
        }
        
        if (!$isTestMode && !$this->isLiveKey($secretKey, 'sk')) {
            \XF::logError('Live mode is enabled but test secret key is configured');
            return null;
        }
        
        $apiUrl = 'https://api.paystack.co/transaction/initialize';

        $client = \XF::app()->http()->client();

        try {
            $response = $client->post($apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => $params
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            return $body;
        } catch (\Exception $e) {
            \XF::logException($e);
            return null;
        }
    }

    public function setupCallback(Request $request)
    {
        $state = new CallbackState();
        
        // Check if this is a webhook request
        if ($request->filter('type', 'str') === 'webhook') {
            return $this->setupWebhookCallback($request, $state);
        }
        
        // Standard redirect callback
        // Get reference from query parameter or metadata
        $reference = $request->filter('reference', 'str');
        if (!$reference) {
            $reference = $request->filter('trxref', 'str');
        }

        $state->transactionId = $reference;
        
        // Extract request_key from reference
        // Reference format: XF_{request_key}_{timestamp}
        if ($reference && preg_match('/^XF_(.+)_(\d+)$/', $reference, $matches)) {
            $requestKey = $matches[1];
            $state->requestKey = $requestKey;
        }

        return $state;
    }

    // Paystack's official server IPs - only requests from these should be trusted
    protected $paystackIps = [
        '52.214.14.220',
        '52.49.173.169',
        '52.31.139.75'
    ];

    protected function setupWebhookCallback(Request $request, CallbackState $state)
    {
        // Check if this is a direct browser access (GET request)
        if ($request->getRequestMethod() === 'GET') {
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'status'   => 'success',
                'message'  => 'Paystack webhook endpoint is active and ready to receive POST requests from Paystack.',
                'provider' => 'Paystack Payment Gateway by Theophilus Adegbohungbe',
                'note'     => 'Configure this URL in your Paystack Dashboard under Settings > Webhooks.'
            ]);
            exit;
        }

        // Per Paystack docs: send 200 OK immediately to acknowledge receipt
        // This prevents Paystack from retrying due to slow processing
        http_response_code(200);

        // Read and store raw input ONCE - stream can only be read once
        $input = @file_get_contents('php://input');

        if (!$input) {
            $state->logType    = 'error';
            $state->logMessage = 'No webhook data received';
            return $state;
        }

        $event = json_decode($input, true);

        if (!$event) {
            $state->logType    = 'error';
            $state->logMessage = 'Invalid webhook JSON data';
            return $state;
        }

        // Store raw input alongside parsed event for signature verification later
        $state->_webhook = [
            'event'     => $event,
            'raw_input' => $input,
            'signature' => $request->getServer('HTTP_X_PAYSTACK_SIGNATURE'),
            'ip'        => $request->getIp()
        ];

        // Extract reference from webhook data
        if (isset($event['data']['reference'])) {
            $reference             = $event['data']['reference'];
            $state->transactionId  = $reference;

            // Reference format: XF_{request_key}_{timestamp}
            if (preg_match('/^XF_(.+)_(\d+)$/', $reference, $matches)) {
                $state->requestKey = $matches[1];
            }
        }

        return $state;
    }

    protected function verifyWebhookSignature($payload, $signature, $secretKey)
    {
        if (empty($signature)) {
            return false;
        }
        $hash = hash_hmac('sha512', $payload, $secretKey);
        return hash_equals($hash, $signature);
    }

    protected function isValidPaystackIp($ip)
    {
        return in_array($ip, $this->paystackIps, true);
    }

    public function validateCallback(CallbackState $state)
    {
        $request = \XF::app()->request();
        $purchaseRequest = $state->getPurchaseRequest();

        if (!$purchaseRequest) {
            $state->logType = 'error';
            $state->logMessage = 'Purchase request not found';
            return false;
        }

        $profile = $purchaseRequest->PaymentProfile;

        // Handle webhook validation
        if (isset($state->_webhook)) {
            return $this->validateWebhookCallback($state, $profile);
        }

        // Standard redirect validation
        $reference = $state->transactionId;

        // Verify transaction with Paystack API
        $verification = $this->verifyTransaction($profile, $reference);

        if (!$verification || !isset($verification['status']) || !$verification['status']) {
            $state->logType = 'error';
            $state->logMessage = 'Transaction verification failed';
            return false;
        }

        $data = $verification['data'];

        // Check transaction status
        if ($data['status'] !== 'success') {
            $state->logType = 'info';
            $state->logMessage = 'Payment not successful: ' . $data['status'];
            return false;
        }

        // Verify amount (convert from kobo back to main currency)
        $paidAmount = $data['amount'] / 100;
        $expectedAmount = $purchaseRequest->cost_amount;

        if (abs($paidAmount - $expectedAmount) > 0.01) {
            $state->logType = 'error';
            $state->logMessage = sprintf(
                'Amount mismatch. Expected: %s, Received: %s',
                $expectedAmount,
                $paidAmount
            );
            return false;
        }

        // Verify currency
        if (strtoupper($data['currency']) !== strtoupper($purchaseRequest->cost_currency)) {
            $state->logType = 'error';
            $state->logMessage = 'Currency mismatch';
            return false;
        }

        $state->paymentResult = CallbackState::PAYMENT_RECEIVED;
        $state->transactionId = $data['reference'];
        $state->cost          = $paidAmount;
        $state->currency      = strtoupper($data['currency']);

        return true;
    }

    protected function validateWebhookCallback(CallbackState $state, PaymentProfile $profile)
    {
        $webhook   = $state->_webhook;
        $event     = $webhook['event'];
        $signature = $webhook['signature'];
        $ip        = $webhook['ip'];

        // Optional IP whitelist check per Paystack docs
        // Only Paystack's official IPs should be sending webhook events
        if (!$this->isValidPaystackIp($ip)) {
            $state->logType    = 'error';
            $state->logMessage = 'Webhook received from unauthorized IP: ' . $ip;
            return false;
        }

        // Get active secret key based on mode and verify HMAC SHA512 signature
        // Use stored raw input - do NOT re-read php://input as stream is already consumed
        $keys      = $this->getActiveKeys($profile);
        $secretKey = $keys['secret_key'];
        $rawInput  = $webhook['raw_input'];

        if (!$this->verifyWebhookSignature($rawInput, $signature, $secretKey)) {
            $state->logType    = 'error';
            $state->logMessage = 'Invalid webhook signature - possible spoofed request';
            return false;
        }

        $eventType = $event['event'] ?? '';

        // Per Paystack docs: acknowledge all events with 200 (already sent in setupWebhookCallback)
        // but only process charge.success for payment fulfillment
        if ($eventType !== 'charge.success') {
            $state->logType    = 'info';
            $state->logMessage = 'Webhook acknowledged - event type not processed: ' . $eventType;
            // Return true so XenForo logs it without error, but paymentResult is not set so no fulfillment happens
            return true;
        }

        $data = $event['data'];

        // Check transaction status inside the event data
        if (($data['status'] ?? '') !== 'success') {
            $state->logType    = 'info';
            $state->logMessage = 'Charge event received but status is not success: ' . ($data['status'] ?? 'unknown');
            return true;
        }

        $purchaseRequest = $state->getPurchaseRequest();

        // Verify amount - Paystack sends amounts in kobo (smallest currency unit)
        $paidAmount     = $data['amount'] / 100;
        $expectedAmount = $purchaseRequest->cost_amount;

        if (abs($paidAmount - $expectedAmount) > 0.01) {
            $state->logType    = 'error';
            $state->logMessage = sprintf(
                'Amount mismatch. Expected: %s, Received: %s',
                $expectedAmount,
                $paidAmount
            );
            return false;
        }

        // Verify currency matches what was purchased
        if (strtoupper($data['currency']) !== strtoupper($purchaseRequest->cost_currency)) {
            $state->logType    = 'error';
            $state->logMessage = 'Currency mismatch. Expected: ' . $purchaseRequest->cost_currency . ', Received: ' . $data['currency'];
            return false;
        }

        $state->paymentResult = CallbackState::PAYMENT_RECEIVED;
        $state->transactionId = $data['reference'];
        $state->cost          = $paidAmount;
        $state->currency      = strtoupper($data['currency']);
        $state->logMessage    = 'Payment verified via Paystack webhook (charge.success)';

        return true;
    }

    protected function verifyTransaction(PaymentProfile $profile, $reference)
    {
        $keys = $this->getActiveKeys($profile);
        $secretKey = $keys['secret_key'];
        $apiUrl = 'https://api.paystack.co/transaction/verify/' . urlencode($reference);

        $client = \XF::app()->http()->client();

        try {
            $response = $client->get($apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secretKey
                ]
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            return $body;
        } catch (\Exception $e) {
            \XF::logException($e);
            return null;
        }
    }

    public function getPaymentResult(CallbackState $state)
    {
        switch ($state->paymentResult)
        {
            case CallbackState::PAYMENT_RECEIVED:
                $state->logType = 'payment';
                $state->logMessage = $state->logMessage ?: 'Payment received successfully';
                break;

            case CallbackState::PAYMENT_REVERSED:
                $state->logType = 'cancel';
                $state->logMessage = $state->logMessage ?: 'Payment reversed';
                break;

            default:
                $state->logType = 'info';
                $state->logMessage = $state->logMessage ?: 'No payment result';
                break;
        }
    }

    public function validateCost(CallbackState $state)
    {
        // Cost and currency are already validated in validateCallback/validateWebhookCallback
        // Calling verifyTransaction again here would be a duplicate API call - skip it
        return parent::validateCost($state);
    }

    public function prepareLogData(CallbackState $state)
    {
        // For webhook requests, log the webhook event payload
        if (isset($state->_webhook) && isset($state->_webhook['event'])) {
            $state->logDetails = $state->_webhook['event'];
        } else {
            // For redirect callbacks, log the query parameters
            $state->logDetails = \XF::app()->request()->getInputForLogs();
        }
    }

    public function supportsRecurring(PaymentProfile $paymentProfile, $unit, $amount, &$result = self::ERR_NO_RECURRING)
    {
        return false; // Paystack subscriptions can be added in future versions
    }
}
