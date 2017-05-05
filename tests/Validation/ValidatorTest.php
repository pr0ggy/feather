<?php

namespace Kase\Test\Validation;

use PHPUnit\Framework\TestCase;
use Kase\Validation\Validator;
use Kase\Test\TestUtils;

class ValidatorTest extends TestCase
{
    private function createPreAccountedForValidatorSUT(
        $onFailureMessage = 'some on-failure message',
        $customValidatorMap = []
    ) {
        $sut = new Validator($onFailureMessage, $customValidatorMap);
        $sut->markAccountedFor();
        return $sut;
    }

    /**
     * @test
     * @expectedException Kase\Validation\Validator
     */
    public function can_utilize_custom_validation_methods_passed_in_as_a_dictionary()
    {
        $customValidators = [
            'justFail' => function () {
                throw $this;
            }
        ];
        $sut = $this->createPreAccountedForValidatorSUT('some on-failure message', $customValidators);

        $sut->justFail();
    }

    /**
     * @test
     * @expectedException \BadMethodCallException
     */
    public function throws_BadMethodCallException_if_nonexistent_validation_method_called()
    {
        $customValidators = [
            'justFail' => function () {
                throw $this;
            }
        ];
        $sut = $this->createPreAccountedForValidatorSUT('some on-failure message', $customValidators);

        $sut->justFailThenDoSomethingElse();
    }

    /**
     * @test
     * @expectedException Kase\Validation\Validator
     */
    public function can_utilize_custom_validation_methods_even_if_the_fail_prefix_is_ommitted_from_the_method_name()
    {
        $customValidators = [
            'failInAllCases' => function () {
                throw $this;
            }
        ];
        $sut = $this->createPreAccountedForValidatorSUT('some on-failure message', $customValidators);

        $sut->inAllCases();
    }

    /**
     * @test
     */
    public function is_not_accounted_for_when_created()
    {
        $sut = new Validator('some on-failure message');
        $this->assertFalse($sut->hasBeenAccountedFor(), 'instance has not been tested against, but returned that it was');
        $sut->markAccountedFor(); // account for the validator so no error is thrown when the test method returns
    }

    /**
     * @test
     */
    public function can_be_marked_accounted_for()
    {
        $sut = new Validator('some on-failure message');
        $sut->markAccountedFor();
        $this->assertTrue($sut->hasBeenAccountedFor(), 'instance has been tested against, but returned that it was not');
    }

    /**
     * @test
     */
    public function passes_a_test_if_a_given_value_is_truthy()
    {
        $that = $this;
        $someOnFailureMessage = 'some on-failure message';
        $assertValidatorPassesWithValue = function ($value, $description) use ($that, $someOnFailureMessage) {
            try {
                $sut = new Validator('some on-failure message');
                $sut->failUnless($value);
            } catch (Validator $sut) {
                $that->fail("failed to pass a validation test when given value is truthy: {$description}");
            }
        };

        $assertValidatorPassesWithValue(true, 'true');
        $assertValidatorPassesWithValue(1, '1');
        $assertValidatorPassesWithValue('1', '"1"');
        $assertValidatorPassesWithValue(['foo'], 'non-empty array');
    }

    /**
     * @test
     */
    public function fails_a_test_if_a_given_value_is_not_truthy()
    {
        $that = $this;
        $someOnFailureMessage = 'some on-failure message';
        $assertValidatorFailsWithValue = function ($value, $description) use ($that, $someOnFailureMessage) {
            try {
                $sut = new Validator($someOnFailureMessage);
                $sut->failUnless($value);
                $that->fail("failed to throw validation exception when given non-truthy value: {$description}");
            } catch (Validator $sut) {
                $this->assertEquals($someOnFailureMessage, $sut->getMessage(),
                    'exception thrown as expected, but message was not as expected');
            }
        };

        $assertValidatorFailsWithValue(false, 'false');
        $assertValidatorFailsWithValue(0, '0');
        $assertValidatorFailsWithValue('0', '"0"');
        $assertValidatorFailsWithValue([], 'empty array');
    }

    /**
     * @test
     */
    public function fails_a_test_if_a_given_value_is_truthy()
    {
        $that = $this;
        $someOnFailureMessage = 'some on-failure message';
        $assertValidatorFailsWithValue = function ($value, $description) use ($that, $someOnFailureMessage) {
            try {
                $sut = new Validator($someOnFailureMessage);
                $sut->failIf($value);
                $that->fail("failed to throw validation exception when given truthy value: {$description}");
            } catch (Validator $sut) {
                $this->assertEquals($someOnFailureMessage, $sut->getMessage(),
                    'exception thrown as expected, but message was not as expected');
            }
        };

        $assertValidatorFailsWithValue(true, 'true');
        $assertValidatorFailsWithValue(1, '1');
        $assertValidatorFailsWithValue('1', '"1"');
        $assertValidatorFailsWithValue(['foo'], 'non-empty array');
    }

