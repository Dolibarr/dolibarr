<?php

namespace MathPHP\InformationTheory;

use MathPHP\Functions\Map;
use MathPHP\Exception;

/**
 * Functions dealing with information entropy in the field of statistical field of information thoery.
 *
 * - Entropy:
 *   - Shannon entropy (bits)
 *   - Shannon entropy (nats)
 *   - Shannon entropy (harts)
 *   - Cross entropy
 *
 * In information theory, entropy is the expected value (average) of the information contained in each message.
 *
 * https://en.wikipedia.org/wiki/Entropy_(information_theory)
 */
class Entropy
{
    private const ONE_TOLERANCE = 0.010001;

    /**
     * Shannon entropy (bit entropy)
     * The average minimum number of bits needed to encode a string of symbols, based on the probability of the symbols.
     * https://en.wikipedia.org/wiki/Entropy_(information_theory)
     *
     * H = -∑ pᵢlog₂(pᵢ)
     *
     * H is in shannons, or bits.
     *
     * @param  array $p probability distribution
     *
     * @return float average minimum number of bits
     *
     * @throws Exception\BadDataException if probability distribution p does not add up to 1
     */
    public static function shannonEntropy(array $p)
    {
        // Probability distribution must add up to 1.0
        if (\abs(\array_sum($p) - 1) > self::ONE_TOLERANCE) {
            throw new Exception\BadDataException('Probability distribution p must add up to 1; p adds up to: ' . \array_sum($p));
        }

        // Defensive measure against taking the log of 0 which would be -∞
        $p = \array_map(
            function ($pᵢ) {
                return $pᵢ == 0 ? 1e-15 : $pᵢ;
            },
            $p
        );

        // ∑ pᵢlog₂(pᵢ)
        $∑pᵢlog₂⟮pᵢ⟯ = \array_sum(\array_map(
            function ($pᵢ) {
                return $pᵢ * \log($pᵢ, 2);
            },
            $p
        ));

        return -$∑pᵢlog₂⟮pᵢ⟯;
    }

    /**
     * Shannon nat entropy (nat entropy)
     * The average minimum number of nats needed to encode a string of symbols, based on the probability of the symbols.
     * https://en.wikipedia.org/wiki/Entropy_(information_theory)
     *
     * H = -∑ pᵢln(pᵢ)
     *
     * H is in units of nats.
     * 1 nat = 1/ln(2) shannons or bits.
     * https://en.wikipedia.org/wiki/Nat_(unit)
     *
     * @param  array $p probability distribution
     *
     * @return float average minimum number of nats
     *
     * @throws Exception\BadDataException if probability distribution p does not add up to 1
     */
    public static function shannonNatEntropy(array $p)
    {
        // Probability distribution must add up to 1.0
        if (\abs(\array_sum($p) - 1) > self::ONE_TOLERANCE) {
            throw new Exception\BadDataException('Probability distribution p must add up to 1; p adds up to: ' . \array_sum($p));
        }

        // Defensive measure against taking the log of 0 which would be -∞
        $p = \array_map(
            function ($pᵢ) {
                return $pᵢ == 0 ? 1e-15 : $pᵢ;
            },
            $p
        );

        // ∑ pᵢln(pᵢ)
        $∑pᵢln⟮pᵢ⟯ = \array_sum(\array_map(
            function ($pᵢ) {
                return $pᵢ * \log($pᵢ);
            },
            $p
        ));

        return -$∑pᵢln⟮pᵢ⟯;
    }

    /**
     * Shannon hartley entropy (hartley entropy)
     * The average minimum number of hartleys needed to encode a string of symbols, based on the probability of the symbols.
     * https://en.wikipedia.org/wiki/Entropy_(information_theory)
     *
     * H = -∑ pᵢlog₁₀(pᵢ)
     *
     * H is in units of hartleys, or harts.
     * 1 hartley = log₂(10) bit = ln(10) nat, or approximately 3.322 Sh, or 2.303 nat.
     * https://en.wikipedia.org/wiki/Hartley_(unit)
     *
     * @param  array $p probability distribution
     *
     * @return float average minimum number of hartleys
     *
     * @throws Exception\BadDataException if probability distribution p does not add up to 1
     */
    public static function shannonHartleyEntropy(array $p)
    {
        // Probability distribution must add up to 1.0
        if (\abs(\array_sum($p) - 1) > self::ONE_TOLERANCE) {
            throw new Exception\BadDataException('Probability distribution p must add up to 1; p adds up to: ' . \array_sum($p));
        }

        // Defensive measure against taking the log of 0 which would be -∞
        $p = \array_map(
            function ($pᵢ) {
                return $pᵢ == 0 ? 1e-15 : $pᵢ;
            },
            $p
        );

        // ∑ pᵢlog₁₀(pᵢ)
        $∑pᵢlog₁₀⟮pᵢ⟯ = \array_sum(\array_map(
            function ($pᵢ) {
                return $pᵢ * \log10($pᵢ);
            },
            $p
        ));

        return -$∑pᵢlog₁₀⟮pᵢ⟯;
    }

