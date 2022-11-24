<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;

class MatrixFactoryErrorPropagationTest extends \PHPUnit\Framework\TestCase
{
    /** @var NumericMatrix */
    private $A;

    private const ε = 0.01;

    public function setUp(): void
    {
        $this->A = MatrixFactory::createNumeric([
            [1, 2],
            [3, 4],
        ]);
        $this->A->setError(self::ε);
    }

    /* **************************** *
     * MatrixFactory::createNumeric
     * **************************** */

    /**
     * @test add propagates error tolerance to resultant matrix
     */
    public function testAdd()
    {
        // Given
        $B = MatrixFactory::createNumeric([
            [3, 4],
            [5, 6]
        ]);

        // When
        $R = $this->A->add($B);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test directSum propagates error tolerance to resultant matrix
     */
    public function testDirectSum()
    {
        // Given
        $B = MatrixFactory::createNumeric([
            [3, 4],
            [5, 6]
        ]);

        // When
        $R = $this->A->directSum($B);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test subtract propagates error tolerance to resultant matrix
     */
    public function testSubtract()
    {
        // Given
        $B = MatrixFactory::createNumeric([
            [3, 4],
            [5, 6]
        ]);

        // When
        $R = $this->A->subtract($B);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test multiply propagates error tolerance to resultant matrix
     */
    public function testMultiply()
    {
        // Given
        $B = MatrixFactory::createNumeric([
            [3, 4],
            [5, 6]
        ]);

        // When
        $R = $this->A->multiply($B);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test scalarMultiply propagates error tolerance to resultant matrix
     */
    public function testScalarMultiply()
    {
        // Given
        $x = 4;

        // When
        $R = $this->A->scalarMultiply($x);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test scalarDivide propagates error tolerance to resultant matrix
     */
    public function testScalarDivide()
    {
        // Given
        $x = 4;

        // When
        $R = $this->A->scalarDivide($x);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test hadamardProduct propagates error tolerance to resultant matrix
     */
    public function testHadamardProduct()
    {
        // Given
        $B = MatrixFactory::createNumeric([
            [3, 4],
            [5, 6]
        ]);

        // When
        $R = $this->A->hadamardProduct($B);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test diagonal propagates error tolerance to resultant matrix
     */
    public function testDiagonal()
    {
        // When
        $R = $this->A->diagonal();

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test inverse propagates error tolerance to resultant matrix
     */
    public function testInverse()
    {
        // When
        $R = $this->A->inverse();

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test cofactorMatrix propagates error tolerance to resultant matrix
     */
    public function testCofactorMatrix()
    {
        // When
        $R = $this->A->cofactorMatrix();

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test rowMultiply propagates error tolerance to resultant matrix
     */
    public function testRowMultiply()
    {
        // Given
        $m = 1;
        $k = 2;

        // When
        $R = $this->A->rowMultiply($m, $k);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test rowDivide propagates error tolerance to resultant matrix
     */
    public function testRowDivide()
    {
        // Given
        $m = 1;
        $k = 2;

        // When
        $R = $this->A->rowDivide($m, $k);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test rowAdd propagates error tolerance to resultant matrix
     */
    public function testRowAdd()
    {
        // Given
        $i = 1;
        $j = 1;
        $k = 2;

        // When
        $R = $this->A->rowAdd($i, $j, $k);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test rowAddScalar propagates error tolerance to resultant matrix
     */
    public function testRowAddScalar()
    {
        // Given
        $m = 1;
        $k = 2;

        // When
        $R = $this->A->rowAddScalar($m, $k);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }


    /**
     * @test rowSubtract propagates error tolerance to resultant matrix
     */
    public function testRowSubtract()
    {
        // Given
        $i = 1;
        $j = 1;
        $k = 2;

        // When
        $R = $this->A->rowSubtract($i, $j, $k);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test rowSubtractScalar propagates error tolerance to resultant matrix
     */
    public function testRowSubtractScalar()
    {
        // Given
        $m = 1;
        $k = 2;

        // When
        $R = $this->A->rowSubtractScalar($m, $k);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test columnAdd propagates error tolerance to resultant matrix
     */
    public function testColumnAdd()
    {
        // Given
        $i = 1;
        $j = 1;
        $k = 2;

        // When
        $R = $this->A->columnAdd($i, $j, $k);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }


    /**
     * @test columnMultiply propagates error tolerance to resultant matrix
     */
    public function testColumnMultiply()
    {
        // Given
        $n = 1;
        $k = 2;

        // When
        $R = $this->A->columnMultiply($n, $k);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test adjugate propagates error tolerance to resultant matrix
     */
    public function testAdjugate()
    {
        // When
        $R = $this->A->adjugate();

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /* ********************* *
     * MatrixFactory::create
     * ********************* */

    /**
     * @test augment propagates error tolerance to resultant matrix
     */
    public function testAugment()
    {
        // Given
        $B = MatrixFactory::createNumeric([
            [3, 4],
            [5, 6]
        ]);

        // When
        $R = $this->A->augment($B);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test augmentLeft propagates error tolerance to resultant matrix
     */
    public function testAugmentLeft()
    {
        // Given
        $B = MatrixFactory::createNumeric([
            [3, 4],
            [5, 6]
        ]);

        // When
        $R = $this->A->augmentLeft($B);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test augmentBelow propagates error tolerance to resultant matrix
     */
    public function testAugmentBelow()
    {
        // Given
        $B = MatrixFactory::createNumeric([
            [3, 4],
            [5, 6]
        ]);

        // When
        $R = $this->A->augmentBelow($B);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test augmentAbove propagates error tolerance to resultant matrix
     */
    public function testAugmentAbove()
    {
        // Given
        $B = MatrixFactory::createNumeric([
            [3, 4],
            [5, 6]
        ]);

        // When
        $R = $this->A->augmentAbove($B);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }


    /**
     * @test transpose propagates error tolerance to resultant matrix
     */
    public function testTranspose()
    {
        // When
        $R = $this->A->transpose();

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test submatrix propagates error tolerance to resultant matrix
     */
    public function testSubmatrix()
    {
        // Given
        $B = MatrixFactory::createNumeric([
            [3],
        ]);
        $m = 1;
        $n = 1;

        // When
        $R = $this->A->insert($B, $m, $n);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test map propagates error tolerance to resultant matrix
     */
    public function testMap()
    {
        // Given
        $f = function ($x) {
            return $x + 1;
        };

        // When
        $R = $this->A->map($f);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test rowInterchange propagates error tolerance to resultant matrix
     */
    public function testRowInterchange()
    {
        // Given
        $i = 0;
        $j = 1;

        // When
        $R = $this->A->rowInterchange($i, $j);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test rowExclude propagates error tolerance to resultant matrix
     */
    public function testRowExclude()
    {
        // Given
        $i = 0;

        // When
        $R = $this->A->rowExclude($i);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test columnInterchange propagates error tolerance to resultant matrix
     */
    public function testcolumnInterchange()
    {
        // Given
        $i = 0;
        $j = 1;

        // When
        $R = $this->A->columnInterchange($i, $j);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test columnExclude propagates error tolerance to resultant matrix
     */
    public function testColumnExclude()
    {
        // Given
        $i = 0;

        // When
        $R = $this->A->columnExclude($i);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test leadingPrincipalMinor propagates error tolerance to resultant matrix
     */
    public function testLeadingPrincipalMinor()
    {
        // Given
        $k = 1;

        // When
        $R = $this->A->leadingPrincipalMinor($k);

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /* ******************************** *
     * MatrixFactory::createFromVectors
     * ******************************** */

    /**
     * @test meanDeviationOfRowVariables propagates error tolerance to resultant matrix
     */
    public function testMeanDeviationOfRowVariables()
    {
        // When
        $R = $this->A->meanDeviationOfRowVariables();

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }

    /**
     * @test meanDeviationOfColumnVariables propagates error tolerance to resultant matrix
     */
    public function testMeanDeviationOfColumnVariables()
    {
        // When
        $R = $this->A->meanDeviationOfColumnVariables();

        // Then
        $this->assertEquals(self::ε, $R->getError());
    }
}
