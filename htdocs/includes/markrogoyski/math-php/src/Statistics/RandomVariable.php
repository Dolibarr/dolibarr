<?php

namespace MathPHP\Statistics;

use MathPHP\Probability\Distribution\Table;
use MathPHP\Functions\Map;
use MathPHP\Exception;

/**
 * Functions dealing with random variables.
 *
 * - Central moment
 * - Skewness
 * - Kurtosis
 * - Standard Error of the Mean (SEM)
 * - Confidence interval
 *
 * In probability and statistics, a random variable is a variable whose
 * value is subject to variations due to chance.
 * A random variable can take on a set of possible different values
 * (similarly to other mathematical variables), each with an associated
 * probability, in contrast to other mathematical variables.
 *
 * The mathematical function describing the possible values of a random
 * variable and their associated probabilities is known as a probability
 * distribution. Random variables can be discrete, that is, taking any of a
 * specified finite or countable list of values, endowed with a probability
 * mass function, characteristic of a probability distribution; or
 * continuous, taking any numerical value in an interval or collection of
 * intervals, via a probability density function that is characteristic of
 * a probability distribution; or a mixture of both types.
 *
 * https://en.wikipedia.org/wiki/Random_variable
 */
class RandomVariable
{
    public const SAMPLE_SKEWNESS      = 'sample';
    public const POPULATION_SKEWNESS  = 'population';
    public const ALTERNATIVE_SKEWNESS = 'alternative';

    public const SAMPLE_KURTOSIS     = 'sample';
    public const POPULATION_KURTOSIS = 'population';

    /**
     * n-th Central moment
     * A moment of a probability distribution of a random variable about the random variable's mean.
     * It is the expected value of a specified integer power of the deviation of the random variable from the mean.
     * https://en.wikipedia.org/wiki/Central_moment
     *
     *      ∑⟮xᵢ - μ⟯ⁿ
     * μn = ----------
     *          N
     *
     * @param float[] $X list of numbers (random variable X)
     * @param int     $n n-th central moment to calculate
     *
     * @return float n-th central moment
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function centralMoment(array $X, int $n): float
    {
        if (empty($X)) {
            throw new Exception\BadDataException('Cannot find the central moment of an empty list of numbers');
        }

        $μ         = Average::mean($X);
        $∑⟮xᵢ − μ⟯ⁿ = \array_sum(\array_map(
            function ($xᵢ) use ($μ, $n) {
                return \pow(($xᵢ - $μ), $n);
            },
            $X
        ));
        $N = \count($X);

        return $∑⟮xᵢ − μ⟯ⁿ / $N;
    }

    /**
     * Population skewness
     * A measure of the asymmetry of the probability distribution of a real-valued random variable about its mean.
     * https://en.wikipedia.org/wiki/Skewness
     * http://brownmath.com/stat/shape.htm
     *
     * This method tends to match Excel's SKEW.P function.
     * R (e1071) describes it as the typical definition used in many older textbooks (skewness type 1).
     *
     *         μ₃
     * γ₁ = -------
     *       μ₂³′²
     *
     * μ₂ is the second central moment
     * μ₃ is the third central moment
     *
     * @param float[] $X list of numbers (random variable X)
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function populationSkewness(array $X): float
    {
        if (empty($X)) {
            throw new Exception\BadDataException('Cannot find the population skewness of an empty list of numbers');
        }

        $μ₃ = self::centralMoment($X, 3);
        $μ₂ = self::centralMoment($X, 2);

        $μ₂³′² = \pow($μ₂, 3 / 2);
        if ($μ₂³′² == 0) {
            return \NAN;  // Prevents division by zero in μ₃ / μ₂³′² equation
        }

        return ($μ₃ /  $μ₂³′²);
    }

    /**
     * Sample skewness
     * A measure of the asymmetry of the probability distribution of a real-valued random variable about its mean.
     * https://en.wikipedia.org/wiki/Skewness
     * http://brownmath.com/stat/shape.htm
     *
     * This method tends to match Excel's SKEW function.
     * It also matches what is used in SAS and SPSS. In R (e1071) it is skewness type 2.
     *
     *         μ₃     √(n(n - 1))
     * γ₁ = ------- × -----------
     *       μ₂³′²       n - 2
     *
     * μ₂ is the second central moment
     * μ₃ is the third central moment
     * n is the sample size
     *
     * @param float[] $X list of numbers (random variable X)
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers has less than 3 elements
     */
    public static function sampleSkewness(array $X): float
    {
        $n = \count($X);
        if ($n < 3) {
            throw new Exception\BadDataException('Cannot find the sample skewness of less than three numbers');
        }

        $μ₃    = self::centralMoment($X, 3);
        $μ₂    = self::centralMoment($X, 2);

        $μ₂³′² = \pow($μ₂, 3 / 2);
        if ($μ₂³′² == 0) {
            return \NAN;  // Prevents division by zero in μ₃ / μ₂³′² equation
        }

        $√⟮n⟮n − 1⟯⟯ = \sqrt($n * ($n - 1));

        return ($μ₃ / $μ₂³′²) * ( $√⟮n⟮n − 1⟯⟯ / ($n - 2) );
    }

