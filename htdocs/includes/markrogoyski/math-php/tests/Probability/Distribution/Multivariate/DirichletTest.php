<?php

namespace MathPHP\Tests\Probability\Distribution\Multivariate;

use MathPHP\Probability\Distribution\Multivariate\Dirichlet;
use MathPHP\Exception;

class DirichletTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         pdf works as expected
     * @dataProvider dataProviderForPDF
     * @param        array $xs
     * @param        array $αs
     * @param        float $expected
     * @throws       \Exception
     */
    public function testPdf(array $xs, array $αs, $expected)
    {
        // Given
        $dirichlet = new Dirichlet($αs);

        // When
        $pdf = $dirichlet->pdf($xs);

        // Then
        $this->assertEqualsWithDelta($expected, $pdf, 0.00001);
    }

    /**
     * Test data made with scipy.stats.dirichlet.pdf
     * @return array
     */
    public function dataProviderForPdf(): array
    {
        return [
            [
                [0.07255081, 0.27811903, 0.64933016],
                [1, 2, 3],
                7.03579378,
            ],
            [
                [0.48061647, 0.26403299, 0.25535054],
                [1, 2, 3],
                1.0329588,
            ],
            [
                [0.1075077, 0.68975829, 0.20273401],
                [1, 2, 3],
                1.70098867,
            ],
            [
                [0.14909711, 0.20747317, 0.64342972],
                [1, 2, 3],
                5.15365594,
            ],
            [
                [0.19010044, 0.26225231, 0.54764725],
                [1, 2, 3],
                4.71924363,
            ],
            [
                [0.06652357, 0.53396899, 0.39950744],
                [1, 2, 3],
                5.11348541,
            ],
            [
                [0.16362941, 0.4521069, 0.38426368],
                [1, 2, 3],
                4.00544774,
            ],
            [
                [0.07255081, 0.27811903, 0.64933016],
                [0.1, 0.3, 0.7],
                0.76125217,
            ],
            [
                [0.48061647, 0.26403299, 0.25535054],
                [0.1, 0.3, 0.7],
                0.19049659,
            ],
            [
                [0.1075077, 0.68975829, 0.20273401],
                [0.1, 0.3, 0.7],
                0.40118652,
            ],
            [
                [0.14909711, 0.20747317, 0.64342972],
                [0.1, 0.3, 0.7],
                0.49007413,
            ],
            [
                [0.19010044, 0.26225231, 0.54764725],
                [0.1, 0.3, 0.7],
                0.35080774,
            ],
            [
                [0.06652357, 0.53396899, 0.39950744],
                [0.1, 0.3, 0.7],
                0.6031302,
            ],
            [
                [0.16362941, 0.4521069, 0.38426368],
                [0.1, 0.3, 0.7],
                0.30498251,
            ],
        ];
    }

    /**
     * @test    pdf throws a BadDataException if the xs and αs do not have the same number of elements
     * @throws \Exception
     */
    public function testPdfArraysNotSameLengthException()
    {
        // Given
        $xs = [0.1, 0.2];
        $αs = [1, 2, 3];
        $dirichlet = new Dirichlet($αs);

        // Then
        $this->expectException(Exception\BadDataException::class);

        // When
        $pdf = $dirichlet->pdf($xs);
    }
}
