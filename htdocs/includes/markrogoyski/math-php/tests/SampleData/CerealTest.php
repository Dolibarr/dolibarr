<?php

namespace MathPHP\Tests\SampleData;

use MathPHP\SampleData;

class CerealTest extends \PHPUnit\Framework\TestCase
{
    /** @var SampleData\Cereal */
    private $cereal;

    public function setUp(): void
    {
        $this->cereal = new SampleData\Cereal();
    }

    /**
     * @test observations
     */
    public function testDataObservations()
    {
        // When
        $X     = $this->cereal->getXData();
        $Y     = $this->cereal->getYData();
        $Ysc   = $this->cereal->getYscData();
        $names = $this->cereal->getCereals();

        // Then
        $this->assertCount(15, $X);
        $this->assertCount(15, $Y);
        $this->assertCount(15, $Ysc);
        $this->assertCount(15, $names);
    }

    /**
     * @test X variables
     */
    public function testXDataVariables()
    {
        // When
        $X = $this->cereal->getXData();

        // Then
        foreach ($X as $observation) {
            $this->assertCount(145, $observation);
        }
    }

    /**
     * @test Labeled X data
     */
    public function testLabeledXData()
    {
        // When
        $X = $this->cereal->getLabeledXData();

        // Then
        $this->assertCount(15, $X);
        foreach ($X as $label => $observation) {
            $this->assertTrue(\in_array($label, SampleData\Cereal::CEREALS));
            $this->assertCount(145, $observation);
        }
    }

    /**
     * @test Y variables
     */
    public function testYDataVariables()
    {
        // When
        $Y = $this->cereal->getYData();

        // Then
        foreach ($Y as $observation) {
            $this->assertCount(6, $observation);
        }
    }

    /**
     * @test Labeled Y data
     */
    public function testLabeledYData()
    {
        // When
        $Y = $this->cereal->getLabeledYData();

        // Then
        $this->assertCount(15, $Y);
        foreach ($Y as $label => $observation) {
            $this->assertTrue(\in_array($label, SampleData\Cereal::CEREALS));
            $this->assertCount(6, $observation);
        }
    }

    /**
     * @test Ysc variables
     */
    public function testYscDataVariables()
    {
        // When
        $Ysc = $this->cereal->getYscData();

        // Then
        foreach ($Ysc as $observation) {
            $this->assertCount(6, $observation);
        }
    }

    /**
     * @test Labeled Ysc data
     */
    public function testLabeledYscData()
    {
        // When
        $Ysc = $this->cereal->getLabeledYscData();

        // Then
        $this->assertCount(15, $Ysc);
        foreach ($Ysc as $label => $observation) {
            $this->assertTrue(\in_array($label, SampleData\Cereal::CEREALS));
            $this->assertCount(6, $observation);
        }
    }

    /**
     * @test Scaled center
     */
    public function testGetScaledCenter()
    {
        // When
        $scaledCenter = $this->cereal->getScaledCenter();

        // Then
        $this->assertCount(6, $scaledCenter);
    }

    /**
     * @test Scaled scake
     */
    public function testGetScaledScale()
    {
        // When
        $scaledScale = $this->cereal->getScaledScale();

        // Then
        $this->assertCount(6, $scaledScale);
    }
}
