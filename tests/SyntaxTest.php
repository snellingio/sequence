<?php

class SyntaxTest extends PHPUnit_Framework_TestCase
{

    public function testIsThereAnySyntaxError()
    {
        $var = new Snelling\Sequence\Sequence();
        $this->assertTrue(is_object($var));
        unset($var);

        $var = new Snelling\Sequence\Payload();
        $this->assertTrue(is_object($var));
        unset($var);
    }

}