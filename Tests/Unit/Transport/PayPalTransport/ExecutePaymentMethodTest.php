<?php

namespace Oro\Bundle\PayPalExpressBundle\Tests\Unit\Transport\PayPalTransport;

use Oro\Bundle\PayPalExpressBundle\Transport\DTO\RedirectRoutesInfo;
use Oro\Bundle\PayPalExpressBundle\Transport\PayPalTransport;
use PayPal\Api\PaymentExecution;

class ExecutePaymentMethodTest extends AbstractTransportTestCase
{
    /**
     * @var string
     */
    protected $expectedPaymentId = '2xBU5pnHF6qNArI7Nt5yNqy4EgGWAU3K1w0eN6q77GZhNtu5cotSRWwZ';

    /**
     * @var RedirectRoutesInfo
     */
    protected $redirectRoutesInfo;

    protected function setUp()
    {
        parent::setUp();
        $this->paymentInfo = $this->createPaymentInfo($this->expectedPaymentId);
    }

    public function testCanExecutePaymentAndUpdatePaymentInfo()
    {
        $expectedOrderId = '123';

        $this->expectTranslatorGetApiContext();

        $execution = new PaymentExecution();
        $this->translator
            ->expects($this->once())
            ->method('getPaymentExecution')
            ->with($this->paymentInfo)
            ->willReturn($execution);

        $order = $this->createOrder($expectedOrderId);
        $payment = $this->createPayment($this->expectedPaymentId);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($this->expectedPaymentId, $this->apiContext)
            ->willReturn($payment);

        $executedPayment = $this->createPaymentWithOrder(
            $order,
            $this->expectedPaymentId,
            PayPalTransport::PAYMENT_EXECUTED_STATUS
        );

        $this->client->expects($this->once())
            ->method('executePayment')
            ->with($payment, $execution, $this->apiContext)
            ->willReturn($executedPayment);

        $this->transport->executePayment($this->paymentInfo, $this->apiContextInfo);

        $this->assertEquals($expectedOrderId, $this->paymentInfo->getOrderId());
    }

    public function testCanThrowExceptionWhenPaymentHasNoOrder()
    {
        $expectedPaymentState = 'failed';
        $expectedFailureReason = 'Payment failed because of some error';

        $this->expectTranslatorGetApiContext();

        $execution = new PaymentExecution();
        $this->translator
            ->expects($this->once())
            ->method('getPaymentExecution')
            ->with($this->paymentInfo)
            ->willReturn($execution);

        $payment = $this->createPayment($this->expectedPaymentId);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($this->expectedPaymentId, $this->apiContext)
            ->willReturn($payment);

        $executedPayment = $this->createPaymentWithOrder(
            null,
            $this->expectedPaymentId,
            $expectedPaymentState,
            $expectedFailureReason
        );

        $this->client->expects($this->once())
            ->method('executePayment')
            ->with($payment, $execution, $this->apiContext)
            ->willReturn($executedPayment);

        $this->expectTransportException(
            'Order was not created for payment after execute.',
            [
                'payment_id'             => $this->expectedPaymentId,
                'payment_state'          => $expectedPaymentState,
                'payment_failure_reason' => $expectedFailureReason,
            ],
            null
        );

        $this->transport->executePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testCanThrowExceptionWhenClientGetPaymentByIdFails()
    {
        $clientException = new \Exception();

        $this->expectTranslatorGetApiContext();

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($this->expectedPaymentId, $this->apiContext)
            ->willThrowException($clientException);

        $this->expectTransportException(
            'Execute payment failed.',
            [
                'payment_id' => $this->expectedPaymentId,
            ],
            $clientException
        );

        $this->transport->executePayment($this->paymentInfo, $this->apiContextInfo);
    }

    public function testCanThrowExceptionWhenClientExecutePaymentFails()
    {
        $clientException = new \Exception();
        $expectedPaymentState = PayPalTransport::PAYMENT_CREATED_STATUS;
        $expectedFailureReason = null;

        $this->expectTranslatorGetApiContext();

        $execution = new PaymentExecution();
        $this->translator
            ->expects($this->once())
            ->method('getPaymentExecution')
            ->with($this->paymentInfo)
            ->willReturn($execution);

        $payment = $this->createPayment($this->expectedPaymentId, $expectedPaymentState, $expectedFailureReason);

        $this->client->expects($this->once())
            ->method('getPaymentById')
            ->with($this->expectedPaymentId, $this->apiContext)
            ->willReturn($payment);

        $this->client->expects($this->once())
            ->method('executePayment')
            ->with($payment, $execution, $this->apiContext)
            ->willThrowException($clientException);

        $this->expectTransportException(
            'Execute payment failed.',
            [
                'payment_id'    => $this->expectedPaymentId,
                'payment_state' => $expectedPaymentState,
            ],
            $clientException
        );

        $this->transport->executePayment($this->paymentInfo, $this->apiContextInfo);
    }
}
