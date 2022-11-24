<?php

namespace MathPHP\Statistics;

use MathPHP\Probability\Distribution\Continuous\F;
use MathPHP\Exception;

/**
 * ANOVA (Analysis of Variance)
 */
class ANOVA
{
    /**
     * One-way ANOVA
     * Technique used to compare means of three or more samples
     * (using the F distribution).
     * https://en.wikipedia.org/wiki/One-way_analysis_of_variance
     *
     * Produces the following analysis of the data:
     *
     * ANOVA hypothesis test summary data
     *
     *           | SS | df | MS | F | P |
     * Treatment |    |    |    |   |   |
     * Error     |    |    |    |
     * Total     |    |    |
     *
     *  where:
     *   Treament is between groups
     *   Error is within groups
     *   SS = Sum of squares
     *   df = Degrees of freedom
     *   MS = Mean squares
     *   F  = F statistic
     *   P  = P value
     *
     * Data summary table
     *
     *       | N | Sum | Mean | SS | Variance | SD | SEM |
     * 0     |   |     |      |    |          |    |     |
     * 1     |   |     |      |    |          |    |     |
     * ...   |   |     |      |    |          |    |     |
     * Total |   |     |      |    |          |    |     |
     *
     *  where:
     *   Each row is the summary for a sample, numbered from 0 to m - 1
     *   m   = Number of samples
     *   N   = Sample size
     *   SS  = Sum of squares
     *   SD  = Standard deviation
     *   SEM = Standard error of the mean
     *
     * Calculations
     *
     * Sum of Squares
     * SST (sum of squares total)
     * ∑⟮xᵢ − μ⟯²
     *  where:
     *   xᵢ = each element of all samples
     *   μ  = mean total of all elements of all samples
     *
     * SSB (sum of squares between - treatment)
     * ∑n(x - μ)²
     *  where:
     *   n = sample size
     *   x = sample mean
     *   μ  = mean total of all elements of all samples
     *
     * SSW (sum of squares within - error)
     * ∑∑⟮xᵢ − μ⟯²  Sum of sum of squared deviations of each sample
     *  where:
     *   xᵢ = each element of the sample
     *   μ  = mean of the sample
     *
     * Degrees of Freedom
     * dfT (degrees of freedom for the total)
     * mn - 1
     *
     * dfB (degrees of freedom between - treatment)
     * m - 1
     *
     * dfW (degrees of freedom within - error)
     * m(n - 1)
     *
     *  where:
     *   m = number of samples
     *   n = number of elements in each sample
     *
     * Mean Squares
     * MSB (Mean squares between - treatment)
     * SSB / dfB
     *
     * MSW (Mean squares within - error)
     * SSW / dfW
     *
     * Test Statistics
     * F = MSB / MSW
     * P = F distribution CDF above F with degrees of freedom dfB and dfW
     *
     * @param  array[] ...$samples Samples to analyze (at least 3 or more samples)
     *
     * @return array [
     *                 ANOVA => [
     *                   treatment => [SS, df, MS, F, P],
     *                   error     => [SS, df, MS],
     *                   total     => [SS, df],
     *                 ],
     *                 total_summary => [n, sum, mean, SS, variance, sd, sem],
     *                 data_summary  => [
     *                   0     => [n, sum, mean, SS, variance, sd, sem],
     *                   1     => [n, sum, mean, SS, variance, sd, sem],
     *                   ...
     *                 ]
     *               ]
     *
     * @throws Exception\BadDataException if less than three samples, or if all samples don't have the same number of values
     * @throws Exception\OutOfBoundsException
     */
    public static function oneWay(array ...$samples): array
    {
        // Must have at least three samples
        $m = \count($samples);
        if ($m < 3) {
            throw new Exception\BadDataException('Must have at least three samples');
        }

        // All samples must have the same number of items
        $n = \count($samples[0]);
        for ($i = 1; $i < $m; $i++) {
            if (\count($samples[$i]) !== $n) {
                throw new Exception\BadDataException('All samples must have the same number of values');
            }
        }

        // Summary data for each sample
        $summary_data = [];
        foreach ($samples as $i => $sample) {
            $summary_data[$i]             = [];
            $summary_data[$i]['n']        = $n;
            $summary_data[$i]['sum']      = \array_sum($sample);
            $summary_data[$i]['mean']     = Average::mean($sample);
            $summary_data[$i]['SS']       = RandomVariable::sumOfSquares($sample);
            $summary_data[$i]['variance'] = Descriptive::sampleVariance($sample);
            $summary_data[$i]['sd']       = Descriptive::sd($sample);
            $summary_data[$i]['sem']      = RandomVariable::standardErrorOfTheMean($sample);
        }

        // Totals summary
        $all_elements = \array_reduce(
            $samples,
            function ($merged, $sample) {
                return \array_merge($merged, $sample);
            },
            array()
        );
        $μ     = Average::mean($all_elements);
        $total = [
            'n'        => \count($all_elements),
            'sum'      => \array_sum($all_elements),
            'mean'     => $μ,
            'SS'       => RandomVariable::sumOfSquares($all_elements),
            'variance' => Descriptive::sampleVariance($all_elements),
            'sd'       => Descriptive::sd($all_elements),
            'sem'      => RandomVariable::standardErrorOfTheMean($all_elements),
        ];

        // ANOVA sum of squares
        $SST = RandomVariable::sumOfSquaresDeviations($all_elements);
        $SSB = \array_sum(\array_map(
            function ($sample) use ($n, $μ) {
                return $n * (Average::mean($sample) - $μ) ** 2;
            },
            $samples
        ));
        $SSW = \array_sum(\array_map(
            'MathPHP\Statistics\RandomVariable::sumOfSquaresDeviations',
            $samples
        ));

        // ANOVA degrees of freedom
        $dfT = $m * $n - 1;
        $dfB = $m - 1;
        $dfW = $m * ($n - 1);

        // ANOVA mean squares
        $MSB = $SSB / $dfB;
        $MSW = $SSW / $dfW;

        // Test statistics
        $F = $MSB / $MSW;
        $fDist = new F($dfB, $dfW);
        $P = $fDist->above($F);

        // Return ANOVA report
        return [
            'ANOVA' => [
                'treatment' => [
                    'SS' => $SSB,
                    'df' => $dfB,
                    'MS' => $MSB,
                    'F'  => $F,
                    'P'  => $P,
                ],
                'error' => [
                    'SS' => $SSW,
                    'df' => $dfW,
                    'MS' => $MSW,
                ],
                'total' => [
                    'SS' => $SST,
                    'df' => $dfT,
                ],
            ],
            'total_summary' => $total,
            'data_summary'  => $summary_data,
        ];
    }