    /**
     * Skewness (alternative method)
     * Classic definition of skewness. This method tends to match most of the online skewness calculators and examples.
     * https://en.wikipedia.org/wiki/Skewness
     *
     *         1     ∑⟮xᵢ - μ⟯³
     * γ₁ =  ----- × ---------
     *       N - 1       σ³
     *
     * μ is the mean
     * σ³ is the standard deviation cubed, or, the variance raised to the 3/2 power.
     * N is the sample size
     *
     * @param float[] $X list of numbers (random variable X)
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException
     *
     * @throws Exception\BadDataException if the input array of numbers has less than 2 elements
     */
    public static function alternativeSkewness(array $X): float
    {
        $N  = \count($X);
        if ($N < 2) {
            throw new Exception\BadDataException('Cannot find the skewness of less than two numbers');
        }

        $μ         = Average::mean($X);
        $∑⟮xᵢ − μ⟯³ = \array_sum(\array_map(
            function ($xᵢ) use ($μ) {
                return \pow(($xᵢ - $μ), 3);
            },
            $X
        ));
        $σ³ = \pow(Descriptive::standardDeviation($X, Descriptive::SAMPLE), 3);

        $⟮σ³ × ⟮N − 1⟯⟯ = ($σ³ * ($N - 1));
        if ($⟮σ³ × ⟮N − 1⟯⟯ == 0) {
            return \NAN;
        }

        return $∑⟮xᵢ − μ⟯³ / $⟮σ³ × ⟮N − 1⟯⟯;
    }

    /**
     * Skewness
     *
     * Multiple algorithms exist to compute skewness.
     * The default is sample skewness, which will match Excel's SKEW function and SAS and SPSS. In R (e1071) it is skewness type 2.
     *
     * To use a different skewness algorithm provide the optional type parameter:
     *  - SAMPLE_SKEWNESS (Excel's SKEW function and SAS and SPSS)
     *  - POPULATION_SKEWNESS (Textbook definition; Excel's SKEW.P function)
     *  - ALTERNATIVE_SKEWNESS (Another textbook definition)
     *
     * @param float[] $X list of numbers (random variable X)
     * @param string $type (optional) determines the skewness algorithm used (SAMPLE_SKEWNESS (default), POPULATION_SKEWNESS, ALTERNATIVE_SKEWNESS)
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers has less than 2 elements
     * @throws Exception\IncorrectTypeException
     * @throws Exception\OutOfBoundsException
     */
    public static function skewness(array $X, string $type = self::SAMPLE_SKEWNESS): float
    {
        switch ($type) {
            case self::SAMPLE_SKEWNESS:
                return self::sampleSkewness($X);

            case self::POPULATION_SKEWNESS:
                return self::populationSkewness($X);

            case self::ALTERNATIVE_SKEWNESS:
                return self::alternativeSkewness($X);

            default:
                throw new Exception\IncorrectTypeException("Type $type is not a valid skewness algorithm type");
        }
    }

    /**
     * Standard Error of Skewness (SES)
     *
     *         _____________________
     *        /      6n(n - 1)
     * SES = / --------------------
     *      √  (n - 2)(n + 1)(n + 3)
     *
     * @param int $n Sample size
     *
     * @return float
     *
     * @throws Exception\BadDataException if n < 3
     */
    public static function ses(int $n): float
    {
        if ($n < 3) {
            throw new Exception\BadDataException("SES requires a dataset of n > 2. N of $n given.");
        }

        $６n⟮n − 1⟯           = 6 * $n * ($n - 1);
        $⟮n − 2⟯⟮n ＋ 1⟯⟮n ＋ 2⟯ = ($n - 2) * ($n + 1) * ($n + 3);

        return \sqrt($６n⟮n − 1⟯ / $⟮n − 2⟯⟮n ＋ 1⟯⟮n ＋ 2⟯);
    }

