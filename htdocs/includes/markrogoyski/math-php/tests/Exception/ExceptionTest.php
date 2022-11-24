<?php

namespace MathPHP\Tests\Exception;

use MathPHP\Exception;

class ExceptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test BadDataException
     */
    public function testBadDataException()
    {
        // Given
        $e = new Exception\BadDataException('message');

        // Then
        $this->assertInstanceOf(Exception\MathException::class, $e);
    }

    /**
     * @test BadParameterException
     */
    public function testBadParameterException()
    {
        // Given
        $e = new Exception\BadParameterException('message');

        // Then
        $this->assertInstanceOf(Exception\MathException::class, $e);
    }

    /**
     * @test DivisionByZeroException
     */
    public function testDivisionByZeroException()
    {
        // Given
        $e = new Exception\DivisionByZeroException('message');

        // Then
        $this->assertInstanceOf(Exception\MathException::class, $e);
    }

    /**
     * @test FunctionFailedToConvergeException
     */
    public function testFunctionFailedToConvergeException()
    {
        // Given
        $e = new Exception\FunctionFailedToConvergeException('message');

        // Then
        $this->assertInstanceOf(Exception\MathException::class, $e);
    }

    /**
     * @test IncorrectTypeException
     */
    public function testIncorrectTypeException()
    {
        // Given
        $e = new Exception\IncorrectTypeException('message');

        // Then
        $this->assertInstanceOf(Exception\MathException::class, $e);
    }

    /**
     * @test MathException
     */
    public function testMathException()
    {
        // Given
        $e = new Exception\MathException('message');

        // Then
        $this->assertInstanceOf(\Exception::class, $e);
    }

    /**
     * @test MatrixException
     */
    public function testMatrixException()
    {
        // Given
        $e = new Exception\MatrixException('message');

        // Then
        $this->assertInstanceOf(Exception\MathException::class, $e);
    }

    /**
     * @test NanException
     */
    public function testNanException()
    {
        // Given
        $e = new Exception\NanException('message');

        // Then
        $this->assertInstanceOf(Exception\MathException::class, $e);
    }

    /**
     * @test OutOfBoundsException
     */
    public function testOutOfBoundsException()
    {
        // Given
        $e = new Exception\OutOfBoundsException('message');

        // Then
        $this->assertInstanceOf(Exception\MathException::class, $e);
    }

    /**
     * @test SingularMatrixException
     */
    public function testSingularMatrixException()
    {
        // Given
        $e = new Exception\SingularMatrixException('message');

        // Then
        $this->assertInstanceOf(Exception\MatrixException::class, $e);
        $this->assertInstanceOf(Exception\MathException::class, $e);
    }

    /**
     * @test VectorException
     */
    public function testVectorException()
    {
        // Given
        $e = new Exception\VectorException('message');

        // Then
        $this->assertInstanceOf(Exception\MathException::class, $e);
    }
}
