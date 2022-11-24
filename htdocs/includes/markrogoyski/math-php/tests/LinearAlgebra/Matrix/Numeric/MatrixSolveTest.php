<?php

namespace MathPHP\Tests\LinearAlgebra\Matrix\Numeric;

use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\Vector;
use MathPHP\Exception;

class MatrixSolveTest extends \PHPUnit\Framework\TestCase
{
    use \MathPHP\Tests\LinearAlgebra\Fixture\MatrixDataProvider;

    /**
     * @test         Solve array
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSolveArray(array $A, array $b, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $expected = new Vector($expected);

        // When
        $x = $A->solve($b);

        // Then
        $this->assertEqualsWithDelta($expected, $x, 0.00001);
    }

    /**
     * @test         Solve vector
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSolveVector(array $A, array $b, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $b        = new Vector($b);
        $expected = new Vector($expected);

        // When
        $x = $A->solve($b);

        // Then
        $this->assertEqualsWithDelta($expected, $x, 0.00001);
    }

    /**
     * @test         Compute the inverse before trying to solve
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSolveInverse(array $A, array $b, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $b        = new Vector($b);
        $expected = new Vector($expected);

        // When
        $A->inverse();
        $x = $A->solve($b);

        // Then
        $this->assertEqualsWithDelta($expected, $x, 0.00001);
    }

    /**
     * @test         Compute the RREF before trying to solve.
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSolveRref(array $A, array $b, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $b        = new Vector($b);
        $expected = new Vector($expected);

        // When
        $A->rref();
        $x = $A->solve($b);

        // Then
        $this->assertEqualsWithDelta($expected, $x, 0.00001);
    }

    /**
     * @test         Solve forcing LU method
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSolveForcingLuMethod(array $A, array $b, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $b        = new Vector($b);
        $expected = new Vector($expected);

        // When
        $x = $A->solve($b, NumericMatrix::LU);

        // Then
        $this->assertEqualsWithDelta($expected, $x, 0.00001);
    }

    /**
     * @test         Solve forcing QR method
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSolveForcingQrMethod(array $A, array $b, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $b        = new Vector($b);
        $expected = new Vector($expected);

        // When
        $x = $A->solve($b, NumericMatrix::QR);

        // Then
        $this->assertEqualsWithDelta($expected, $x, 0.00001);
    }

    /**
     * @test         Solve forcing Inverse method
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSolveForcingInverseMethod(array $A, array $b, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $b        = new Vector($b);
        $expected = new Vector($expected);

        // When
        $x = $A->solve($b, NumericMatrix::INVERSE);

        // Then
        $this->assertEqualsWithDelta($expected, $x, 0.00001);
    }

    /**
     * @test         Solve forcing RREF method
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @param        array $expected
     * @throws       \Exception
     */
    public function testSolveForcingRrefMethod(array $A, array $b, array $expected)
    {
        // Given
        $A        = MatrixFactory::create($A);
        $b        = new Vector($b);
        $expected = new Vector($expected);

        // When
        $x = $A->solve($b, NumericMatrix::RREF);

        // Then
        $this->assertEqualsWithDelta($expected, $x, 0.00001);
    }

