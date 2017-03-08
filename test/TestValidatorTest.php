<?php

namespace Feather;

use PHPUnit\Framework\TestCase;

class TestValidatorTest extends TestCase
{
    const NON_TRUTHY_VALUES = [false, 0, '', '0'];
    const TRUTHY_VALUES = [true, 'true', 1, '1'];

    /**
     * @test
     * @expectedException \Feather\ValidationFailureException
     * @expectedExceptionMessage Validation failure message
     */
    public function fail_throwsValidationFailureExceptionWithGivenMessage()
    {
        $sut = new TestValidator();
        $expectedValidationFailureMessage = 'Validation failure message';

        $sut->fail($expectedValidationFailureMessage);
    }

    /**
     * @test
     */
    public function assert_throwsValidationFailureExceptionWithGivenMessage_whenGivenValueIsNotTruthy()
    {
        $sut = new TestValidator();
        $expectedValidationFailureMessage = 'Validation failure message';

        foreach (self::NON_TRUTHY_VALUES as $nonTruthyValue) {
            try {
                $sut->assert($nonTruthyValue, $expectedValidationFailureMessage);
                // if the above call doesn't throw, test has failed
                $this->fail('No exception thrown when asserting a non-truthy value');
            } catch (ValidationFailureException $exception) {
                $this->assertEquals(
                    $expectedValidationFailureMessage,
                    $exception->getMessage(),
                    'Validation exception thrown, but failure message was not as expected'
                );
            }
        }
    }

    /**
     * @test
     */
    public function assert_doesNotThrow_whenGivenValueIsTruthy()
    {
        $sut = new TestValidator();

        foreach (self::TRUTHY_VALUES as $truthyValue) {
            $sut->assert($truthyValue);
        }
    }

    /**
     * @test
     */
    public function assertEqual_throwsValidationFailureExceptionWithGivenMessage_whenGivenEntitiesDoNotExhibitLooseEquality()
    {
        $sut = new TestValidator();
        $expectedValidationFailureMessage = 'Validation failure message';
        $entityPairGenerator = new Test\Utils\EqualityTestGenerator();

        foreach ($entityPairGenerator->generatedLooseEqualityFailurePairs() as list(list($entityA, $entityB), $entityTypeDescription)) {
            try {
                $sut->assertEqual($entityA, $entityB, $expectedValidationFailureMessage);
                // if the above call doesn't throw, test has failed
                $this->fail("
                    No exception thrown when asserting that inequal pairs of type {$entityTypeDescription} are equal
                    \n\nOBJECT A:\n"
                    .var_export($entityA, true)
                    ."\n\nOBJECT B:\n"
                    .var_export($entityB, true)
                );
            } catch (ValidationFailureException $exception) {
                $this->assertEquals(
                    $expectedValidationFailureMessage,
                    $exception->getMessage(),
                    'Validation exception thrown, but failure message was not as expected'
                );
            }
        }
    }

    /**
     * @test
     */
    public function assertEqual_doesNotThrow_whenGivenEntitiesExhibitEquality()
    {
        $sut = new TestValidator();
        $entityPairGenerator = new Test\Utils\EqualityTestGenerator();

        foreach ($entityPairGenerator->generatedLooseEqualitySuccessPairs() as list(list($entityA, $entityB), $entityTypeDescription)) {
            $sut->assertEqual($entityA, $entityB, "
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
    public function assertSame_throwsValidationFailureExceptionWithGivenMessage_whenGivenEntitiesDoNotExhibitIdentityEquality()
    {
        $sut = new TestValidator();
        $expectedValidationFailureMessage = 'Validation failure message';
        $entityPairGenerator = new Test\Utils\EqualityTestGenerator();

        foreach ($entityPairGenerator->generatedStrictEqualityFailurePairs() as list(list($entityA, $entityB), $entityTypeDescription)) {
            try {
                $sut->assertSame($entityA, $entityB, $expectedValidationFailureMessage);
                // if the above call doesn't throw, test has failed
                $this->fail("
                    No exception thrown when asserting that inequal pairs of type {$entityTypeDescription} are identical
                    \n\nOBJECT A:\n"
                    .var_export($entityA, true)
                    ."\n\nOBJECT B:\n"
                    .var_export($entityB, true)
                );
            } catch (ValidationFailureException $exception) {
                $this->assertEquals(
                    $expectedValidationFailureMessage,
                    $exception->getMessage(),
                    'Validation exception thrown, but failure message was not as expected'
                );
            }
        }
    }

    /**
     * @test
     */
    public function assertSame_doesNotThrow_whenGivenEntitiesExhibitIdentityEquality()
    {
        $sut = new TestValidator();
        $entityPairGenerator = new Test\Utils\EqualityTestGenerator();

        foreach ($entityPairGenerator->generatedStrictEqualitySuccessPairs() as list(list($entityA, $entityB), $entityTypeDescription)) {
            $sut->assertEqual($entityA, $entityB, "
                Exception thrown when asserting that identical pairs of type {$entityTypeDescription} are equal
                \n\nOBJECT A:\n"
                .var_export($entityA, true)
                ."\n\nOBJECT B:\n"
                .var_export($entityB, true)
            );
        }
    }
}
