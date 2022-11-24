<?php

namespace MathPHP\Tests\SampleData;

use MathPHP\SampleData;

class PlantGrowthTest extends \PHPUnit\Framework\TestCase
{
    /** @var SampleData\PlantGrowth */
    private $plantGrowth;

    public function setUp(): void
    {
        $this->plantGrowth = new SampleData\PlantGrowth();
    }

    /**
     * @test 30 observations
     */
    public function testDataHas30Observations()
    {
        // When
        $data = $this->plantGrowth->getData();

        // Then
        $this->assertCount(30, $data);
    }

    /**
     * @test 2 variables
     */
    public function testDataHas2Variables()
    {
        // When
        $data = $this->plantGrowth->getData();

        // Then
        foreach ($data as $observation) {
            $this->assertCount(2, $observation);
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
        $labeledData = $this->plantGrowth->getLabeledData();

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
                    'weight' => 4.17,
                    'group'  => 'ctrl',
                ]
            ],
            [
                14,
                [
                    'weight' => 5.87,
                    'group'  => 'trt1',
                ]
            ],
            [
                29,
                [
                    'weight' => 5.26,
                    'group'  => 'trt2',
                ]
            ],
        ];
    }

    /**
     * @test 30 weight observations
     */
    public function testNumberOfWeights()
    {
        // When
        $observations = $this->plantGrowth->getWeight();

        // Then
        $this->assertCount(30, $observations);
    }

    /**
     * @test 30 group observations
     */
    public function testNumberOfGroups()
    {
        // When
        $observations = $this->plantGrowth->getGroup();

        // Then
        $this->assertCount(30, $observations);
    }
}
