<?php

namespace Kase\Test\Validation;

use PHPUnit\Framework\TestCase;
use Kase\Validation\ValidatorFactory;
use Kase\Validation\Validator;
use Kase\Test\TestUtils;

class ValidatorFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function failBecause_returnsAValidatorInstanceWithTheGivenMessage()
    {
        $sut = new ValidatorFactory();
        $someFailureExplanation = 'this is a reason something failed';

        $failure = $sut->failBecause($someFailureExplanation);
        $failure->markAccountedFor(); // deactivate the failure's destructor-time check

        $this->assertInstanceOf(Validator::class, $failure);
        $this->assertEquals($someFailureExplanation, $failure->getMessage());
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Validation failure message
     */
    public function fail_throwsValidationFailureWithGivenMessage()
    {
        $sut = new ValidatorFactory();
        $expectedValidationFailureMessage = 'Validation failure message';

        $sut->fail($expectedValidationFailureMessage);
    }
}
