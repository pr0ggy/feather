<?php

namespace Kase\Test\Utils;

use stdClass;

class EqualityTestGenerator
{
    const TYPES = [
        'Boolean',
        'Int',
        'String',
        'Array',
        'Object',
        'Heterogeneous'
    ];

    public function generatedLooseEqualityFailurePairs()
    {
        foreach (self::TYPES as $type) {
            $looseEqualityFailureGenerator = [$this, "generateUnequal{$type}Pairs"];
            foreach ($looseEqualityFailureGenerator() as $pair) {
                yield [$pair, $type];
            }
        }
    }

    protected function generateUnequalBooleanPairs() { yield [ true, false ]; }
    protected function generateUnequalIntPairs() { yield [ 4, -4 ]; }
    protected function generateUnequalStringPairs() { yield [ '', 'test' ]; }
    protected function generateUnequalArrayPairs() { yield [ [], ['test'] ]; }

    protected function generateUnequalHeterogeneousPairs()
    {
        yield [ [], 'test' ];
        yield [ 1, 'test' ];
        yield [ false, 'a' ];

        $obj1 = new stdClass();
        $obj1->var1 = false;
        yield [ $obj1, '234' ];
    }

    protected function generateUnequalObjectPairs()
    {
        $obj1 = new stdClass();
        $obj1->var1 = false;
        $obj2 = new stdClass();
        $obj2->var1 = null;
        yield [ $obj1, $obj2 ];

        $obj1 = new stdClass();
        $obj1->var1 = 0;
        $obj2 = new stdClass();
        $obj2->var1 = '0';
        yield [ $obj1, $obj2 ];

        $obj1 = new stdClass();
        $obj1->var1 = true;
        $obj2 = new stdClass();
        $obj2->var1 = 1;
        yield [ $obj1, $obj2 ];

        $obj1 = new stdClass();
        $obj1->var1 = 1;
        $obj2 = new stdClass();
        $obj2->var1 = '1';
        yield [ $obj1, $obj2 ];
    }

    public function generatedLooseEqualitySuccessPairs()
    {
        foreach (self::TYPES as $type) {
            $looseEqualityGenerator = [$this, "generateEqual{$type}Pairs"];
            $identityEqualityGenerator = [$this, "generateSame{$type}Pairs"];
            foreach ([$looseEqualityGenerator, $identityEqualityGenerator] as $generator) {
                if (is_callable($generator) === false) {
                    continue;
                }

                foreach ($generator() as $pair) {
                    yield [$pair, $type];
                }
            }
        }
    }

    protected function generateSameBooleanPairs() { yield [ true, true ]; }
    protected function generateSameIntPairs() { yield [ 4, 4 ]; }
    protected function generateSameStringPairs() { yield [ 'test', 'test' ]; }
    protected function generateSameArrayPairs() { yield [ ['test'], ['test'] ]; }

    protected function generateEqualHeterogeneousPairs()
    {
        yield [ [], false ];
        yield [ 0, false ];
        yield [ '0', false ];
        yield [ ['test'], true ];
        yield [ true, 1 ];
        yield [ 'true', true ];
    }

    protected function generateEqualObjectPairs()
    {
        $obj1 = new stdClass();
        $obj1->var1 = false;
        $obj2 = new stdClass();
        $obj2->var1 = false;
        yield [ $obj1, $obj2 ];

        $obj1 = new stdClass();
        $obj1->var1 = 0;
        $obj2 = new stdClass();
        $obj2->var1 = 0;
        yield [ $obj1, $obj2 ];

        $obj1 = new stdClass();
        $obj1->var1 = ['test'];
        $obj2 = new stdClass();
        $obj2->var1 = ['test'];
        yield [ $obj1, $obj2 ];

        $obj1 = new stdClass();
        $obj2 = new stdClass();
        yield [ $obj1, $obj2 ];
    }

    public function generatedStrictEqualityFailurePairs()
    {
        foreach (self::TYPES as $type) {
            $looseEqualityFailureGenerator = [$this, "generateUnequal{$type}Pairs"];
            if (is_callable($looseEqualityFailureGenerator) === false) {
                continue;
            }

            foreach ($looseEqualityFailureGenerator() as $pair) {
                yield [$pair, $type];
            }
        }

        foreach ($this->generateEqualObjectPairs() as $pair) {
            yield [$pair, 'Object'];
        }
    }

    public function generatedStrictEqualitySuccessPairs()
    {
        foreach (self::TYPES as $type) {
            $identityEqualityGenerator = [$this, "generateSame{$type}Pairs"];
            if (is_callable($identityEqualityGenerator) === false) {
                continue;
            }

            foreach ($identityEqualityGenerator() as $pair) {
                yield [$pair, $type];
            }
        }
    }

    protected function generateSameObjectPairs()
    {
        $obj1 = new stdClass();
        yield [ $obj1, $obj1 ];

        $obj1 = new stdClass();
        $obj1->var1 = ['test'];
        yield [ $obj1, $obj1 ];
    }
}
