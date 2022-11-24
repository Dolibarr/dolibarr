<?php

namespace MathPHP\SampleData;

/**
 * People data (R people {mdatools})
 *
 * Dataset for exploratory analysis with 32 objects (male and female persons) and 12 variables.
 *
 * Matrix with 32 observations (persons) and 12 variables.
 *  - Height in cm.
 *  - Weight in kg.
 *  - Hair length (-1 for short, +1 for long).
 *  - Shoe size (EU standard).
 *  - Age, years.
 *  - Income, euro per year.
 *  - Beer consumption, liters per year.
 *  - Wine consumption, liters per year.
 *  - Sex (-1 for male, +1 for female).
 *  - Swimming ability (index, based on 500 m swimming time).
 *  - Region (-1 for Scandinavia, +1 for Mediterranean.
 *  - IQ (European standardized test).
 *
 * The data was taken from the book [1] and is in fact a small subset of a pan-European demographic survey.
 * It includes information about 32 persons, 16 represent northern Europe (Scandinavians) and 16 are from the Mediterranean regions.
 * In both groups there are 8 male and 8 female persons. The data includes both quantitative and qualitative variables
 * and is particularly useful for benchmarking exploratory data analysis methods.
 *
 * Source: 1. K. Esbensen. Multivariate Data Analysis in Practice. Camo, 2002.
 */
class People
{
    private const LABELS = ['height', 'weight', 'hairLength', 'shoeSize', 'age', 'income', 'beer', 'wine', 'sex', 'swim', 'region', 'iq'];

    private const DATA = [
        'Lars'       => [198, 92, -1, 48, 48, 45000, 420, 115, -1, 98, -1, 100],
        'Peter'      => [184, 84, -1, 44, 33, 33000, 350, 102, -1, 92, -1, 130],
        'Rasmus'     => [183, 83, -1, 44, 37, 34000, 320,  98, -1, 91, -1, 127],
        'Lene'       => [166, 47, -1, 36, 32, 28000, 270,  78,  1, 75, -1, 112],
        'Mette'      => [170, 60,  1, 38, 23, 20000, 312,  99,  1, 81, -1, 110],
        'Gitte'      => [172, 64,  1, 39, 24, 22000, 308,  91,  1, 82, -1, 102],
        'Jens'       => [182, 80, -1, 42, 35, 30000, 398,  65, -1, 85, -1, 140],
        'Erik'       => [180, 80, -1, 43, 36, 30000, 388,  63, -1, 84, -1, 129],
        'Lotte'      => [169, 51,  1, 36, 24, 23000, 250,  89,  1, 78, -1,  98],
        'Heidi'      => [168, 52,  1, 37, 27, 23500, 260,  86,  1, 78, -1, 100],
        'Kaj'        => [183, 81, -1, 42, 37, 35000, 345,  45, -1, 90, -1, 105],
        'Gerda'      => [157, 47,  1, 36, 32, 32000, 235,  92,  1, 70, -1, 127],
        'Anne'       => [164, 50,  1, 38, 41, 34000, 255, 134,  1, 76, -1, 101],
        'Britta'     => [162, 49,  1, 37, 40, 34000, 265, 124,  1, 75, -1, 108],
        'Magnus'     => [180, 82, -1, 44, 43, 37000, 355,  82, -1, 88, -1, 109],
        'Casper'     => [180, 81, -1, 44, 46, 42000, 362,  90, -1, 86, -1, 113],
        'Luka'       => [185, 82, -1, 45, 26, 16000, 295, 180, -1, 92,  1, 109],
        'Federico'   => [187, 84, -1, 46, 27, 16500, 299, 178, -1, 95,  1, 119],
        'Dona'       => [168, 50,  1, 37, 49, 34000, 170, 162,  1, 76,  1, 135],
        'Fabrizia'   => [166, 49,  1, 36, 21, 14000, 150, 245,  1, 75,  1, 123],
        'Lisa'       => [158, 46,  1, 34, 30, 18000, 120, 120,  1, 70,  1, 119],
        'Benito'     => [177, 65, -1, 41, 26, 18000, 209, 160, -1, 86,  1, 120],
        'Franko'     => [180, 72, -1, 43, 33, 19000, 236, 175, -1, 85,  1, 115],
        'Alessandro' => [181, 75, -1, 43, 42, 31000, 198, 161, -1, 83,  1, 105],
        'Leonora'    => [163, 50,  1, 36, 18, 11000, 143, 136,  1, 75,  1, 102],
        'Giuliana'   => [162, 50,  1, 36, 20, 11500, 133, 146,  1, 74,  1, 132],
        'Giovanni'   => [176, 68, -1, 42, 50, 36000, 195, 177, -1, 82,  1,  96],
        'Leonardo'   => [175, 67,  1, 42, 55, 38000, 185, 187, -1, 80,  1, 105],
        'Marta'      => [165, 51,  1, 36, 36, 26000, 121, 129,  1, 76,  1, 126],
        'Rosetta'    => [161, 48,  1, 35, 41, 31500, 116, 196,  1, 75,  1, 120],
        'Romeo'      => [178, 75, -1, 42, 30, 24000, 203, 208, -1, 81,  1, 118],
        'Romina'     => [160, 48,  1, 35, 40, 31000, 118, 198,  1, 74,  1, 129],
    ];

    /**
     * Raw data without labels
     * [[198, 92, -1, ... ], [184, 84, -1, ... ], ... ]
     *
     * @return int[][]
     */
    public function getData(): array
    {
        return \array_values(self::DATA);
    }

    /**
     * Raw data with each observation labeled
     * ['Lars' => ['height' => 198, 'weight' => 92, 'hairLength' => -1, ... ]]
     *
     * @return int[][]
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
     * People names
     *
     * @return string[]
     */
    public function getNames(): array
    {
        return \array_keys(self::DATA);
    }

    /**
     * Data for a person, with labels
     * ['height' => 198, 'weight' => 92, 'hairLength' => -1, ... ]
     *
     * @param string $name
     *
     * @return int[]
     */
    public function getPersonData(string $name): array
    {
        return \array_combine(self::LABELS, self::DATA[$name]);
    }

    /**
     * @return int[]
     */
    public function getHeight(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 0));
    }

    /**
     * @return int[]
     */
    public function getWeight(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 1));
    }

    /**
     * @return int[]
     */
    public function getHairLength(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 2));
    }

    /**
     * @return int[]
     */
    public function getShowSize(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 3));
    }

    /**
     * @return number[]
     */
    public function getAge(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 4));
    }

    /**
     * @return int[]
     */
    public function getIncome(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 5));
    }

    /**
     * @return int[]
     */
    public function getBeer(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 6));
    }

    /**
     * @return int[]
     */
    public function getWine(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 7));
    }

    /**
     * @return int[]
     */
    public function getSex(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 8));
    }

    /**
     * @return int[]
     */
    public function getSwim(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 9));
    }

    /**
     * @return int[]
     */
    public function getRegion(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 10));
    }

    /**
     * @return int[]
     */
    public function getIq(): array
    {
        return \array_combine($this->getNames(), \array_column(self::DATA, 11));
    }
}