    /**
     * @test
     */
    public function passes_a_test_if_a_given_value_is_not_truthy()
    {
        $that = $this;
        $assertValidatorPassesWithValue = function ($value, $description) use ($that) {
            try {
                $sut = new Validator('some on-failure message');
                $sut->failIf($value);
            } catch (Validator $sut) {
                $that->fail("failed to pass a validation test when given value is not truthy: {$description}");
            }
        };

        $assertValidatorPassesWithValue(false, 'false');
        $assertValidatorPassesWithValue(0, '0');
        $assertValidatorPassesWithValue('0', '"0"');
        $assertValidatorPassesWithValue([], 'empty array');
    }


    /**
     * @test
     */
    public function fails_a_test_if_give_2_values_that_do_not_exhibit_loose_equality()
    {
        $expectedValidationFailureMessage = 'Validation failure message';
        $entityPairGenerator = new TestUtils\EqualityTestGenerator();

        foreach ($entityPairGenerator->generatedLooseEqualityFailurePairs() as list(list($entityA, $entityB), $entityTypeDescription)) {
            $sut = new Validator($expectedValidationFailureMessage);
            try {
                $sut->failIfNotEqual($entityA, $entityB, $expectedValidationFailureMessage);
                // if the above call doesn't throw, test has failed
                $this->fail("
                    No exception thrown when asserting that inequal pairs of type {$entityTypeDescription} are equal
                    \n\nOBJECT A:\n"
                    .var_export($entityA, true)
                    ."\n\nOBJECT B:\n"
                    .var_export($entityB, true)
                );
            } catch (Validator $sut) {
                $this->assertEquals(
                    $expectedValidationFailureMessage,
                    $sut->getMessage(),
                    'Validation exception thrown, but failure message was not as expected'
                );
            }
        }
    }

    /**
     * @test
     */
    public function passes_a_test_if_give_2_values_that_do_exhibit_loose_equality()
    {
        $entityPairGenerator = new TestUtils\EqualityTestGenerator();
        foreach ($entityPairGenerator->generatedLooseEqualitySuccessPairs() as list(list($entityA, $entityB), $entityTypeDescription)) {
            $sut = new Validator('some on-failure message');
            $sut->failIfNotEqual($entityA, $entityB, "
                Exception thrown when asserting that equal pairs of type {$entityTypeDescription} are equal
                \n\nOBJECT A:\n"
                .var_export($entityA, true)
                ."\n\nOBJECT B:\n"
                .var_export($entityB, true)
            );
        }
    }

    /**
     * @test
     */
    public function fails_a_test_if_give_2_values_that_do_not_exhibit_strict_equality()
    {
        $expectedValidationFailureMessage = 'Validation failure message';
        $entityPairGenerator = new TestUtils\EqualityTestGenerator();

        foreach ($entityPairGenerator->generatedStrictEqualityFailurePairs() as list(list($entityA, $entityB), $entityTypeDescription)) {
            $sut = new Validator($expectedValidationFailureMessage);
            try {
                $sut->failIfNotSame($entityA, $entityB, $expectedValidationFailureMessage);
                // if the above call doesn't throw, test has failed
                $this->fail("
                    No exception thrown when asserting that inequal pairs of type {$entityTypeDescription} are identical
                    \n\nOBJECT A:\n"
                    .var_export($entityA, true)
                    ."\n\nOBJECT B:\n"
                    .var_export($entityB, true)
                );
            } catch (Validator $sut) {
                $this->assertEquals(
                    $expectedValidationFailureMessage,
                    $sut->getMessage(),
                    'Validation exception thrown, but failure message was not as expected'
                );
            }
        }
    }

    /**
     * @test
     */
    public function passes_a_test_if_give_2_values_that_do_exhibit_strict_equality()
    {
        $entityPairGenerator = new TestUtils\EqualityTestGenerator();
        foreach ($entityPairGenerator->generatedStrictEqualitySuccessPairs() as list(list($entityA, $entityB), $entityTypeDescription)) {
            $sut = new Validator('some on-failure message');
            $sut->failIfNotSame($entityA, $entityB, "
                Exception thrown when asserting that identical pairs of type {$entityTypeDescription} are equal
                \n\nOBJECT A:\n"
                .var_export($entityA, true)
                ."\n\nOBJECT B:\n"
                .var_export($entityB, true)
            );
        }
    }

    /**
     * @test
     */
    public function fails_automatically_if_unset_before_being_accounted_for()
    {
        try {
            $sut = new Validator('some on-failure message');
            $expectedValidationFailureMessage = Validator::getUnaccountedForErrorMessageForValidator($sut);
            unset($sut); // should automatically fail/throw the Validator instance
            $this->fail('failed to automatically fail validation when Validator instance was unset without being accounted for');
        } catch (Validator $sut) {
            $this->assertEquals(
                $expectedValidationFailureMessage,
                $sut->getMessage(),
                'instance did fail automatically, but failure message was not as expected'
            );
        }
    }
}
