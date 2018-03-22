<?php

namespace Oro\Bundle\PayPalExpressBundle\Method\PaymentAction;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PayPalExpressBundle\Exception\ExceptionInterface;
use Oro\Bundle\PayPalExpressBundle\Method\Config\PayPalExpressConfigInterface;

class PurchaseAction extends AbstractPaymentAction
{
    /**
     * {@inheritdoc}
     */
    public function executeAction(PaymentTransaction $paymentTransaction, PayPalExpressConfigInterface $config)
    {
        /**
         * Should be the one of not success payment statuses to avoid incorrect status in payment entity
         * @see \Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider::getStatusByEntityAndTransactions
         */
        $paymentTransaction->setAction('create_payment');

        try {
            $route = $this->payPalTransportFacade->getPayPalPaymentRoute($paymentTransaction, $config);
            $paymentTransaction
                ->setSuccessful(true)
                ->setActive(true);

            return ['purchaseRedirectUrl' => $route];
        } catch (ExceptionInterface $e) {
            $paymentTransaction
                ->setSuccessful(false)
                ->setActive(false);

            return [];
        }
    }


    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return PaymentMethodInterface::PURCHASE;
    }
}
