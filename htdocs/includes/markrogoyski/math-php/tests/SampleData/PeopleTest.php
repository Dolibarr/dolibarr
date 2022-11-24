<?php

namespace MathPHP\Tests\SampleData;

use MathPHP\SampleData;

class PeopleTest extends \PHPUnit\Framework\TestCase
{
    /** @var SampleData\People */
    private $people;

    public function setUp(): void
    {
        $this->people = new SampleData\People();
    }

    /**
     * @test 32 observations
     */
    public function testDataHas32Observations()
    {
        // When
        $data = $this->people->getData();

        // Then
        $this->assertCount(32, $data);
    }

    /**
     * @test 11 variables
     */
    public function testDataHas12Variables()
    {
        // When
        $data = $this->people->getData();

        // Then
        foreach ($data as $observation) {
            $this->assertCount(12, $observation);
        }
    }

    /**
     * @test 16 names
     */
    public function testNumberOfNames()
    {
        // When
        $names = $this->people->getNames();

        // Then
        $this->assertCount(32, $names);
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
        $labeledData = $this->people->getLabeledData();

        // Then
        $this->assertEquals($expectedData, $labeledData[$model]);
    }

    /**
     * @return array (model, data)
     */
    public function dataProviderForLabeledData(): array
    {
        return [
            [
                'Lars',
                [
                    'height'     => 198,
                    'weight'     => 92,
                    'hairLength' => -1,
                    'shoeSize'   => 48,
                    'age'        => 48,
                    'income'     => 45000,
                    'beer'       => 420,
                    'wine'       => 115,
                    'sex'        => -1,
                    'swim'       => 98,
                    'region'     => -1,
                    'iq'         => 100,
                ]
            ],
            [
                'Alessandro',
                [
                    'height'     => 181,
                    'weight'     => 75,
                    'hairLength' => -1,
                    'shoeSize'   => 43,
                    'age'        => 42,
                    'income'     => 31000,
                    'beer'       => 198,
                    'wine'       => 161,
                    'sex'        => -1,
                    'swim'       => 83,
                    'region'     => 1,
                    'iq'         => 105,
                ]
            ],
            [
                'Romina',
                [
                    'height'     => 160,
                    'weight'     => 48,
                    'hairLength' => 1,
                    'shoeSize'   => 35,
                    'age'        => 40,
                    'income'     => 31000,
                    'beer'       => 118,
                    'wine'       => 198,
                    'sex'        => 1,
                    'swim'       => 74,
                    'region'     => 1,
                    'iq'         => 129,
                ]
            ],
        ];
    }

    /**
     * @test 16 height observations
     */
    public function testNumberOfPersonData()
    {
        // Given
        $expected = [
            'height'     => 198,
            'weight'     => 92,
            'hairLength' => -1,
            'shoeSize'   => 48,
            'age'        => 48,
            'income'     => 45000,
            'beer'       => 420,
            'wine'       => 115,
            'sex'        => -1,
            'swim'       => 98,
            'region'     => -1,
            'iq'         => 100,
        ];

        // When
        $observation = $this->people->getPersonData('Lars');

        // Then
        $this->assertEquals($expected, $observation);
    }

    /**
     * @test 16 height observations
     */
    public function testNumberOfHeight()
    {
        // When
        $observations = $this->people->getHeight();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 weight observations
     */
    public function testNumberOfWeight()
    {
        // When
        $observations = $this->people->getWeight();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 hair length observations
     */
    public function testNumberOfHairLength()
    {
        // When
        $observations = $this->people->getHairLength();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 show size observations
     */
    public function testNumberOfShowSize()
    {
        // When
        $observations = $this->people->getShowSize();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 age observations
     */
    public function testNumberOfAge()
    {
        // When
        $observations = $this->people->getAge();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 income observations
     */
    public function testNumberOfIncome()
    {
        // When
        $observations = $this->people->getIncome();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 beer observations
     */
    public function testNumberOfBeer()
    {
        // When
        $observations = $this->people->getBeer();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 wine observations
     */
    public function testNumberOfWine()
    {
        // When
        $observations = $this->people->getWine();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 sex observations
     */
    public function testNumberOfSex()
    {
        // When
        $observations = $this->people->getSex();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 swim observations
     */
    public function testNumberOfSwim()
    {
        // When
        $observations = $this->people->getSwim();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 region observations
     */
    public function testNumberOfRegion()
    {
        // When
        $observations = $this->people->getRegion();

        // Then
        $this->assertCount(32, $observations);
    }

    /**
     * @test 32 iq observations
     */
    public function testNumberOfIq()
    {
        // When
        $observations = $this->people->getIq();

        // Then
        $this->assertCount(32, $observations);
    }
}
