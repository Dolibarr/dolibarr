<?php

namespace MathPHP\Tests\SampleData;

use MathPHP\SampleData;

class MtCarsTest extends \PHPUnit\Framework\TestCase
{
    /** @var SampleData\MtCars */
    private $mtCars;

    public function setUp(): void
    {
        $this->mtCars = new SampleData\MtCars();
    }

    /**
     * @test 32 observations
     */
    public function testDataHas32Observations()
    {
        // When
        $data = $this->mtCars->getData();

        // Then
        $this->assertCount(32, $data);
    }

    /**
     * @test 11 variables
     */
    public function testDataHas11Variables()
    {
        // When
        $data = $this->mtCars->getData();

        // Then
        foreach ($data as $observation) {
            $this->assertCount(11, $observation);
        }
    }

    /**
     * @test 32 models
     */
    public function testNumberOfModels()
    {
        // When
        $models = $this->mtCars->getModels();

        // Then
        $this->assertCount(32, $models);
    }

    /**
     * @test Model names
     */
    public function testModelNames()
    {
        // Given
        $sampleOfModelNames = ['Mazda RX4', 'Honda Civic', 'Toyota Corolla', 'Volvo 142E'];
        $models             = $this->mtCars->getModels();

        // When
        foreach ($sampleOfModelNames as $model) {
            // Then
            $this->assertTrue(\in_array($model, $models));
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
        $labeledData = $this->mtCars->getLabeledData();

        // Then
        $this->assertEquals($expectedData, $labeledData[$model]);
    }

    /**
     * @test         Model data
     * @dataProvider dataProviderForLabeledData
     * @param        string $model
     * @param        array  $expectedData
     */
    public function testGetModelData(string $model, array $expectedData)
    {
        // When
        $data = $this->mtCars->getModelData($model);

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
                'Mazda RX4',
                [
                    'mpg'  => 21,
                    'cyl'  => 6,
                    'disp' => 160,
                    'hp'   => 110,
                    'drat' => 3.9,
                    'wt'   => 2.62,
                    'qsec' => 16.46,
                    'vs'   => 0,
                    'am'   => 1,
                    'gear' => 4,
                    'carb' => 4
                ]
            ],
            [
                'Honda Civic',
                [
                    'mpg'  => 30.4,
                    'cyl'  => 4,
                    'disp' => 75.7,
                    'hp'   => 52,
                    'drat' => 4.93,
                    'wt'   => 1.615,
                    'qsec' => 18.52,
                    'vs'   => 1,
                    'am'   => 1,
                    'gear' => 4,
                    'carb' => 2,
                ]
            ],
            [
                'Volvo 142E',
                [
                    'mpg'  => 21.4,
                    'cyl'  => 4,
                    'disp' => 121,
                    'hp'   => 109,
                    'drat' => 4.11,
                    'wt'   => 2.78,
                    'qsec' => 18.6,
                    'vs'   => 1,
                    'am'   => 1,
                    'gear' => 4,
                    'carb' => 2,
                ]
            ],
        ];
    }

    /**
     * @test 32 mpg observations
     */
    public function testNumberOfMpgs()
    {
        // When
        $observations = $this->mtCars->getMpg();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 cyl observations
     */
    public function testNumberOfCyl()
    {
        // When
        $observations = $this->mtCars->getCyl();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 disp observations
     */
    public function testNumberOfDisps()
    {
        // When
        $observations = $this->mtCars->getDisp();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 hp observations
     */
    public function testNumberOfHps()
    {
        // When
        $observations = $this->mtCars->getHp();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 drat observations
     */
    public function testNumberOfDrats()
    {
        // When
        $observations = $this->mtCars->getDrat();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 wt observations
     */
    public function testNumberOfWts()
    {
        // When
        $observations = $this->mtCars->getWt();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 qsec observations
     */
    public function testNumberOfQsecs()
    {
        // When
        $observations = $this->mtCars->getQsec();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 vs observations
     */
    public function testNumberOfVss()
    {
        // When
        $observations = $this->mtCars->getVs();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 am observations
     */
    public function testNumberOfAms()
    {
        // When
        $observations = $this->mtCars->getAm();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 gear observations
     */
    public function testNumberOfgears()
    {
        // When
        $observations = $this->mtCars->getGear();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 carb observations
     */
    public function testNumberOfCarbs()
    {
        // When
        $observations = $this->mtCars->getCarb();

        // Then
        $this->assertCount(32, $observations);
    }
}
