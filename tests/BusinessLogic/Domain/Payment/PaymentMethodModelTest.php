<?php

namespace Adyen\Core\Tests\BusinessLogic\Domain\Payment;

use Adyen\Core\BusinessLogic\Domain\Payment\Models\Exceptions\InvalidTokenTypeException;
use Adyen\Core\BusinessLogic\Domain\Payment\Models\PaymentMethod;
use Adyen\Core\BusinessLogic\Domain\Payment\Models\TokenType;
use Adyen\Core\Tests\BusinessLogic\Common\BaseTestCase;

/**
 * Class PaymentMethodModelTest
 *
 * @package Adyen\Core\Tests\BusinessLogic\Domain\Payment
 */
class PaymentMethodModelTest extends BaseTestCase
{
    public function testTotalSurchargeCalculationForNoneType()
    {
        $paymentMethod = new PaymentMethod('test123', 'scheme', 'Test');
        $paymentMethod->setSurchargeType('none');

        self::assertEquals(0, $paymentMethod->getTotalSurchargeFor(123.45));
    }

    public function testTotalSurchargeCalculationForCombinedType()
    {
        $paymentMethod = new PaymentMethod('test123', 'scheme', 'Test');
        $paymentMethod->setSurchargeType('combined');
        $paymentMethod->setFixedSurcharge(100);
        $paymentMethod->setPercentSurcharge(10);

        self::assertEquals(110, $paymentMethod->getTotalSurchargeFor(100));
    }

    public function testTotalSurchargeCalculationLimit()
    {
        $paymentMethod = new PaymentMethod('test123', 'scheme', 'Test');
        $paymentMethod->setSurchargeType('combined');
        $paymentMethod->setFixedSurcharge(100);
        $paymentMethod->setPercentSurcharge(10);
        $paymentMethod->setSurchargeLimit(5);

        self::assertEquals(105, $paymentMethod->getTotalSurchargeFor(100));
    }
    public function testTokenTypeException()
    {
        // arrange
        $this->expectException(InvalidTokenTypeException::class);
        new PaymentMethod(
            'test123',
            'scheme',
            'Test',
            '',
            '',
            [],
            [],
            '',
            '',
            '',
            '',
            null,
            '',
        '',
            null,
        false,
            true,
            TokenType::fromState('test')
        );
    }
}
