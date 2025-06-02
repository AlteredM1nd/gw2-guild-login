<?php
use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{
    public function test_math_works(): void
    {
        $this->assertEquals(4, 2 + 2, 'Basic math should work');
    }
}
