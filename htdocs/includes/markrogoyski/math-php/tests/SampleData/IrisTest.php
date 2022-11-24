<?php

namespace MathPHP\Tests\SampleData;

use MathPHP\SampleData;

class IrisTest extends \PHPUnit\Framework\TestCase
{
    /** @var SampleData\Iris */
    private $iris;

    public function setUp(): void
    {
        $this->iris = new SampleData\Iris();
    }

    /**
     * @test 150 observations
     */
    public function testDataHas150Observations()
    {
        // When
        $data = $this->iris->getData();

        // Then
        $this->assertCount(150, $data);
    }

    /**
     * @test 5 variables
     */
    public function testDataHas5Variables()
    {
        // When
        $data = $this->iris->getData();

        // Then
        foreach ($data as $observation) {
            $this->assertCount(5, $observation);
        }
    }

    /**
     * @test         Labeled data
     * @dataProvider dataProviderForLabeledData
     * @param        int    $i
     * @param        array  $expectedData
     */
    public function testLabeledData(int $i, array $expectedData)
    {
        // When
        $labeledData = $this->iris->getLabeledData();

        // Then
        $this->assertEquals($expectedData, $labeledData[$i]);
    }

    /**
     * @return array (model, data)
     */
    public function dataProviderForLabeledData(): array
    {
        return [
            [
                0,
                [
                    'sepalLength' => 5.1,
                    'sepalWidth'  => 3.5,
                    'petalLength' => 1.4,
                    'petalWidth'  => 0.2,
                    'species'     => 'setosa',
                ]
            ],
            [
                74,
                [
                    'sepalLength' => 6.4,
                    'sepalWidth'  => 2.9,
                    'petalLength' => 4.3,
                    'petalWidth'  => 1.3,
                    'species'     => 'versicolor',
                ]
            ],
            [
                149,
                [
                    'sepalLength' => 5.9,
                    'sepalWidth'  => 3.0,
                    'petalLength' => 5.1,
                    'petalWidth'  => 1.8,
                    'species'     => 'virginica',
                ]
            ],
        ];
    }

    /**
     * @test 150 sepal length observations
     */
    public function testNumberOfSepalLength()
    {
        // When
        $observations = $this->iris->getSepalLength();

        // Then
        $this->assertCount(150, $observations);
    }

    /**
     * @test 150 sepal width observations
     */
    public function testNumberOfSepalWidth()
    {
        // When
        $observations = $this->iris->getSepalWidth();

        // Then
        $this->assertCount(150, $observations);
    }

    /**
     * @test 150 petal length observations
     */
    public function testNumberOfPetalLength()
    {
        // When
        $observations = $this->iris->getPetalLength();

        // Then
        $this->assertCount(150, $observations);
    }

    /**
     * @test 150 petal width observations
     */
    public function testNumberOfPetalWidth()
    {
        // When
        $observations = $this->iris->getPetalWidth();

        // Then
        $this->assertCount(150, $observations);
    }

    /**
     * @test 150 species observations
     */
    public function testNumberOfSpecies()
    {
        // When
        $observations = $this->iris->getSpecies();

        // Then
        $this->assertCount(150, $observations);
    }
}
