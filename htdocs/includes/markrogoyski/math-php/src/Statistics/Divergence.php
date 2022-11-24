<?php

namespace MathPHP\Statistics;

use MathPHP\Exception;

/**
 * Functions dealing with statistical divergence.
 * Related to probability and information theory and entropy.
 *
 * - Divergences
 *   - Kullback-Leibler
 *   - Jensen-Shannon
 *
 * In statistics and information geometry, divergence or a contrast function is a function which establishes the "distance"
 * of one probability distribution to the other on a statistical manifold. The divergence is a weaker notion than that of
 * the distance, in particular the divergence need not be symmetric (that is, in general the divergence from p to q is not
 * equal to the divergence from q to p), and need not satisfy the triangle inequality.
 *
 * https://en.wikipedia.org/wiki/Divergence_(statistics)
 */
class Divergence
{
    private const ONE_TOLERANCE = 0.010001;

    /**
     * Kullback-Leibler divergence
     * (also known as: discrimination information, information divergence, information gain, relative entropy, KLIC, KL divergence)
     * A measure of the difference between two probability distributions P and Q.
     * https://en.wikipedia.org/wiki/Kullback%E2%80%93Leibler_divergence
     *
     *                       P(i)
     * Dkl(P‖Q) = ∑ P(i) log ----
     *            ⁱ          Q(i)
     *
     *
     *
     * @param  array  $p distribution p
     * @param  array  $q distribution q
     *
     * @return float difference between distributions
     *
     * @throws Exception\BadDataException if p and q do not have the same number of elements
     * @throws Exception\BadDataException if p and q are not probability distributions that add up to 1
     */
    public static function kullbackLeibler(array $p, array $q): float
    {
        // Arrays must have the same number of elements
        if (\count($p) !== \count($q)) {
            throw new Exception\BadDataException('p and q must have the same number of elements');
        }

        // Probability distributions must add up to 1.0
        if ((\abs(\array_sum($p) - 1) > self::ONE_TOLERANCE) || (\abs(\array_sum($q) - 1) > self::ONE_TOLERANCE)) {
            throw new Exception\BadDataException('Distributions p and q must add up to 1');
        }

        // Defensive measures against taking the log of 0 which would be -∞ or dividing by 0
        $p = \array_map(
            function ($pᵢ) {
                return $pᵢ == 0 ? 1e-15 : $pᵢ;
            },
            $p
        );
        $q = \array_map(
            function ($qᵢ) {
                return $qᵢ == 0 ? 1e-15 : $qᵢ;
            },
            $q
        );

        // ∑ P(i) log(P(i)/Q(i))
        $Dkl⟮P‖Q⟯ = \array_sum(\array_map(
            function ($P, $Q) {
                return $P * \log($P / $Q);
            },
            $p,
            $q
        ));

        return $Dkl⟮P‖Q⟯;
    }

    /**
     * Jensen-Shannon divergence
     * Also known as: information radius (IRad) or total divergence to the average.
     * A method of measuring the similarity between two probability distributions.
     * It is based on the Kullback–Leibler divergence, with some notable (and useful) differences,
     * including that it is symmetric and it is always a finite value.
     * https://en.wikipedia.org/wiki/Jensen%E2%80%93Shannon_divergence
     *
     *            1          1
     * JSD(P‖Q) = - D(P‖M) + - D(Q‖M)
     *            2          2
     *
     *           1
     * where M = - (P + Q)
     *           2
     *
     *       D(P‖Q) = Kullback-Leibler divergence
     *
     * @param array $p distribution p
     * @param array $q distribution q
     *
     * @return float difference between distributions
     *
     * @throws Exception\BadDataException if p and q do not have the same number of elements
     * @throws Exception\BadDataException if p and q are not probability distributions that add up to 1
     */
    public static function jensenShannon(array $p, array $q): float
    {
        // Arrays must have the same number of elements
        if (\count($p) !== \count($q)) {
            throw new Exception\BadDataException('p and q must have the same number of elements');
        }

        // Probability distributions must add up to 1.0
        if ((\abs(\array_sum($p) - 1) > self::ONE_TOLERANCE) || (\abs(\array_sum($q) - 1) > self::ONE_TOLERANCE)) {
            throw new Exception\BadDataException('Distributions p and q must add up to 1');
        }

        $M = \array_map(
            function ($pᵢ, $qᵢ) {
                return ($pᵢ + $qᵢ) / 2;
            },
            $p,
            $q
        );

        $½D⟮P‖M⟯ = self::kullbackLeibler($p, $M) / 2;
        $½D⟮Q‖M⟯ = self::kullbackLeibler($q, $M) / 2;

        return $½D⟮P‖M⟯ + $½D⟮Q‖M⟯;
    }
}
