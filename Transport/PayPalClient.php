<?php

namespace Oro\Bundle\PayPalExpressBundle\Transport;

use PayPal\Api\Authorization;
use PayPal\Api\Capture;
use PayPal\Api\Order;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Core\PayPalHttpConfig;
use PayPal\Rest\ApiContext;

class PayPalClient
{
    /**
     * Apply workaround for issue with invalid ssl constant in pay_pal sdk
     */
    public function __construct()
    {
        PayPalHttpConfig::$defaultCurlOptions[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
    }

    /**
     * @param string     $paymentId
     * @param ApiContext $apiContext
     *
     * @return Payment
     */
    public function getPaymentById($paymentId, ApiContext $apiContext)
    {
        return Payment::get($paymentId, $apiContext);
    }

    /**
     * @param Payment    $payment
     * @param ApiContext $apiContext
     *
     * @return Payment
     */
    public function createPayment(Payment $payment, ApiContext $apiContext)
    {
        return $payment->create($apiContext);
    }

    /**
     * @param Payment          $payment
     * @param PaymentExecution $execution
     * @param ApiContext       $apiContext
     *
     * @return Payment
     */
    public function executePayment(Payment $payment, PaymentExecution $execution, ApiContext $apiContext)
    {
        return $payment->execute($execution, $apiContext);
    }

    /**
     * @param Order         $order
     * @param Authorization $authorization
     * @param ApiContext    $apiContext
     *
     * @return Authorization
     */
    public function authorizeOrder(Order $order, Authorization $authorization, ApiContext $apiContext)
    {
        return $order->authorize($authorization, $apiContext);
    }

    /**
     * @param Order      $order
     * @param Capture    $captureDetails
     * @param ApiContext $apiContext
     *
     * @return Capture
     */
    public function captureOrder(Order $order, Capture $captureDetails, ApiContext $apiContext)
    {
        return $order->capture($captureDetails, $apiContext);
    }

    /**
     * @param string     $orderId
     * @param ApiContext $apiContext
     *
     * @return Order
     */
    public function getOrderById($orderId, ApiContext $apiContext)
    {
        return Order::get($orderId, $apiContext);
    }
}
