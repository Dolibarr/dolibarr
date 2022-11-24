<?php

namespace MathPHP\SampleData;

/**
 * ToothGrowth data set (The Effect of Vitamin C on Tooth Growth in Guinea Pigs)
 *
 * The response is the length of odontoblasts (cells responsible for tooth growth) in 60 guinea pigs.
 * Each animal received one of three dose levels of vitamin C (0.5, 1, and 2 mg/day) by one of two delivery methods,
 * orange juice or ascorbic acid (a form of vitamin C and coded as VC).
 *
 * 60 observations on 3 variables: tooth length, supplement type (VC or OJ), and dose in milligrams/day
 * R ToothGrowth
 *
 * Source: C. I. Bliss (1952) The Statistics of Bioassay. Academic Press.
 */
class ToothGrowth
{
    private const LABELS = ['len', 'supp', 'dose'];

    private const DATA = [
        [4.2, 'VC', 0.5],
        [11.5, 'VC', 0.5],
        [7.3, 'VC', 0.5],
        [5.8, 'VC', 0.5],
        [6.4, 'VC', 0.5],
        [10.0, 'VC', 0.5],
        [11.2, 'VC', 0.5],
        [11.2, 'VC', 0.5],
        [5.2, 'VC', 0.5],
        [7.0, 'VC', 0.5],
        [16.5, 'VC', 1.0],
        [16.5, 'VC', 1.0],
        [15.2, 'VC', 1.0],
        [17.3, 'VC', 1.0],
        [22.5, 'VC', 1.0],
        [17.3, 'VC', 1.0],
        [13.6, 'VC', 1.0],
        [14.5, 'VC', 1.0],
        [18.8, 'VC', 1.0],
        [15.5, 'VC', 1.0],
        [23.6, 'VC', 2.0],
        [18.5, 'VC', 2.0],
        [33.9, 'VC', 2.0],
        [25.5, 'VC', 2.0],
        [26.4, 'VC', 2.0],
        [32.5, 'VC', 2.0],
        [26.7, 'VC', 2.0],
        [21.5, 'VC', 2.0],
        [23.3, 'VC', 2.0],
        [29.5, 'VC', 2.0],
        [15.2, 'OJ', 0.5],
        [21.5, 'OJ', 0.5],
        [17.6, 'OJ', 0.5],
        [9.7, 'OJ', 0.5],
        [14.5, 'OJ', 0.5],
        [10.0, 'OJ', 0.5],
        [8.2, 'OJ', 0.5],
        [9.4, 'OJ', 0.5],
        [16.5, 'OJ', 0.5],
        [9.7, 'OJ', 0.5],
        [19.7, 'OJ', 1.0],
        [23.3, 'OJ', 1.0],
        [23.6, 'OJ', 1.0],
        [26.4, 'OJ', 1.0],
        [20.0, 'OJ', 1.0],
        [25.2, 'OJ', 1.0],
        [25.8, 'OJ', 1.0],
        [21.2, 'OJ', 1.0],
        [14.5, 'OJ', 1.0],
        [27.3, 'OJ', 1.0],
        [25.5, 'OJ', 2.0],
        [26.4, 'OJ', 2.0],
        [22.4, 'OJ', 2.0],
        [24.5, 'OJ', 2.0],
        [24.8, 'OJ', 2.0],
        [30.9, 'OJ', 2.0],
        [26.4, 'OJ', 2.0],
        [27.3, 'OJ', 2.0],
        [29.4, 'OJ', 2.0],
        [23.0, 'OJ', 2.0],
    ];

    /**
     * Raw data without labels
     * [[4.2, 'VC', 0.5], [11.5, 'VC', '0.5], ... ]
     *
     * @return mixed[]
     */
    public function getData(): array
    {
        return \array_values(self::DATA);
    }

    /**
     * Raw data with each observation labeled
     * [['len' => 4.2, 'supp' => 'VC', 'dose' => 0.5], ... ]
     *
     * @return number[]
     */
    public function getLabeledData(): array
    {
        return \array_map(
            function (array $data) {
                return \array_combine(self::LABELS, $data);
            },
            self::DATA
        );
    }

    /**
     * Tooth length observations
     *
     * @return number[]
     */
    public function getLen(): array
    {
        return \array_column(self::DATA, 0);
    }

    /**
     * Supplement type (VC or OJ) observations
     *
     * @return string[]
     */
    public function getSupp(): array
    {
        return \array_column(self::DATA, 0);
    }

    /**
     * Dose in milligrams/day observations
     *
     * @return number[]
     */
    public function getDose(): array
    {
        return \array_column(self::DATA, 0);
    }
}