    /**
     * @test         solve exception
     * @dataProvider dataProviderForSolveExceptionNotVectorOrArray
     * @throws       \Exception
     */
    public function testSolveExceptionNotVectorOrArray($b)
    {
        // Given
        $A = new NumericMatrix([
            [1, 2, 3],
            [2, 3, 4],
            [3, 4, 5],
        ]);

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $A->solve($b);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function dataProviderForSolveExceptionNotVectorOrArray(): array
    {
        return [
            [new NumericMatrix([[1], [2], [3]])],
            [25],
        ];
    }

    /**
     * @test         Test ref by solving the system of linear equations.
     *               There is no single row echelon form for a matrix (as opposed to reduced row echelon form).
     *               Therefore, instead of directly testing the REF obtained,
     *               use the REF to then solve for x using back substitution.
     *               The result should be the expected solution to the system of linear equations.
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @param        array $expected_x
     * @throws       \Exception
     */
    public function testRefUsingSolve(array $A, array $b, array $expected_x)
    {
        // Given
        $m        = count($b);
        $A        = MatrixFactory::create($A);
        $b_matrix = MatrixFactory::createFromVectors([new Vector($b)]);
        $Ab       = $A->augment($b_matrix);
        $ref      = $Ab->ref();

        // When solve for x using back substitution on the REF matrix
        $x = [];
        for ($i = $m - 1; $i >= 0; $i--) {
            $x[$i] = $ref[$i][$m];
            for ($j = $i + 1; $j < $m; $j++) {
                $x[$i] -= $ref[$i][$j] * $x[$j];
            }
            $x[$i] /= $ref[$i][$i];
        }

        // Then
        $this->assertEqualsWithDelta($expected_x, $x, 0.00001);

        // And as an extra check, solve the original matrix and compare the result.
        $solved_x = $A->solve($b);
        $this->assertEqualsWithDelta($x, $solved_x->getVector(), 0.00001);
    }

    /**
     * @test         After solving, multiplying Ax = b
     *               In Python you could do numpy.dot(A, x) == b for this verification
     * @dataProvider dataProviderForSolve
     * @param        array $A
     * @param        array $b
     * @throws       \Exception
     */
    public function testAxEqualsBAfterSolving(array $A, array $b)
    {
        // Given
        $A = MatrixFactory::create($A);

        // And
        $x = $A->solve($b);

        // When Ax
        $Ax = $A->multiply($x);

        // Then Ax = b
        $this->assertEqualsWithDelta($b, $Ax->asVectors()[0]->getVector(), 0.00001);
    }

    /**
     * @test Issue 413 solving a singular matrix with RREF - Solve with RREF
     *
     * For the matrix
     *   [1, 0, 0, 0, 0, 0]
     *   [0, 1, 0, 0, 1, 0]
     *   [0, 0, 1, 0, 0, 1]
     *   [0, 0, 0, 0, 0, 0]
     *   [0, 0, -180.92, 0, 0, -854.14]
     *   [0, 180.92, 0, 0, 854.14, 0]
     * The RREF ends up being
     *   [1, 0, 0, 0, 0, 0, 1.457]
     *   [0, 1, 0, 0, 0, 0, -1.2294375984077]
     *   [0, 0, 1, 0, 0, 0, -4.7787483437806]
     *   [0, 0, 0, 0, 1, 0, -0.22756240159235]
     *   [0, 0, 0, 0, 0, 1, -0.88525165621936]
     *   [0, 0, 0, 0, 0, 0, 0]
     *
     * If we solve by just taking the augmented column on the right, the values are in the wrong order.
     * This is because the ones are not on the diagonal because the zero row at the bottom.
     * Expect x to be [1.4577, -1.230246179417, -4.778633012290, 0, -0.22745382058, -0.88526698770]
     * But intead ends up being [1.4577, -1.230246179417, -4.778633012290, -0.22745382058, -0.88526698770, 0]
     *
     * The fix checks if the matrix is singular, and if so, rearranges the x vector using the ones to order the values.
     */
    public function testSingularMatrixIssue413WhenSpecifyingSolveByRref()
    {
        // Given
        $data = [
            [1, 0, 0, 0, 0, 0],
            [0, 1, 0, 0, 1, 0],
            [0, 0, 1, 0, 0, 1],
            [0, 0, 0, 0, 0, 0],
            [0, 0, -180.92, 0, 0, -854.14],
            [0, 180.92, 0, 0, 854.14, 0],
        ];

        // And
        $A = MatrixFactory::create($data);
        $b = [1.457, -1.457, -5.664, 0, 1620.7, -416.8];

        // When
        $x = $A->solve($b, NumericMatrix::RREF);

        // Then x has expected values
        $expected = [1.4577, -1.230246179417, -4.778633012290, 0, -0.22745382058, -0.88526698770];
        $this->assertEqualsWithDelta($expected, $x->getVector(), 0.001);

        // And when Ax
        $Ax = $A->multiply($x);

        // Then Ax =  b
        $this->assertEqualsWithDelta($b, $Ax->getColumn(0), 0.00001);
    }

    /**
     * @test Issue 413 solving a singular matrix with RREF - Solve without specifying method
     * @link https://github.com/markrogoyski/math-php/issues/413
     *
     * For the matrix
     *   [1, 0, 0, 0, 0, 0]
     *   [0, 1, 0, 0, 1, 0]
     *   [0, 0, 1, 0, 0, 1]
     *   [0, 0, 0, 0, 0, 0]
     *   [0, 0, -180.92, 0, 0, -854.14]
     *   [0, 180.92, 0, 0, 854.14, 0]
     * The RREF ends up being
     *   [1, 0, 0, 0, 0, 0, 1.457]
     *   [0, 1, 0, 0, 0, 0, -1.2294375984077]
     *   [0, 0, 1, 0, 0, 0, -4.7787483437806]
     *   [0, 0, 0, 0, 1, 0, -0.22756240159235]
     *   [0, 0, 0, 0, 0, 1, -0.88525165621936]
     *   [0, 0, 0, 0, 0, 0, 0]
     *
     * If we solve by just taking the augmented column on the right, the values are in the wrong order.
     * This is because the ones are not on the diagonal because the zero row at the bottom.
     * Expect x to be [1.4577, -1.230246179417, -4.778633012290, 0, -0.22745382058, -0.88526698770]
     * But instead ends up being [1.4577, -1.230246179417, -4.778633012290, -0.22745382058, -0.88526698770, 0]
     *
     * The fix checks if the matrix is singular, and if so, rearranges the x vector using the ones to order the values.
     */
    public function testSingularMatrixIssue413WhenSpecifyingSolveWithoutSpecifyingMethod()
    {
        // Given
        $data = [
            [1, 0, 0, 0, 0, 0],
            [0, 1, 0, 0, 1, 0],
            [0, 0, 1, 0, 0, 1],
            [0, 0, 0, 0, 0, 0],
            [0, 0, -180.92, 0, 0, -854.14],
            [0, 180.92, 0, 0, 854.14, 0],
        ];

        // And
        $A = MatrixFactory::create($data);
        $b = [1.457, -1.457, -5.664, 0, 1620.7, -416.8];

        // When
        $x = $A->solve($b);

        // Then x has expected values
        $expected = [1.4577, -1.230246179417, -4.778633012290, 0, -0.22745382058, -0.88526698770];
        $this->assertEqualsWithDelta($expected, $x->getVector(), 0.001);

        // And when Ax
        $Ax = $A->multiply($x);

        // Then Ax =  b
        $this->assertEqualsWithDelta($b, $Ax->getColumn(0), 0.00001);
    }
}