    /**
     * Sample Excess Kurtosis
     * A measure of the "tailedness" of the probability distribution of a real-valued random variable.
     * https://en.wikipedia.org/wiki/Kurtosis
     *
     *       μ₄
     * γ₂ = ---- − 3
     *       μ₂²
     *
     * μ₂ is the second central moment
     * μ₄ is the fourth central moment
     *
     * This is the typical definition used in textbooks. In R (e1071) it is kurtosis type 1.
     *
     * @param float[] $X list of numbers (random variable X)
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function sampleKurtosis(array $X): float
    {
        if (empty($X)) {
            throw new Exception\BadDataException('Cannot find the kurtosis of an empty list of numbers');
        }

        $μ₄  = self::centralMoment($X, 4);
        $μ₂² = \pow(self::centralMoment($X, 2), 2);

        if ($μ₂² == 0) {
            return \NAN;
        }

        return ( $μ₄ / $μ₂² ) - 3;
    }

    /**
     * Population Excess Kurtosis
     * A measure of the "tailedness" of the probability distribution of a real-valued random variable.
     * https://en.wikipedia.org/wiki/Kurtosis
     *
     *                          (n - 1)
     * G₂ = [(n + 1) g₂ + 6] --------------
     *                       (n - 2)(n - 3)
     *
     *                                    μ₄
     * where g₂ is the sample kurtotis = ---- − 3
     *                                    μ₂²
     *
     *
     * This is the common application version of kurtosis, used in Excel, SAS and SPSS.
     * In R (e1071) it is kurtosis type 2. Excel's KURT function.
     *
     * @param float[] $X list of numbers (random variable X)
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty or has fewer than four elements
     */
    public static function populationKurtosis(array $X): float
    {
        if (\count($X) < 4) {
            throw new Exception\BadDataException('Cannot find the kurtosis of an empty list of numbers');
        }

        $g₂ = self::sampleKurtosis($X);

        $n = \count($X);
        $⟮n ＋ 1⟯g₂ ＋ 6 = ($n + 1) * $g₂ + 6;

        return ($⟮n ＋ 1⟯g₂ ＋ 6 * ($n - 1)) / (($n - 2) * ($n - 3));
    }

    /**
     * Sample Excess Kurtosis
     * A measure of the "tailedness" of the probability distribution of a real-valued random variable.
     * https://en.wikipedia.org/wiki/Kurtosis
     *
     *       μ₄
     * γ₂ = ---- − 3
     *       μ₂²
     *
     * μ₂ is the second central moment
     * μ₄ is the fourth central moment
     *
     * This is the typical definition used in textbooks. In R (e1071) it is kurtosis type 1.
     *
     * @param float[] $X list of numbers (random variable X)
     * @param string $type (optional) determines the kurtsosis algorithm used (POPULATION_KURTOSIS (default), SAMPLE_KURTOSIS)
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     * @throws Exception\IncorrectTypeException
     */
    public static function kurtosis(array $X, string $type = self::POPULATION_KURTOSIS): float
    {
        switch ($type) {
            case self::SAMPLE_KURTOSIS:
                return self::sampleKurtosis($X);

            case self::POPULATION_KURTOSIS:
                return self::populationKurtosis($X);

            default:
                throw new Exception\IncorrectTypeException("Type $type is not a valid kurtosis algorithm type");
        }
    }

    /**
     * Is the kurtosis negative? (Platykurtic)
     * Indicates a flat distribution.
     *
     * @param array $X list of numbers (random variable X)
     * @param string $type (optional) determines the kurtsosis algorithm used (POPULATION_KURTOSIS (default), SAMPLE_KURTOSIS)
     *
     * @return bool true if platykurtic
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function isPlatykurtic(array $X, string $type = self::POPULATION_KURTOSIS): bool
    {
        return self::kurtosis($X, $type) < 0;
    }

    /**
     * Is the kurtosis postive? (Leptokurtic)
     * Indicates a peaked distribution.
     *
     * @param array $X list of numbers (random variable X)
     * @param string $type (optional) determines the kurtsosis algorithm used (POPULATION_KURTOSIS (default), SAMPLE_KURTOSIS)
     *
     * @return bool true if leptokurtic
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function isLeptokurtic(array $X, string $type = self::POPULATION_KURTOSIS): bool
    {
        return self::kurtosis($X, $type) > 0;
    }

    /**
     * Is the kurtosis zero? (Mesokurtic)
     * Indicates a normal distribution.
     *
     * @param array $X list of numbers (random variable X)
     * @param string $type (optional) determines the kurtsosis algorithm used (POPULATION_KURTOSIS (default), SAMPLE_KURTOSIS)
     *
     * @return bool true if mesokurtic
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function isMesokurtic(array $X, string $type = self::POPULATION_KURTOSIS): bool
    {
        return self::kurtosis($X, $type) == 0;
    }

    /**
     * Standard Error of Kurtosis (SEK)
     *
     *                ______________
     *               /    (n² - 1)
     * SEK = 2(SES) / --------------
     *             √  (n - 3)(n + 5)
     *
     * @param int $n Sample size
     *
     * @return float
     *
     * @throws Exception\BadDataException if n < 4
     */
    public static function sek(int $n): float
    {
        if ($n < 4) {
            throw new Exception\BadDataException("SEK requires a dataset of n > 3. N of $n given.");
        }

        $２⟮SES⟯        = 2 * self::ses($n);
        $⟮n² − 1⟯       = $n ** 2 - 1;
        $⟮n − 3⟯⟮n ＋ 5⟯ = ($n - 3) * ($n + 5);

        return $２⟮SES⟯ * \sqrt($⟮n² − 1⟯ / $⟮n − 3⟯⟮n ＋ 5⟯);
    }

