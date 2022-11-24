<?php

namespace MathPHP\Statistics;

use MathPHP\Exception;
use MathPHP\Functions\Map\Single;
use MathPHP\Probability\Distribution\Continuous\StudentT;

/**
 * Tests for outliers in data
 *  - Grubbs' Test
 */
class Outlier
{
    public const ONE_SIDED       = 'one';
    public const TWO_SIDED       = 'two';
    public const ONE_SIDED_LOWER = 'lower';
    public const ONE_SIDED_UPPER = 'upper';

    /**
     * The Grubbs' Statistic (G) of a series of data
     *
     * G is the largest z-score for a set of data
     * The statistic can be calculated, looking at only the maximum value ("upper")
     * the minimum value ("lower"), or the data point with the largest residual ("two")
     *
     * https://en.wikipedia.org/wiki/Grubbs%27_test_for_outliers
     * https://www.itl.nist.gov/div898/handbook/eda/section3/eda35h1.htm
     *
     * Two-sided Grubbs' test statistic - largest difference from the mean is an outlier
     *
     *     max❘Yᵢ − μ❘
     * G = ----------
     *         σ
     *
     * One-sided Grubbs' test statistic - minimum value is an outlier
     *
     *     μ - Ymin
     * G = --------
     *        σ
     *
     * One-sided Grubbs' test statistic - maximum value is an outlier
     *
     *     Ymax - μ
     * G = --------
     *        σ
     *
     * @param float[] $data
     * @param string  $typeOfTest ("upper" "lower", or "two")
     *
     * @return float G (Grubb's test statistic)
     *
     * @throws Exception\BadDataException
     * @throws Exception\OutOfBoundsException
     * @throws Exception\BadParameterException if the type of test is not valid
     */
    public static function grubbsStatistic(array $data, string $typeOfTest = self::TWO_SIDED): float
    {
        $μ = Average::mean($data);
        $σ = Descriptive::standardDeviation($data);

        if ($typeOfTest === self::TWO_SIDED) {
            $max❘Yᵢ − μ❘ = \max(Single::abs(Single::subtract($data, $μ)));
            return $max❘Yᵢ − μ❘ / $σ;
        }

        if ($typeOfTest === self::ONE_SIDED_LOWER) {
            $yMin = \min($data);
            return ($μ - $yMin) / $σ;
        }

        if ($typeOfTest === self::ONE_SIDED_UPPER) {
            $yMax = \max($data);
            return ($yMax - $μ) / $σ;
        }

        throw new Exception\BadParameterException("{$typeOfTest} is not a valid Grubbs; test");
    }

    /**
     * The critical Grubbs Value
     *
     * The critical Grubbs' value is used to determine if a value in a set of data is likely to be an outlier.
     *
     * https://en.wikipedia.org/wiki/Grubbs%27_test_for_outliers
     * https://www.itl.nist.gov/div898/handbook/eda/section3/eda35h1.htm
     *
     *                                ___________
     *                   (n - 1)     /    T²
     * Critical value =  ------- \  / ----------
     *                     √n     \/  n - 2 + T²
     *
     * T = Critical value of the t distribution with (N-2) degrees of freedom and a significance level of α/(2N)
     *     For the one-sided tests, replace α/(2N) with α/N.
     *
     * @param float  $𝛼 Significance level
     * @param int    $n Size of the data set
     * @param string $typeOfTest ('one' or 'two') one or two-tailed test
     *
     * @return float
     *
     * @throws Exception\BadParameterException
     */
    public static function grubbsCriticalValue(float $𝛼, int $n, string $typeOfTest): float
    {
        self::validateGrubbsCriticalValueTestType($typeOfTest);

        $studentT = new StudentT($n - 2);

        $T = $typeOfTest === self::ONE_SIDED
            ? $studentT->inverse($𝛼 / $n)
            : $studentT->inverse($𝛼 / (2 * $n));

        return (($n - 1) / \sqrt($n)) * \sqrt($T ** 2 / ($n - 2 + $T ** 2));
    }

    /* ********************** *
     * PRIVATE HELPER METHODS
     * ********************** */

    /**
     * Validate the type of test is two sided, or one sided lower or upper
     *
     * @param string $typeOfTest
     *
     * @throws Exception\BadParameterException
     */
    private static function validateGrubbsCriticalValueTestType(string $typeOfTest)
    {
        if (!\in_array($typeOfTest, [self::ONE_SIDED, self::TWO_SIDED])) {
            throw new Exception\BadParameterException("{$typeOfTest} is not a valid Grubbs' test");
        }
    }
}
