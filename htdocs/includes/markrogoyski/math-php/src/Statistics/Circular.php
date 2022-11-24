<?php

namespace MathPHP\Statistics;

/**
 * Circular statistics (directional statistics)
 * https://en.wikipedia.org/wiki/Directional_statistics
 * https://ncss-wpengine.netdna-ssl.com/wp-content/themes/ncss/pdf/Procedures/NCSS/Circular_Data_Analysis.pdf
 */
class Circular
{
    /**
     * Mean of circular quantities (circular mean)
     * Mean direction of circular data.
     * A mean which is sometimes better-suited for quantities like angles, daytimes, and fractional parts of real numbers.
     * https://en.wikipedia.org/wiki/Mean_of_circular_quantities
     * _
     * α = atan2(∑sin αⱼ, ∑cos αⱼ)
     *
     * @param  array $angles
     *
     * @return float mean direction of circular data
     */
    public static function mean(array $angles): float
    {
        $∑sinαⱼ = \array_sum(\array_map(
            function ($αⱼ) {
                return \sin($αⱼ);
            },
            $angles
        ));
        $∑cosαⱼ = \array_sum(\array_map(
            function ($αⱼ) {
                return \cos($αⱼ);
            },
            $angles
        ));

        return \atan2($∑sinαⱼ, $∑cosαⱼ);
    }

    /**
     * Resultant length (R)
     * https://en.wikipedia.org/wiki/Directional_statistics#Moments
     * https://ncss-wpengine.netdna-ssl.com/wp-content/themes/ncss/pdf/Procedures/NCSS/Circular_Data_Analysis.pdf
     *
     * S  = ∑sin θᵢ
     * C  = ∑cos θᵢ
     * R² = S² + C²
     * R  = √(S² + C²)
     *
     * @param  array $angles
     *
     * @return float
     */
    public static function resultantLength(array $angles): float
    {
        $S = \array_sum(\array_map(
            function ($θᵢ) {
                return \sin($θᵢ);
            },
            $angles
        ));
        $C = \array_sum(\array_map(
            function ($θᵢ) {
                return \cos($θᵢ);
            },
            $angles
        ));

        $S² = $S ** 2;
        $C² = $C ** 2;
        $R² = $S² + $C²;
        $R  = \sqrt($R²);

        return $R;
    }

    /**
     * Mean resultant length - MRL (ρ)
     * https://en.wikipedia.org/wiki/Directional_statistics#Moments
     * https://ncss-wpengine.netdna-ssl.com/wp-content/themes/ncss/pdf/Procedures/NCSS/Circular_Data_Analysis.pdf
     *
     * S  = ∑sin θᵢ
     * C  = ∑cos θᵢ
     * R² = S² + C²
     * R  = √(S² + C²)
     *
     * _    R
     * R  = -
     *      n
     *
     *      _
     * ρ  = R
     *
     * @param  array $angles
     *
     * @return float
     */
    public static function meanResultantLength(array $angles): float
    {
        $n = \count($angles);
        $R = self::resultantLength($angles);
        $ρ = $R / $n;

        return $ρ;
    }

    /**
     * Circular variance
     * https://en.wikipedia.org/wiki/Directional_statistics#Measures_of_location_and_spread
     * https://www.ebi.ac.uk/thornton-srv/software/PROCHECK/nmr_manual/man_cv.html
     * https://ncss-wpengine.netdna-ssl.com/wp-content/themes/ncss/pdf/Procedures/NCSS/Circular_Data_Analysis.pdf
     *              _
     * Var(θ) = 1 - R
     * Var(θ) = 1 - ρ
     *
     * @param  array $angles
     *
     * @return float
     */
    public static function variance(array $angles): float
    {
        $ρ = self::meanResultantLength($angles);

        return 1 - $ρ;
    }

    /**
     * Circular standard deviation
     * https://en.wikipedia.org/wiki/Directional_statistics#Measures_of_location_and_spread
     * https://ncss-wpengine.netdna-ssl.com/wp-content/themes/ncss/pdf/Procedures/NCSS/Circular_Data_Analysis.pdf
     *
     *       _______
     *      /     _
     * ν = √ -2ln(R)
     *
     *       _
     * Where R = ρ = mean resultant length
     *
     * @param  array $angles
     *
     * @return float
     */
    public static function standardDeviation(array $angles): float
    {
        $ρ       = self::meanResultantLength($angles);
        $√⟮−2ln⟮R⟯⟯ = \sqrt(-2 * \log($ρ));

        return $√⟮−2ln⟮R⟯⟯;
    }

    /**
     * Get a report of all the descriptive circular statistics over a list of angles
     * Includes mean, resultant length, mean resultant length, variance, standard deviation.
     *
     * @param array $angles
     *
     * @return array [ n, mean, resultant_length, mean_resultant_length, variance, sd]
     */
    public static function describe(array $angles): array
    {
        return [
            'n'                     => \count($angles),
            'mean'                  => self::mean($angles),
            'resultant_length'      => self::resultantLength($angles),
            'mean_resultant_length' => self::meanResultantLength($angles),
            'variance'              => self::variance($angles),
            'sd'                    => self::standardDeviation($angles),
        ];
    }
}
