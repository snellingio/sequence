<?php

class SequenceTest extends PHPUnit\Framework\TestCase
{

    public function testNullReturnPayload()
    {
        $payload         = new Snelling\Sequence\Payload();
        $payload->number = 1;

        $pipeline = (new Snelling\Sequence\Sequence)
            ->then(function ($payload) {
                $payload->number++;

                return $payload;
            })
            // This should not change the payload, as there is no return statement
            ->then(function ($payload) {
                $payload->number++;
            })
            ->then(function ($number) {
                return $number++;
            }, 'number')
            // This should not change the payload, as there is no return statement
            ->then(function ($number) {
                $number++;
            }, 'number');


        $resultPayload = $pipeline->run($payload);

        $this->assertEquals($resultPayload->number, 3);
    }

}