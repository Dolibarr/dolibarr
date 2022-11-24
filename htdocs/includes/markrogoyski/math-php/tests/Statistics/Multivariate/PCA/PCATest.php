<?php

namespace MathPHP\Tests\Statistics\Multivariate\PCA;

use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\SampleData;
use MathPHP\Statistics\Multivariate\PCA;
use MathPHP\Exception;

class PCATest extends \PHPUnit\Framework\TestCase
{
    /** @var PCA */
    private static $pca;

    /** @var NumericMatrix  */
    private static $matrix;

    /**
     * R code for expected values:
     *   library(mdatools)
     *   data = mtcars[,c(1:7,10,11)]
     *   model = pca(data, center=TRUE, scale=TRUE)
     *
     * @throws Exception\MathException
     */
    public static function setUpBeforeClass(): void
    {
        $mtCars = new SampleData\MtCars();

        // Remove and categorical variables
        self::$matrix = MatrixFactory::create($mtCars->getData())->columnExclude(8)->columnExclude(7);
        self::$pca = new PCA(self::$matrix, true, true);
    }

    /**
     * @test         Construction
     * @dataProvider dataProviderForConstructorParameters
     * @param        bool $center
     * @param        bool $scale
     * @throws       Exception\MathException
     */
    public function testConstruction(bool $center, bool $scale)
    {
        // When
        $pca = new PCA(self::$matrix, $center, $scale);

        // Then
        $this->assertInstanceOf(PCA::class, $pca);
    }

    /**
     * @return array (center, scale)
     */
    public function dataProviderForConstructorParameters(): array
    {
        return [
            [true, true],
            [true, false],
            [false, true],
            [false, false],
        ];
    }

    /**
     * @test   Test that the constructor throws an exception if the source matrix is too small
     * @throws \Exception
     */
    public function testConstructorException()
    {
        // Given
        $matrix = MatrixFactory::create([[1,2]]);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $pca = new PCA($matrix, true, true);
    }

    /**
     * @test   Test that the new data must have the have the same number of columns
     * @throws \Exception
     */
    public function testNewDataException()
    {
        // Given
        $new_data = MatrixFactory::create([[1,2]]);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        self::$pca->getScores($new_data);
    }
}
