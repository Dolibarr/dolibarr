<?php

namespace MathPHP\Tests\SampleData;

use MathPHP\SampleData;

class ToothGrowthTest extends \PHPUnit\Framework\TestCase
{
    /** @var SampleData\ToothGrowth */
    private $toothGrowth;

    public function setUp(): void
    {
        $this->toothGrowth = new SampleData\ToothGrowth();
    }

    /**
     * @test 60 observations
     */
    public function testDataHas60Observations()
    {
        // When
        $data = $this->toothGrowth->getData();

        // Then
        $this->assertCount(60, $data);
    }

    /**
     * @test 3 variables
     */
    public function testDataHas3Variables()
    {
        // When
        $data = $this->toothGrowth->getData();

        // Then
        foreach ($data as $observation) {
            $this->assertCount(3, $observation);
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
        $labeledData = $this->toothGrowth->getLabeledData();

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
                    'len'  => 4.2,
                    'supp' => 'VC',
                    'dose' => 0.5,
                ]
            ],
            [
                29,
                [
                    'len'  => 29.5,
                    'supp' => 'VC',
                    'dose' => 2.0,
                ]
            ],
            [
                59,
                [
                    'len'  => 23.0,
                    'supp' => 'OJ',
                    'dose' => 2.0,
                ]
            ],
        ];
    }

    /**
     * @test 60 length observations
     */
    public function testNumberOfLength()
    {
        // When
        $observations = $this->toothGrowth->getLen();

        // Then
        $this->assertCount(60, $observations);
    }

    /**
     * @test 60 supplement observations
     */
    public function testNumberOfSupplementTypes()
    {
        // When
        $observations = $this->toothGrowth->getSupp();

        // Then
        $this->assertCount(60, $observations);
    }

    /**
     * @test 60 dose observations
     */
    public function testNumberOfDoses()
    {
        // When
        $observations = $this->toothGrowth->getDose();

        // Then
        $this->assertCount(60, $observations);
    }
}
