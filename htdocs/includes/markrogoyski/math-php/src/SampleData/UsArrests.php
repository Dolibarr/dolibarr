<?php

namespace MathPHP\SampleData;

/**
 * USArrests dataset (Violent Crime Rates by US State)
 *
 * This data set contains statistics, in arrests per 100,000 residents for assault, murder, and rape in each of
 * the 50 US states in 1973. Also given is the percent of the population living in urban areas.
 *
 * 50 observations on 4 variables.
 * R USArrests
 *
 * Source: World Almanac and Book of facts 1975. (Crime rates).
 */
class UsArrests
{
    private const LABELS = ['murder', 'assault', 'urbanPop', 'rape'];

    private const DATA = [
        'Alabama'        => [13.2, 236, 58, 21.2],
        'Alaska'         => [10.0, 263, 48, 44.5],
        'Arizona'        => [8.1, 294, 80, 31.0],
        'Arkansas'       => [8.8, 190, 50, 19.5],
        'California'     => [9.0, 276, 91, 40.6],
        'Colorado'       => [7.9, 204, 78, 38.7],
        'Connecticut'    => [3.3, 110, 77, 11.1],
        'Delaware'       => [5.9, 238, 72, 15.8],
        'Florida'        => [15.4, 335, 80, 31.9],
        'Georgia'        => [17.4, 211, 60, 25.8],
        'Hawaii'         => [5.3, 46, 83, 20.2],
        'Idaho'          => [2.6, 120, 54, 14.2],
        'Illinois'       => [10.4, 249, 83, 24.0],
        'Indiana'        => [7.2, 113, 65, 21.0],
        'Iowa'           => [2.2, 56, 57, 11.3],
        'Kansas'         => [6.0, 115, 66, 18.0],
        'Kentucky'       => [9.7, 109, 52, 16.3],
        'Louisiana'      => [15.4, 249, 66, 22.2],
        'Maine'          => [2.1, 83, 51, 7.8],
        'Maryland'       => [11.3, 300, 67, 27.8],
        'Massachusetts'  => [4.4, 149, 85, 16.3],
        'Michigan'       => [12.1, 255, 74, 35.1],
        'Minnesota'      => [2.7, 72, 66, 14.9],
        'Mississippi'    => [16.1, 259, 44, 17.1],
        'Missouri'       => [9.0, 178, 70, 28.2],
        'Montana'        => [6.0, 109, 53, 16.4],
        'Nebraska'       => [4.3, 102, 62, 16.5],
        'Nevada'         => [12.2, 252, 81, 46.0],
        'New Hampshire'  => [2.1, 57, 56,  9.5],
        'New Jersey'     => [7.4, 159, 89, 18.8],
        'New Mexico'     => [11.4, 285, 70, 32.1],
        'New York'       => [11.1, 254, 86, 26.1],
        'North Carolina' => [13.0, 337, 45, 16.1],
        'North Dakota'   => [0.8, 45, 44,  7.3],
        'Ohio'           => [7.3, 120, 75, 21.4],
        'Oklahoma'       => [6.6, 151, 68, 20.0],
        'Oregon'         => [4.9, 159, 67, 29.3],
        'Pennsylvania'   => [6.3, 106, 72, 14.9],
        'Rhode Island'   => [3.4, 174, 87,  8.3],
        'South Carolina' => [14.4, 279, 48, 22.5],
        'South Dakota'   => [3.8, 86, 45, 12.8],
        'Tennessee'      => [13.2, 188, 59, 26.9],
        'Texas'          => [12.7, 201, 80, 25.5],
        'Utah'           => [3.2, 120, 80, 22.9],
        'Vermont'        => [2.2, 48, 32, 11.2],
        'Virginia'       => [8.5, 156, 63, 20.7],
        'Washington'     => [4.0, 145, 73, 26.2],
        'West Virginia'  => [5.7, 81, 39,  9.3],
        'Wisconsin'      => [2.6, 53, 66, 10.8],
        'Wyoming'        => [6.8, 161, 60, 15.6],
    ];

    /**
     * Raw data without labels
     * [[13.2, 236, 58, 21.2], [10.0, 263, 48, 44.5], ... ]
     *
     * @return number[][]
     */
    public function getData(): array
    {
        return \array_values(self::DATA);
    }

    /**
     * Raw data with each observation labeled
     * ['Alabama' => ['murder' => 13.2, 'assault' => 236, 'urbanPop' => 58, 'rape' => 21.2], ... ]
     *
     * @return number[][]
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
     * State names names
     *
     * @return string[]
     */
    public function getStates(): array
    {
        return \array_keys(self::DATA);
    }

    /**
     * Data for a state, with labels
     * ['murder' => 13.2, 'assault' => 236, 'urbanPop' => 58, 'rape' => 21.2]
     *
     * @param string $state
     *
     * @return number[]
     */
    public function getStateData(string $state): array
    {
        return \array_combine(self::LABELS, self::DATA[$state]);
    }

    /**
     * Murder observations for all states
     * ['Alabama' => 13.2, 'Alaska' => 10.1, ... ]
     *
     * @return number[]
     */
    public function getMurder(): array
    {
        return \array_combine($this->getStates(), \array_column(self::DATA, 0));
    }

    /**
     * Assault observations for all states
     * ['Alabama' => 236, 'Alaska' => 263, ... ]
     *
     * @return number[]
     */
    public function getAssault(): array
    {
        return \array_combine($this->getStates(), \array_column(self::DATA, 1));
    }

    /**
     * UrbanPop observations for all states
     * ['Alabama' => 58, 'Alaska' => 48, ... ]
     *
     * @return number[]
     */
    public function getUrbanPop(): array
    {
        return \array_combine($this->getStates(), \array_column(self::DATA, 2));
    }

    /**
     * Rape observations for all states
     * ['Alabama' => 21.2, 'Alaska' => 44.5, ... ]
     *
     * @return number[]
     */
    public function getRape(): array
    {
        return \array_combine($this->getStates(), \array_column(self::DATA, 3));
    }
}