    /**
     * Cross entropy
     * The cross entropy between two probability distributions p and q over the same underlying set of events
     * measures the average number of bits needed to identify an event drawn from the set, if a coding scheme
     * is used that is optimized for an "unnatural" probability distribution q, rather than the "true" distribution p.
     * https://en.wikipedia.org/wiki/Cross_entropy
     *
     * H(p,q) = -∑ p(x) log₂ q(x)
     *
     * @param array $p distribution p
     * @param array $q distribution q
     *
     * @return float entropy between distributions
     *
     * @throws Exception\BadDataException if p and q do not have the same number of elements
     * @throws Exception\BadDataException if p and q are not probability distributions that add up to 1
     */
    public static function crossEntropy(array $p, array $q)
    {
        // Arrays must have the same number of elements
        if (\count($p) !== \count($q)) {
            throw new Exception\BadDataException('p and q must have the same number of elements');
        }

        // Probability distributions must add up to 1.0
        if ((\abs(\array_sum($p) - 1) > self::ONE_TOLERANCE) || (\abs(\array_sum($q) - 1) > self::ONE_TOLERANCE)) {
            throw new Exception\BadDataException('Distributions p and q must add up to 1');
        }

        // Defensive measure against taking the log of 0 which would be -∞
        $q = \array_map(
            function ($qᵢ) {
                return $qᵢ == 0 ? 1e-15 : $qᵢ;
            },
            $q
        );

        // ∑ p(x) log₂ q(x)
        $∑plog₂⟮q⟯ = \array_sum(\array_map(
            function ($pᵢ, $qᵢ) {
                return $pᵢ * \log($qᵢ, 2);
            },
            $p,
            $q
        ));

        return -$∑plog₂⟮q⟯;
    }

    /**
     * Joint entropy (bits)
     * A measure of the uncertainty associated with a set of variables.
     * https://en.wikipedia.org/wiki/Joint_entropy
     *
     * H(X,Y) = -∑ ∑ P(x,y)log₂[P(x,y)]
     *           x y
     *
     * Where x and y are particular values of random variables X and Y, respectively,
     * and P(x,y) is the joint probability of these values occurring together.
     * H is in shannons, or bits.
     *
     * Joint entropy is basically just shannonEntropy but the probability distribution input
     * represents the probability of two variables happening at the same time.
     *
     * @param  array $P⟮x、y⟯ probability distribution of x and y occuring together
     *
     * @return float uncertainty
     *
     * @throws Exception\BadDataException if probability distribution $P⟮x、y⟯ does not add up to 1
     */
    public static function jointEntropy(array $P⟮x、y⟯)
    {
        return self::shannonEntropy($P⟮x、y⟯);
    }

    /**
     * Rényi entropy
     * Rényi entropy generalizes the Hartley entropy, the Shannon entropy, the collision entropy and the min entropy
     * https://en.wikipedia.org/wiki/R%C3%A9nyi_entropy
     *           1
     * Hₐ(X) = ----- log₂(∑ pᵢᵃ)
     *         1 - α
     *
     * α ≥ 0; α ≠ 1
     *
     * H is in shannons, or bits.
     *
     * @param  array  $p probability distribution
     * @param  number $α order α
     *
     * @return float
     *
     * @throws Exception\BadDataException if probability distribution p does not add up to 1
     * @throws Exception\OutOfBoundsException if α < 0 or α = 1
     */
    public static function renyiEntropy(array $p, $α)
    {
        // Probability distribution must add up to 1.0
        if (\abs(\array_sum($p) - 1) > self::ONE_TOLERANCE) {
            throw new Exception\BadDataException('Probability distribution p must add up to 1; p adds up to: ' . \array_sum($p));
        }

        // α ≥ 0; α ≠ 1
        if ($α < 0 || $α == 1) {
            throw new Exception\OutOfBoundsException("α must be ≥ 0 and ≠ 1 ");
        }

        // (1 / 1 - α) log (∑ pᵢᵃ)
        $Hₐ⟮X⟯ = (1 / (1 - $α)) * \log(\array_sum(Map\Single::pow($p, $α)), 2);

        return $Hₐ⟮X⟯;
    }

    /**
     * Perplexity
     * a measurement of how well a probability distribution or probability model predicts a sample.
     * It may be used to compare probability models.
     * A low perplexity indicates the probability distribution is good at predicting the sample.
     * https://en.wikipedia.org/wiki/Perplexity
     *
     * perplexity = 2ᴴ⁽ᵖ⁾ = 2^(-∑ pᵢlog₂(pᵢ))
     * where H(p) = entropy
     *
     * Perplexity is in shannons, or bits.
     *
     * @param  array $p probability distribution
     *
     * @return float perplexity
     *
     * @throws Exception\BadDataException if probability distribution p does not add up to 1
     */
    public static function perplexity(array $p)
    {
        // Probability distribution must add up to 1.0
        if (\abs(\array_sum($p) - 1) > self::ONE_TOLERANCE) {
            throw new Exception\BadDataException('Probability distribution p must add up to 1; p adds up to: ' . \array_sum($p));
        }

        // ∑ pᵢlog₂(pᵢ)
        $H⟮p⟯ = self::shannonEntropy($p);

        return 2 ** $H⟮p⟯;
    }
}
