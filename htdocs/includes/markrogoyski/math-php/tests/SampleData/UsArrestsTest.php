<?php

namespace MathPHP\Tests\SampleData;

use MathPHP\SampleData;

class UsArrestsTest extends \PHPUnit\Framework\TestCase
{
    /** @var SampleData\UsArrests */
    private $usArrests;

    public function setUp(): void
    {
        $this->usArrests = new SampleData\UsArrests();
    }

    /**
     * @test 50 observations
     */
    public function testDataHas50Observations()
    {
        // When
        $data = $this->usArrests->getData();

        // Then
        $this->assertCount(50, $data);
    }

    /**
     * @test 4 variables
     */
    public function testDataHas4Variables()
    {
        // When
        $data = $this->usArrests->getData();

        // Then
        foreach ($data as $observation) {
            $this->assertCount(4, $observation);
        }
    }

    /**
     * @test 50 states
     */
    public function testNumberOfModels()
    {
        // When
        $models = $this->usArrests->getStates();

        // Then
        $this->assertCount(50, $models);
    }

    /**
     * @test State names
     */
    public function testStateNames()
    {
        // Given
        $sampleOfStateNames = ['Alabama', 'Alaska', 'Texas', 'Wyoming'];
        $states              = $this->usArrests->getStates();

        // When
        foreach ($sampleOfStateNames as $state) {
            // Then
            $this->assertTrue(\in_array($state, $states));
        }
    }

    /**
     * @test         Labeled data
     * @dataProvider dataProviderForLabeledData
     * @param        string $model
     * @param        array  $expectedData
     */
    public function testLabeledData(string $model, array $expectedData)
    {
        // When
        $labeledData = $this->usArrests->getLabeledData();

        // Then
        $this->assertEquals($expectedData, $labeledData[$model]);
    }

    /**
     * @test         Model data
     * @dataProvider dataProviderForLabeledData
     * @param        string $state
     * @param        array  $expectedData
     */
    public function testGetStateData(string $state, array $expectedData)
    {
        // When
        $data = $this->usArrests->getStateData($state);

        // Then
        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array (model, data)
     */
    public function dataProviderForLabeledData(): array
    {
        return [
            [
                'Alabama',
                [
                    'murder'   => 13.2,
                    'assault'  => 236,
                    'urbanPop' => 58,
                    'rape'     => 21.2,
                ]
            ],
            [
                'New York',
                [
                    'murder'   => 11.1,
                    'assault'  => 254,
                    'urbanPop' => 86,
                    'rape'     => 26.1,
                ]
            ],
            [
                'Wyoming',
                [
                    'murder'   => 6.8,
                    'assault'  => 161,
                    'urbanPop' => 60,
                    'rape'     => 15.6,
                ]
            ],
        ];
    }

    /**
     * @test 50 murder observations
     */
    public function testNumberOfMurders()
    {
        // When
        $observations = $this->usArrests->getMurder();

        // Then
        $this->assertCount(50, $observations);
    }

    /**
     * @test 50 assault observations
     */
    public function testNumberOfAssaults()
    {
        // When
        $observations = $this->usArrests->getAssault();

        // Then
        $this->assertCount(50, $observations);
    }

    /**
     * @test 50 urbanPop observations
     */
    public function testNumberOfUrbanPops()
    {
        // When
        $observations = $this->usArrests->getUrbanPop();

        // Then
        $this->assertCount(50, $observations);
    }

    /**
     * @test 50 rape observations
     */
    public function testNumberOfRapes()
    {
        // When
        $observations = $this->usArrests->getRape();

        // Then
        $this->assertCount(50, $observations);
    }
}
