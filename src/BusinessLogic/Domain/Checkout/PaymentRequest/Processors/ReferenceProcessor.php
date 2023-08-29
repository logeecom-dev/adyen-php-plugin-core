<?php

namespace Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Processors;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Factory\PaymentRequestBuilder;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;

/**
 * Class PaymentRequestStateDataProcessor
 *
 * @package Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Processors
 */
class ReferenceProcessor implements PaymentRequestProcessor
{
    public function process(PaymentRequestBuilder $builder, StartTransactionRequestContext $context): void
    {
        $builder->setReference($context->getReference());
    }
}