    /**
     * Standard error of the mean (SEM)
     * The standard deviation of the sample-mean's estimate of a population mean.
     * https://en.wikipedia.org/wiki/Standard_error
     *
     *       s
     * SEₓ = --
     *       √n
     *
     * s = sample standard deviation
     * n = size (number of observations) of the sample
     *
     * @param float[] $X list of numbers (random variable X)
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function standardErrorOfTheMean(array $X): float
    {
        if (empty($X)) {
            throw new Exception\BadDataException('Cannot find the SEM of an empty list of numbers');
        }

        $s  = Descriptive::standardDeviation($X, Descriptive::SAMPLE);
        $√n = \sqrt(\count($X));
        return $s / $√n;
    }

    /**
     * SEM - Convenience method for standard error of the mean
     *
     * @param array $X list of numbers (random variable X)
     *
     * @return float
     *
     * @throws Exception\OutOfBoundsException
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function sem(array $X): float
    {
        return self::standardErrorOfTheMean($X);
    }

    /**
     * Confidence interval
     * Finds CI given a sample mean, sample size, and standard deviation.
     * Uses Z score.
     * https://en.wikipedia.org/wiki/Confidence_interval
     *          σ
     * ci = z* --
     *         √n
     *
     * interval = (μ - ci, μ + ci)
     *
     * Available confidence levels: See Probability\StandardNormalTable::Z_SCORES_FOR_CONFIDENCE_INTERVALS
     *
     * @param float  $μ sample mean
     * @param int    $n sample size
     * @param float  $σ standard deviation
     * @param string $cl confidence level (Ex: 95, 99, 99.5, 99.9, etc.)
     *
     * @return array [ ci, lower_bound, upper_bound ]
     *
     * @throws Exception\BadDataException
     */
    public static function confidenceInterval(float $μ, int $n, float $σ, string $cl): array
    {
        if ($n === 0) {
            return ['ci' => null, 'lower_bound' => null, 'upper_bound' => null];
        }

        $z = Table\StandardNormal::getZScoreForConfidenceInterval($cl);

        $ci = $z * ($σ / \sqrt($n));

        $lower_bound = $μ - $ci;
        $upper_bound = $μ + $ci;

        return [
            'ci'          => $ci,
            'lower_bound' => $lower_bound,
            'upper_bound' => $upper_bound,
        ];
    }

    /**
     * Sum of squares
     *
     * ∑⟮xᵢ⟯²
     *
     * @param float[] $numbers
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function sumOfSquares(array $numbers): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the sum of squares of an empty list of numbers');
        }

         $∑⟮xᵢ⟯² = \array_sum(Map\Single::square($numbers));

         return $∑⟮xᵢ⟯²;
    }

    /**
     * Sum of squares deviations
     *
     * ∑⟮xᵢ - μ⟯²
     *
     * @param  float[] $numbers
     *
     * @return float
     *
     * @throws Exception\BadDataException if the input array of numbers is empty
     */
    public static function sumOfSquaresDeviations(array $numbers): float
    {
        if (empty($numbers)) {
            throw new Exception\BadDataException('Cannot find the sum of squares deviations of an empty list of numbers');
        }

        $μ         = Average::mean($numbers);
        $∑⟮xᵢ − μ⟯² = \array_sum(\array_map(
            function ($xᵢ) use ($μ) {
                return \pow(($xᵢ - $μ), 2);
            },
            $numbers
        ));

        return $∑⟮xᵢ − μ⟯²;
    }
}
