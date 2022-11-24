<?php

namespace MathPHP\Statistics;

use MathPHP\Functions\Map;
use MathPHP\LinearAlgebra\NumericMatrix;
use MathPHP\Exception;
use MathPHP\LinearAlgebra\Vector;

/**
 * Functions dealing with statistical distance.
 * Related to probability and information theory and entropy.
 *
 * - Distances
 *   - Bhattacharyya
 *   - Hellinger
 *   - Mahalanobis
 *   - Jensen-Shannon
 *   - Minkowski
 *   - Euclidean
 *   - Manhattan
 *   - Cosine
 *   - Cosine similarity
 *   - Bray Curtis
 *   - Canberra
 *
 * In statistics, probability theory, and information theory, a statistical distance quantifies the distance between
 * two statistical objects, which can be two random variables, or two probability distributions or samples, or the
 * distance can be between an individual sample point and a population or a wider sample of points.
 *
 * https://en.wikipedia.org/wiki/Statistical_distance
 */
class Distance
{
    private const ONE_TOLERANCE = 0.010001;

    /**
     * Bhattacharyya distance
     * Measures the similarity of two discrete or continuous probability distributions.
     * https://en.wikipedia.org/wiki/Bhattacharyya_distance
     *
     * For probability distributions p and q over the same domain X,
     * the Bhattacharyya distance is defined as:
     *
     * DB(p,q) = -ln(BC(p,q))
     *
     * where BC is the Bhattacharyya coefficient:
     *
     * BC(p,q) = ∑ √(p(x) q(x))
     *          x∈X
     *
     * @param array $p distribution p
     * @param array $q distribution q
     *
     * @return float distance between distributions
     *
     * @throws Exception\BadDataException if p and q do not have the same number of elements
     * @throws Exception\BadDataException if p and q are not probability distributions that add up to 1
     */
    public static function bhattacharyya(array $p, array $q): float
    {
        // Arrays must have the same number of elements
        if (\count($p) !== \count($q)) {
            throw new Exception\BadDataException('p and q must have the same number of elements');
        }

        // Probability distributions must add up to 1.0
        if ((\abs(\array_sum($p) - 1) > self::ONE_TOLERANCE) || (\abs(\array_sum($q) - 1) > self::ONE_TOLERANCE)) {
            throw new Exception\BadDataException('Distributions p and q must add up to 1');
        }

        // ∑ √(p(x) q(x))
        $BC⟮p、q⟯ = \array_sum(Map\Single::sqrt(Map\Multi::multiply($p, $q)));

        return -\log($BC⟮p、q⟯);
    }

    /**
     * Hellinger distance
     * Used to quantify the similarity between two probability distributions. It is a type of f-divergence.
     * https://en.wikipedia.org/wiki/Hellinger_distance
     *
     *          1   _______________
     * H(P,Q) = -- √ ∑ (√pᵢ - √qᵢ)²
     *          √2
     *
     * @param array $p distribution p
     * @param array $q distribution q
     *
     * @return float difference between distributions
     *
     * @throws Exception\BadDataException if p and q do not have the same number of elements
     * @throws Exception\BadDataException if p and q are not probability distributions that add up to 1
     */
    public static function hellinger(array $p, array $q): float
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

        // √ ∑ (√pᵢ - √qᵢ)²
        $√∑⟮√pᵢ − √qᵢ⟯² = \sqrt(\array_sum(\array_map(
            function ($pᵢ, $qᵢ) {
                return (\sqrt($pᵢ) - \sqrt($qᵢ)) ** 2;
            },
            $p,
            $q
        )));

