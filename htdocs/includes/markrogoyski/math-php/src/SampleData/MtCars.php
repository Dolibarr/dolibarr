<?php

namespace MathPHP\SampleData;

/**
 * mtcars dataset (Motor Trend Car Road Tests)
 *
 * The data was extracted from the 1974 Motor Trend US magazine, and comprises fuel consumption and 10 aspects of
 * automobile design and performance for 32 automobiles (1973–74 models).
 *
 * 32 observations on 11 variables.
 * R mtcars
 *
 * Source: Henderson and Velleman (1981), Building multiple regression models interactively. Biometrics, 37, 391–411.
 */
class MtCars
{
    private const LABELS = ['mpg', 'cyl', 'disp', 'hp', 'drat', 'wt', 'qsec', 'vs', 'am', 'gear', 'carb'];

    private const DATA = [
        'Mazda RX4'           => [21, 6, 160, 110, 3.9, 2.62, 16.46, 0, 1, 4, 4],
        'Mazda RX4 Wag'       => [21, 6, 160, 110, 3.9, 2.875, 17.02, 0, 1, 4, 4],
        'Datsun 710'          => [22.8, 4, 108, 93, 3.85, 2.32, 18.61, 1, 1, 4, 1],
        'Hornet 4 Drive'      => [21.4, 6, 258, 110, 3.08, 3.215, 19.44, 1, 0, 3, 1],
        'Hornet Sportabout'   => [18.7, 8, 360, 175, 3.15, 3.44, 17.02, 0, 0, 3, 2],
        'Valiant'             => [18.1, 6, 225, 105, 2.76, 3.46, 20.22, 1, 0, 3, 1],
        'Duster 360'          => [14.3, 8, 360, 245, 3.21, 3.57, 15.84, 0, 0, 3, 4],
        'Merc 240D'           => [24.4, 4, 146.7, 62, 3.69, 3.19, 20, 1, 0, 4, 2],
        'Merc 230'            => [22.8, 4, 140.8, 95, 3.92, 3.15, 22.9, 1, 0, 4, 2],
        'Merc 280'            => [19.2, 6, 167.6, 123, 3.92, 3.44, 18.3, 1, 0, 4, 4],
        'Merc 280C'           => [17.8, 6, 167.6, 123, 3.92, 3.44, 18.9, 1, 0, 4, 4],
        'Merc 450SE'          => [16.4, 8, 275.8, 180, 3.07, 4.07, 17.4, 0, 0, 3, 3],
        'Merc 450SL'          => [17.3, 8, 275.8, 180, 3.07, 3.73, 17.6, 0, 0, 3, 3],
        'Merc 450SLC'         => [15.2, 8, 275.8, 180, 3.07, 3.78, 18, 0, 0, 3, 3],
        'Cadillac Fleetwood'  => [10.4, 8, 472, 205, 2.93, 5.25, 17.98, 0, 0, 3, 4],
        'Lincoln Continental' => [10.4, 8, 460, 215, 3, 5.424, 17.82, 0, 0, 3, 4],
        'Chrysler Imperial'   => [14.7, 8, 440, 230, 3.23, 5.345, 17.42, 0, 0, 3, 4],
        'Fiat 128'            => [32.4, 4, 78.7, 66, 4.08, 2.2, 19.47, 1, 1, 4, 1],
        'Honda Civic'         => [30.4, 4, 75.7, 52, 4.93, 1.615, 18.52, 1, 1, 4, 2],
        'Toyota Corolla'      => [33.9, 4, 71.1, 65, 4.22, 1.835, 19.9, 1, 1, 4, 1],
        'Toyota Corona'       => [21.5, 4, 120.1, 97, 3.7, 2.465, 20.01, 1, 0, 3, 1],
        'Dodge Challenger'    => [15.5, 8, 318, 150, 2.76, 3.52, 16.87, 0, 0, 3, 2],
        'AMC Javelin'         => [15.2, 8, 304, 150, 3.15, 3.435, 17.3, 0, 0, 3, 2],
        'Camaro Z28'          => [13.3, 8, 350, 245, 3.73, 3.84, 15.41, 0, 0, 3, 4],
        'Pontiac Firebird'    => [19.2, 8, 400, 175, 3.08, 3.845, 17.05, 0, 0, 3, 2],
        'Fiat X1-9'           => [27.3, 4, 79, 66, 4.08, 1.935, 18.9, 1, 1, 4, 1],
        'Porsche 914-2'       => [26, 4, 120.3, 91, 4.43, 2.14, 16.7, 0, 1, 5, 2],
        'Lotus Europa'        => [30.4, 4, 95.1, 113, 3.77, 1.513, 16.9, 1, 1, 5, 2],
        'Ford Pantera L'      => [15.8, 8, 351, 264, 4.22, 3.17, 14.5, 0, 1, 5, 4],
        'Ferrari Dino'        => [19.7, 6, 145, 175, 3.62, 2.77, 15.5, 0, 1, 5, 6],
        'Maserati Bora'       => [15, 8, 301, 335, 3.54, 3.57, 14.6, 0, 1, 5, 8],
        'Volvo 142E'          => [21.4, 4, 121, 109, 4.11, 2.78, 18.6, 1, 1, 4, 2],
    ];