    /**
     * Two-way ANOVA
     * Examines the influence of two different categorical independent variables on
     * one continuous dependent variable. The two-way ANOVA not only aims at assessing
     * the main effect of each independent variable but also if there is any interaction
     * between them (using the F distribution).
     * https://en.wikipedia.org/wiki/Two-way_analysis_of_variance
     *
     * Produces the following analysis of the data:
     *
     * ANOVA hypothesis test summary data
     *
     *             | SS | df | MS | F | P |
     * Factor A    |    |    |    |   |   |
     * Factor B    |    |    |    |   |   |
     * Interaction |    |    |    |   |   |
     * Error       |    |    |    |
     * Total       |    |    |
     *
     *  where:
     *   Interaction = Factor A X Factor B working together
     *   Error is within groups
     *   SS = Sum of squares
     *   df = Degrees of freedom
     *   MS = Mean squares
     *   F  = F statistic
     *   P  = P value
     *
     * Data summary tables for:
     *   Factor A
     *   Factor B
     *   Factor AB (Interaction)
     *   Total
     *
     *       | N | Sum | Mean | SS | Variance | SD | SEM |
     * 0     |   |     |      |    |          |    |     |
     * 1     |   |     |      |    |          |    |     |
     * ...   |   |     |      |    |          |    |     |
     * Total |   |     |      |    |          |    |     |
     *
     *  where:
     *   Each row is the summary for a sample, numbered from 0 to m - 1
     *   m   = Number of samples
     *   N   = Sample size
     *   SS  = Sum of squares
     *   SD  = Standard deviation
     *   SEM = Standard error of the mean
     *
     * Calculations
     *
     * Sum of Squares
     * SST (sum of squares total)
     * ∑⟮xᵢ − μ⟯²
     *  where:
     *   xᵢ = each element of all samples
     *   μ  = mean total of all elements of all samples
     *
     * SSA, SSB (sum of squares for each factor A and B)
     * ∑n(x - μ)²
     *  where:
     *   n = sample size
     *   x = sample mean
     *   μ  = mean total of all elements of all samples
     *
     * SSW (sum of squares within - error)
     * ∑∑⟮x − μ⟯²  Sum of sum of squared deviations of each sample
     *  where:
     *   x = mean of each AB
     *   μ = mean of the sample
     *
     * SSAB (sum of squares AB - interaction)
     * SSAB = SST - SSA - SSB - SSW;
     *
     * Degrees of Freedom
     * dfT (degrees of freedom for the total)
     * n - 1
     *
     * dfA (degrees of freedom factor A)
     * r - 1
     *
     * dfB (degrees of freedom factor B)
     * c - 1
     *
     * dfAB (degrees of freedom factor AB - interaction)
     * (r - 1)(c - 1)
     *
     * dfW (degrees of freedom within - error)
     * n - rc
     *
     *  where:
     *   n = number of samples
     *   r = number of rows (number of factor As)
     *   c = number of columns (number of factor Bs)
     *
     * Mean Squares
     * MSA (Mean squares factor A)
     * SSA / dfA
     *
     * MSB (Mean squares factor B)
     * SSB / dfB
     *
     * MSAB (Mean squares factor AB - interaction)
     * SSAB / dfAB
     *
     * MSW (Mean squares within - error)
     * SSW / dfW
     *
     * F Test Statistics
     * FA  = MSA / MSW
     * FB  = MSB / MSW
     * FAB = MSAB / MSW
     *
     * P values
     * PA  = F distribution CDF above FA with degrees of freedom dfA and dfW
     * PB  = F distribution CDF above FB with degrees of freedom dfA and dfW
     * PAB = F distribution CDF above FAB with degrees of freedom dfAB and dfW
     *
     * Example input data for ...$data parameter:
     *             | Factor B₁ | Factor B₂ | ⋯
     *   Factor A₁ |  4, 6, 8  |  6, 6, 9  | ⋯
     *   Factor A₂ |  4, 8, 9  | 7, 10, 13 | ⋯
     *      ⋮           ⋮           ⋮         ⋮
     * @param  array[] ...$data Samples to analyze [
     *               // Factor A₁
     *               [
     *                   [4, 6, 8] // Factor B₁
     *                   [6, 6, 9] // Factor B₂
     *                       ⋮
     *               ],
     *               // Factor A₂
     *               [
     *                   [4, 8, 9]   // Factor B₁
     *                   [7, 10, 13] // Factor B₂
     *                       ⋮
     *               ],
     *               ...
     *         ]
     *
     * @return array [
     *                 ANOVA => [
     *                   factorA  => [SS, df, MS, F, P],
     *                   factorB  => [SS, df, MS, F, P],
     *                   factorAB => [SS, df, MS, F, P],
     *                   error    => [SS, df, MS],
     *                   total    => [SS, df],
     *                 ],
     *                 total_summary => [n, sum, mean, SS, variance, sd, sem],
     *                 summary_factorA  => [
     *                   0     => [n, sum, mean, SS, variance, sd, sem],
     *                   1     => [n, sum, mean, SS, variance, sd, sem],
     *                   ...
     *                 ],
     *                 summary_factorB  => [
     *                   0     => [n, sum, mean, SS, variance, sd, sem],
     *                   1     => [n, sum, mean, SS, variance, sd, sem],
     *                   ...
     *                 ],
     *                 summary_factorAB  => [
     *                   0     => [n, sum, mean, SS, variance, sd, sem],
     *                   1     => [n, sum, mean, SS, variance, sd, sem],
     *                   ...
     *                 ]
     *               ]
     * @throws Exception\BadDataException if less than two A factors, or if B factors or values have different number elements
     * @throws Exception\OutOfBoundsException
     */
    public static function twoWay(array ...$data): array
    {
        // Must have at least two rows (two types of factor A)
        $r = \count($data);
        if ($r < 2) {
            throw new Exception\BadDataException('Must have at least two rows (two types of factor A)');
        }

        // All samples must have the same number the second factor B
        $c = \count($data[0]);
        for ($i = 1; $i < $r; $i++) {
            if (\count($data[$i]) !== $c) {
                throw new Exception\BadDataException('All samples must have the same number of the second factor B');
            }
        }

        // Each AB factor interaction must have the same number of values
        $v = \count($data[0][0]);
        for ($i = 0; $i < $r; $i++) {
            for ($j = 0; $j < $c; $j++) {
                if (\count($data[$i][$j]) !== $v) {
                    throw new Exception\BadDataException('Each AB factor interaction must have the same number of values');
                }
            }
        }

        // Aggregates for all elements, rows (factor A), and columns (factor B)
        $all_elements = [];
        $A_elements   = [];
        $B_elements   = [];

        // Summaries for factor A, factor B, and AB
        $summary_A     = [];
        $summary_B     = [];
        $summary_AB    = [];

        // Summary data for each AB
        // And aggregate all elements and elements for factor A
        foreach ($data as $A => $Bs) {
            $A_elements[$A] = [];
            foreach ($Bs as $B => $values) {
                // Aggregates
                $all_elements   = \array_merge($all_elements, $values);
                $A_elements[$A] = \array_merge($A_elements[$A], $values);

                // AB summary
                $summary_AB[$A][$B]             = [];
                $summary_AB[$A][$B]['n']        = $c;
                $summary_AB[$A][$B]['sum']      = \array_sum($values);
                $summary_AB[$A][$B]['mean']     = Average::mean($values);
                $summary_AB[$A][$B]['SS']       = RandomVariable::sumOfSquares($values);
                $summary_AB[$A][$B]['variance'] = Descriptive::sampleVariance($values);
                $summary_AB[$A][$B]['sd']       = Descriptive::sd($values);
                $summary_AB[$A][$B]['sem']      = RandomVariable::standardErrorOfTheMean($values);
            }
        }

        // Aggregate elements for factor B
        for ($B = 0; $B < $c; $B++) {
            $B_elements[$B] = [];
            foreach ($data as $factor1s) {
                $B_elements[$B] = \array_merge($B_elements[$B], $factor1s[$B]);
            }
        }

        // Factor A summary
        foreach ($A_elements as $A => $elements) {
            $summary_A[$A]             = [];
            $summary_A[$A]['n']        = \count($elements);
            $summary_A[$A]['sum']      = \array_sum($elements);
            $summary_A[$A]['mean']     = Average::mean($elements);
            $summary_A[$A]['SS']       = RandomVariable::sumOfSquares($elements);
            $summary_A[$A]['variance'] = Descriptive::sampleVariance($elements);
            $summary_A[$A]['sd']       = Descriptive::sd($elements);
            $summary_A[$A]['sem']      = RandomVariable::standardErrorOfTheMean($elements);
        }

        // Factor B summary
        foreach ($B_elements as $B => $elements) {
            $summary_B[$B]             = [];
            $summary_B[$B]['n']        = \count($elements);
            $summary_B[$B]['sum']      = \array_sum($elements);
            $summary_B[$B]['mean']     = Average::mean($elements);
            $summary_B[$B]['SS']       = RandomVariable::sumOfSquares($elements);
            $summary_B[$B]['variance'] = Descriptive::sampleVariance($elements);
            $summary_B[$B]['sd']       = Descriptive::sd($elements);
            $summary_B[$B]['sem']      = RandomVariable::standardErrorOfTheMean($elements);
        }

        // Totals summary
        $μ             = Average::mean($all_elements);
        $summary_total = [
            'n'        => \count($all_elements),
            'sum'      => \array_sum($all_elements),
            'mean'     => $μ,
            'SS'       => RandomVariable::sumOfSquares($all_elements),
            'variance' => Descriptive::sampleVariance($all_elements),
            'sd'       => Descriptive::sd($all_elements),
            'sem'      => RandomVariable::standardErrorOfTheMean($all_elements),
        ];

        // Sum of squares factor A
        $SSA = \array_sum(\array_map(
            function ($f1) use ($μ) {
                return $f1['n'] * ($f1['mean'] - $μ) ** 2;
            },
            $summary_A
        ));

        // Sum of squares factor B
        $SSB = \array_sum(\array_map(
            function ($B) use ($μ) {
                return $B['n'] * ($B['mean'] - $μ) ** 2;
            },
            $summary_B
        ));

        // Sum of squares within (error)
        $SSW = 0;
        foreach ($data as $A => $Bs) {
            foreach ($Bs as $B => $values) {
                foreach ($values as $value) {
                    $SSW += ($value - $summary_AB[$A][$B]['mean']) ** 2;
                }
            }
        }

        // Sum of squares total
        $SST = 0;
        foreach ($data as $A => $Bs) {
            foreach ($Bs as $B => $values) {
                foreach ($values as $value) {
                    $SST += ($value - $μ) ** 2;
                }
            }
        }

        // Sum of squares AB interaction
        $SSAB = $SST - $SSA - $SSB - $SSW;

        // Degrees of freedom
        $dfA  = $r - 1;
        $dfB  = $c - 1;
        $dfAB = ($r - 1) * ($c - 1);
        $dfW  = $summary_total['n'] - ($r * $c);
        $dfT  = $summary_total['n'] - 1;

        // Mean squares
        $MSA  = $SSA / $dfA;
        $MSB  = $SSB / $dfB;
        $MSAB = $SSAB / $dfAB;
        $MSW  = $SSW / $dfW;

        // F test statistics
        $FA  = $MSA / $MSW;
        $FB  = $MSB / $MSW;
        $FAB = $MSAB / $MSW;

        // P values
        $fDist1 = new F($dfA, $dfW);
        $fDist2 = new F($dfB, $dfW);
        $fDist3 = new F($dfAB, $dfW);
        $PA  = $fDist1->above($FA);
        $PB  = $fDist2->above($FB);
        $PAB = $fDist3->above($FAB);

        // Return ANOVA report
        return [
            'ANOVA' => [
                'factorA' => [
                    'SS' => $SSA,
                    'df' => $dfA,
                    'MS' => $MSA,
                    'F'  => $FA,
                    'P'  => $PA,
                ],
                'factorB' => [
                    'SS' => $SSB,
                    'df' => $dfB,
                    'MS' => $MSB,
                    'F'  => $FB,
                    'P'  => $PB,
                ],
                'interaction' => [
                    'SS' => $SSAB,
                    'df' => $dfAB,
                    'MS' => $MSAB,
                    'F'  => $FAB,
                    'P'  => $PAB,
                ],
                'error' => [
                    'SS' => $SSW,
                    'df' => $dfW,
                    'MS' => $MSW,
                ],
                'total' => [
                    'SS' => $SST,
                    'df' => $dfT,
                ],
            ],
            'total_summary'       => $summary_total,
            'summary_factorA'     => $summary_A,
            'summary_factorB'     => $summary_B,
            'summary_interaction' => $summary_AB,
        ];
    }
}