        return (1 / \sqrt(2)) * $√∑⟮√pᵢ − √qᵢ⟯²;
    }

    /**
     * Jensen-Shannon distance
     * Square root of the Jensen-Shannon divergence
     * https://en.wikipedia.org/wiki/Jensen%E2%80%93Shannon_divergence
     *
     *        _____________________
     *       / 1          1
     *  \   /  - D(P‖M) + - D(Q‖M)
     *   \/    2          2
     *
     *           1
     * where M = - (P + Q)
     *           2
     *
     * D(P‖Q) = Kullback-Leibler divergence
     *
     * @param array $p distribution p
     * @param array $q distribution q
     *
     * @return float
     *
     * @throws Exception\BadDataException if p and q do not have the same number of elements
     * @throws Exception\BadDataException if p and q are not probability distributions that add up to 1
     */
    public static function jensenShannon(array $p, array $q): float
    {
        return \sqrt(Divergence::jensenShannon($p, $q));
    }

    /**
     * Mahalanobis distance
     *
     * https://en.wikipedia.org/wiki/Mahalanobis_distance
     *
     * The Mahalanobis distance measures the distance between two points in multidimensional
     * space, scaled by the standard deviation of the data in each dimension.
     *
     * If x and y are vectors of points in space, and S is the covariance matrix of that space,
     * the Mahalanobis distance, D, of the point within the space is:
     *
     *    D = √[(x-y)ᵀ S⁻¹ (x-y)]
     *
     * If y is not provided, the distances will be calculated from x to the centroid of the dataset.
     *
     * The Mahalanobis distance can also be used to measure the distance between two sets of data.
     * If x has more than one column, the combined data covariance matrix is used, and the distance
     * will be calculated between the centroids of each data set.
     *
     * @param NumericMatrix      $x    a vector in the vector space. ie [[1],[2],[4]] or a matrix of data
     * @param NumericMatrix      $data an array of data. i.e. [[1,2,3,4],[6,2,8,1],[0,4,8,1]]
     * @param NumericMatrix|null $y    a vector in the vector space
     *
     * @return float Mahalanobis Distance
     *
     * @throws Exception\BadParameterException
     * @throws Exception\IncorrectTypeException
     * @throws Exception\MatrixException
     * @throws Exception\OutOfBoundsException
     * @throws Exception\VectorException
     */
    public static function mahalanobis(NumericMatrix $x, NumericMatrix $data, NumericMatrix $y = null): float
    {
        $Centroid = $data->rowMeans()->asColumnMatrix();
        $Nx       = $x->getN();

        if ($Nx > 1) {
            // Combined covariance Matrix
            $S = $data->augment($x)->covarianceMatrix();
            $diff = $x->rowMeans()->asColumnMatrix()->subtract($Centroid);
        } else {
            $S = $data->covarianceMatrix();
            if ($y === null) {
                $y = $Centroid;
            }
            $diff = $x->subtract($y);
        }

        $S⁻¹ = $S->inverse();
        $D   = $diff->transpose()->multiply($S⁻¹)->multiply($diff);
        return \sqrt($D[0][0]);
    }

    /**
     * Minkowski distance
     *
     * https://en.wikipedia.org/wiki/Minkowski_distance
     *
     * (Σ|xᵢ - yᵢ|ᵖ)¹/ᵖ
     *
     * @param float[] $xs input array
     * @param float[] $ys input array
     * @param int     $p  order of the norm of the difference
     *
     * @return float
     *
     * @throws Exception\BadDataException if p and q do not have the same number of elements
     */
    public static function minkowski(array $xs, array $ys, int $p): float
    {
        // Arrays must have the same number of elements
        $n = \count($xs);
        if ($n !== \count($ys)) {
            throw new Exception\BadDataException('x and y must have the same number of elements');
        }
        if ($p < 1) {
            throw new Exception\BadDataException("p must be ≥ 1. Given $p");
        }

        $∑｜xᵢ − yᵢ⟯ᵖ = \array_sum(
            \array_map(
                function ($x, $y) use ($p) {
                    return \abs($x - $y) ** $p;
                },
                $xs,
                $ys
            )
        );

        return $∑｜xᵢ − yᵢ⟯ᵖ ** (1 / $p);
    }

    /**
     * Euclidean distance
     *
     * https://en.wikipedia.org/wiki/Euclidean_distance
     *
     * A generalized term for the Euclidean norm is the L² norm or L² distance.
     *
     * (Σ|xᵢ - yᵢ|²)¹/²
     *
     * @param float[] $xs input array
     * @param float[] $ys input array
     *
     * @return float
     *
     * @throws Exception\BadDataException if p and q do not have the same number of elements
     */
    public static function euclidean(array $xs, array $ys): float
    {
        return self::minkowski($xs, $ys, 2);
    }

    /**
     * Manhattan distance (Taxicab geometry)
     *
     * https://en.wikipedia.org/wiki/Taxicab_geometry
     *
     * The taxicab metric is also known as rectilinear distance, L₁ distance, L¹ distance , snake distance, city block
     * distance, Manhattan distance or Manhattan length, with corresponding variations in the name of the geometry.
     *
     * Σ|xᵢ - yᵢ|
     *
     * @param float[] $xs input array
     * @param float[] $ys input array
     *
     * @return float
     *
     * @throws Exception\BadDataException if p and q do not have the same number of elements
     */
    public static function manhattan(array $xs, array $ys): float
    {
        return self::minkowski($xs, $ys, 1);
    }

    /**
     * Cosine distance
     *
     *        A⋅B
     * 1 - ---------
     *     ‖A‖₂⋅‖B‖₂
     *
     *  where
     *    A⋅B is the dot product of A and B
     *    ‖A‖₂ is the L² norm of A
     *    ‖B‖₂ is the L² norm of B
     *
     * Similar to Python: scipy.spatial.distance.cosine(u, v, w=None)
     *
     * @param float[] $A
     * @param float[] $B
     *
     * @return float
     *
     * @throws Exception\BadDataException if null vector passed in
     * @throws Exception\VectorException
     */
    public static function cosine(array $A, array $B): float
    {
        if (\count(\array_unique($A)) === 1 && \end($A) == 0) {
            throw new Exception\BadDataException('A is the null vector');
        }
        if (\count(\array_unique($B)) === 1 && \end($B) == 0) {
            throw new Exception\BadDataException('B is the null vector');
        }

        $A = new Vector($A);
        $B = new Vector($B);

        $A⋅B       = $A->dotProduct($B);
        $‖A‖₂⋅‖B‖₂ = $A->l2Norm() * $B->l2Norm();

        return 1 - ($A⋅B / $‖A‖₂⋅‖B‖₂);
    }

    /**
     * Cosine similarity
     * A measure of similarity between two non-zero vectors of an inner product space.
     * Defined to equal the cosine of the angle between them, which is also the same as the inner product of the same
     * vectors normalized to both have length 1.
     *
     *            A⋅B
     * cos α = ---------
     *         ‖A‖₂⋅‖B‖₂
     *
     *  where
     *    A⋅B is the dot product of A and B
     *    ‖A‖₂ is the L² norm of A
     *    ‖B‖₂ is the L² norm of B
     *
     * Similar to Python: 1 - scipy.spatial.distance.cosine(u, v, w=None)
     *
     * @param float[] $A
     * @param float[] $B
     *
     * @return float
     *
     * @throws Exception\BadDataException if null vector passed in
     * @throws Exception\VectorException
     */
    public static function cosineSimilarity(array $A, array $B): float
    {
        return 1 - self::cosine($A, $B);
    }

    /**
     * Bray Curtis Distance
     *
     * https://docs.scipy.org/doc/scipy/reference/generated/scipy.spatial.distance.braycurtis.html#scipy.spatial.distance.braycurtis
     *
     *  ∑｜uᵢ − vᵢ｜
     *  -----------
     *  ∑｜uᵢ + vᵢ｜
     *
     * @param array $u
     * @param array $v
     *
     * @return float
     */
    public static function brayCurtis(array $u, array $v): float
    {
        if (\count($u) !== \count($v)) {
            throw new Exception\BadDataException('u and v must have the same number of elements');
        }
        $uZero = \count(\array_unique($u)) === 1 && \end($u) == 0;
        $vZero = \count(\array_unique($u)) === 1 && \end($v) == 0;
        if ($uZero && $vZero) {
            return \NAN;
        }

        $∑｜uᵢ − vᵢ｜ = \array_sum(\array_map(
            function (float $uᵢ, float $vᵢ) {
                return \abs($uᵢ - $vᵢ);
            },
            $u,
            $v
        ));
        $∑｜uᵢ ＋ vᵢ｜ = \array_sum(\array_map(
            function (float $uᵢ, float $vᵢ) {
                return \abs($uᵢ + $vᵢ);
            },
            $u,
            $v
        ));

        if ($∑｜uᵢ ＋ vᵢ｜ == 0) {
            return \NAN;
        }

        return $∑｜uᵢ − vᵢ｜ / $∑｜uᵢ ＋ vᵢ｜;
    }

    /**
     * Canberra Distance
     * A numerical measure of the distance between pairs of points in a vector space
     * It is a weighted version of L₁ (Manhattan) distance.
     *
     * https://en.wikipedia.org/wiki/Canberra_distance
     * http://www.code10.info/index.php?option=com_content&view=article&id=49:article_canberra-distance&catid=38:cat_coding_algorithms_data-similarity&Itemid=57
     *
     *              ｜pᵢ − qᵢ｜
     * d(p,q) = ∑ --------------
     *            ｜pᵢ｜ + ｜qᵢ｜
     *
     * @param array $p
     * @param array $q
     *
     * @return float
     */
    public static function canberra(array $p, array $q): float
    {
        if (\count($p) !== \count($q)) {
            throw new Exception\BadDataException('p and q must have the same number of elements');
        }
        $pZero = \count(\array_unique($p)) === 1 && \end($p) == 0;
        $qZero = \count(\array_unique($p)) === 1 && \end($q) == 0;
        if ($pZero && $qZero) {
            return \NAN;
        }

        // Numerators ｜pᵢ − qᵢ｜
        $｜p − q｜ = \array_map(
            function (float $pᵢ, float $qᵢ) {
                return \abs($pᵢ - $qᵢ);
            },
            $p,
            $q
        );
        // Denominators ｜pᵢ｜ + ｜qᵢ｜
        $｜p｜ ＋ ｜q｜ = \array_map(
            function (float $p, float $q) {
                return \abs($p) + \abs($q);
            },
            $p,
            $q
        );

        // Sum of quotients with non-zero denominators
        //    ｜pᵢ − qᵢ｜
        // ∑ --------------
        //   ｜pᵢ｜ + ｜qᵢ｜
        return \array_sum(\array_map(
            function (float $｜pᵢ − qᵢ｜, float $｜pᵢ｜ ＋ ｜qᵢ｜) {
                return $｜pᵢ｜ ＋ ｜qᵢ｜ == 0
                    ? 0
                    : $｜pᵢ − qᵢ｜ / $｜pᵢ｜ ＋ ｜qᵢ｜;
            },
            $｜p − q｜,
            $｜p｜ ＋ ｜q｜
        ));
    }
}