    /**
     * Raw data without labels
     * [[21, 6, 160, ... ], [30.4, 4, 71.1, ... ], ... ]
     *
     * @return number[][]
     */
    public function getData(): array
    {
        return \array_values(self::DATA);
    }

    /**
     * Raw data with each observation labeled
     * ['Car Model' => ['mpg' => 21, 'cyl' => 6, 'disp' => 160, ... ]]
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
     * Car model names
     *
     * @return string[]
     */
    public function getModels(): array
    {
        return \array_keys(self::DATA);
    }

    /**
     * Data for a car model, with labels
     * ['mpg' => 21, 'cyl' => 6, 'disp' => 160, ... ]
     *
     * @param string $model
     *
     * @return number[]
     */
    public function getModelData(string $model): array
    {
        return \array_combine(self::LABELS, self::DATA[$model]);
    }

    /**
     * Miles per gallon observations for all models
     * ['Mazda RX4' => 21, 'Honda civic' => 30.4, ... ]
     *
     * @return number[]
     */
    public function getMpg(): array
    {
        return \array_combine($this->getModels(), \array_column(self::DATA, 0));
    }

    /**
     * Number of cylinders observations for all models
     * ['Mazda RX4' => 6, 'Honda civic' => 4, ... ]
     *
     * @return number[]
     */
    public function getCyl(): array
    {
        return \array_column(self::DATA, 1);
    }

    /**
     * Displacement (cubic inches) observations for all models
     * ['Mazda RX4' => 160, 'Honda civic' => 75.7, ... ]
     *
     * @return number[]
     */
    public function getDisp(): array
    {
        return \array_column(self::DATA, 2);
    }

    /**
     * Gross horsepower observations for all models
     * ['Mazda RX4' => 110, 'Honda civic' => 52, ... ]
     *
     * @return number[]
     */
    public function getHp(): array
    {
        return \array_column(self::DATA, 3);
    }

    /**
     * Rear axle ratio observations for all models
     * ['Mazda RX4' => 3.9, 'Honda civic' => 4.93, ... ]
     *
     * @return number[]
     */
    public function getDrat(): array
    {
        return \array_column(self::DATA, 4);
    }

    /**
     * Weight (1,000 pounds) observations for all models
     * ['Mazda RX4' => 2.62, 'Honda civic' => 1.615, ... ]
     *
     * @return number[]
     */
    public function getWt(): array
    {
        return \array_column(self::DATA, 5);
    }

    /**
     * Quarter-mile time observations for all models
     * ['Mazda RX4' => 16.46, 'Honda civic' => 18.52, ... ]
     *
     * @return number[]
     */
    public function getQsec(): array
    {
        return \array_column(self::DATA, 6);
    }

    /**
     * V/S observations for all models
     * ['Mazda RX4' => 0, 'Honda civic' => 1, ... ]
     *
     * @return number[]
     */
    public function getVs(): array
    {
        return \array_column(self::DATA, 7);
    }

    /**
     * Transmission (automatic: 0, manual: 1) observations for all models
     * ['Mazda RX4' => 1, 'Honda civic' => 1, ... ]
     *
     * @return number[]
     */
    public function getAm(): array
    {
        return \array_column(self::DATA, 8);
    }

    /**
     * Number of forward gears observations for all models
     * ['Mazda RX4' => 4, 'Honda civic' => 4, ... ]
     *
     * @return number[]
     */
    public function getGear(): array
    {
        return \array_column(self::DATA, 9);
    }

    /**
     * Number of carburetors observations for all models
     * ['Mazda RX4' => 4, 'Honda civic' => 2, ... ]
     *
     * @return number[]
     */
    public function getCarb(): array
    {
        return \array_column(self::DATA, 10);
    }
}
