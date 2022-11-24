<?php

namespace MathPHP\Tests\NumericalAnalysis\Interpolation;

use MathPHP\Exception\BadDataException;
use MathPHP\NumericalAnalysis\Interpolation\RegularGridInterpolator;

class RegularGridInterpolatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test         Interpolated regular grid function computes expected values: p(x) = expected
     * @dataProvider dataProviderForRegularGridAgrees
     * @param        array $point
     * @param        float $expected
     * @throws       \Exception
     */
    public function testRegularGridAgrees(array $point, float $expected)
    {
        // Given
        [$points, $values] = $this->getSample4d();

        // And
        $p = new RegularGridInterpolator($points, $values);

        // When
        $evaluated = $p($point);

        // Then
        $this->assertEquals($expected, $evaluated);
    }

    /**
     * Test data based on SciPy test cases
     * https://github.com/scipy/scipy/blob/c734dacd61c5962a86ab3cc4bf2891fc94b720a6/scipy/interpolate/tests/test_interpolate.py#L2361
     * @return array (x, expected)
     */
    public function dataProviderForRegularGridAgrees(): array
    {
        return [
            // test_linear_xi3d
            [[0.1, 0.1, 1., .9], 1001.1],
            [[0.2, 0.1, .45, .8], 846.2],
            [[0.5, 0.5, .5, .5], 555.5],

            // test_linear_edges
            [[0., 0., 0., 0.], 0.],
            [[1., 1., 1., 1.], 1111.],
        ];
    }

    /**
     * @test         Interpolated regular grid function computes expected values: p(x) = expected
     * @dataProvider dataProviderForRegularGridNearestAgrees
     * @param        array $point
     * @throws       \Exception
     */
    public function testRegularGridNearestAgrees(array $point, $expected)
    {
        // Given
        [$points, $values] = $this->getSample4d();

        // And
        $p = new RegularGridInterpolator($points, $values, RegularGridInterpolator::METHOD_NEAREST);

        // When
        $evaluated = $p($point);

        // Then
        $this->assertEquals($expected, $evaluated);
    }

    /**
     * @return array (x, expected)
     */
    public function dataProviderForRegularGridNearestAgrees(): array
    {
        return [
            [[0.1, 0.1, .9, .9], 1100],
            [[0.1, 0.1, 0.1, 0.1], 0.],
            [[0., 0., 0., 0.], 0.],
            [[1., 1., 1., 1.], 1111.],
            [[0.1, 0.4, 0.6, 0.9], 1055.]
        ];
    }

    /**
     * @test Example from Github issue 382
     *       https://github.com/markrogoyski/math-php/issues/382
     *
     * >>> xs = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
     * >>> ys = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
     * >>> zs = [110, 111, 112, 113, 114, 115, 116, 117, 118, 119];
     *
     * >>> def func(x, y, z):
     * ...   return 2 * x + 3 * y - z
     *
     * >>> values = [[[func(x, y, z) for z in zs] for y in ys] for x in xs]
     *
     * >>> my_interpolating_function = RegularGridInterpolator((xs, ys, zs), values, method='linear')
     * >>> my_interpolating_function([2.21, 12.1, 115.9])
     * array([-75.18])
     */
    public function testIssue382ExampleLinear()
    {
        // Given
        $xs = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $ys = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
        $zs = [110, 111, 112, 113, 114, 115, 116, 117, 118, 119];

        // And
        $func = function ($x, $y, $z) {
            return 2 * $x + 3 * $y - $z;
        };

        // And
        $data = [];
        foreach ($xs as $i => $x) {
            foreach ($ys as $j => $y) {
                foreach ($zs as $k => $z) {
                    $data[$i][$j][$k] = $func($x, $y, $z);
                }
            }
        }

        // When
        $interp = new RegularGridInterpolator([$xs, $ys, $zs], $data, 'linear');
        $result = $interp([2.21, 12.1, 115.9]);

        // Then
        $this->assertEqualsWithDelta(-75.18, $result, 0.00001);
    }

    /**
     * @test Example from Github issue 382
     *       https://github.com/markrogoyski/math-php/issues/382
     *
     * >>> xs = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
     * >>> ys = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
     * >>> zs = [110, 111, 112, 113, 114, 115, 116, 117, 118, 119];
     *
     * >>> def func(x, y, z):
     * ...   return 2 * x + 3 * y - z
     *
     * >>> values = [[[func(x, y, z) for z in zs] for y in ys] for x in xs]
     *
     * >>> my_interpolating_function = RegularGridInterpolator((xs, ys, zs), values, method='nearest')
     * >>> my_interpolating_function([2.21, 12.1, 115.9])
     * array([-76.])
     */
    public function testIssue382ExampleNearest()
    {
        // Given
        $xs = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $ys = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
        $zs = [110, 111, 112, 113, 114, 115, 116, 117, 118, 119];

        // And
        $func = function ($x, $y, $z) {
            return 2 * $x + 3 * $y - $z;
        };

        // And
        $data = [];
        foreach ($xs as $i => $x) {
            foreach ($ys as $j => $y) {
                foreach ($zs as $k => $z) {
                    $data[$i][$j][$k] = $func($x, $y, $z);
                }
            }
        }

        // When
        $interp = new RegularGridInterpolator([$xs, $ys, $zs], $data, 'nearest');
        $result = $interp([2.21, 12.1, 115.9]);

        // Then
        $this->assertEqualsWithDelta(-76, $result, 0.00001);
    }

    /**
     * @test SciPy documentation example 1
     * https://docs.scipy.org/doc/scipy/reference/generated/scipy.interpolate.RegularGridInterpolator.html#scipy.interpolate.RegularGridInterpolator
     *
     * from scipy.interpolate import RegularGridInterpolator
     * def f(x, y, z):
     *   return 2 * x**3 + 3 * y**2 - z
     * x = np.linspace(1, 4, 11)
     * y = np.linspace(4, 7, 22)
     * z = np.linspace(7, 9, 33)
     * data = f(*np.meshgrid(x, y, z, indexing='ij', sparse=True))
     * my_interpolating_function = RegularGridInterpolator((x, y, z), data)
     * pts = np.array([[2.1, 6.2, 8.3], [3.3, 5.2, 7.1]])
     * my_interpolating_function(pts)
     * array([ 125.80469388,  146.30069388])
     */
    public function testSciPyExample1()
    {
        // Given
        $xs = [1. , 1.3, 1.6, 1.9, 2.2, 2.5, 2.8, 3.1, 3.4, 3.7, 4.];
        $ys = [
            4.        , 4.14285714, 4.28571429, 4.42857143, 4.57142857,
            4.71428571, 4.85714286, 5.        , 5.14285714, 5.28571429,
            5.42857143, 5.57142857, 5.71428571, 5.85714286, 6.        ,
            6.14285714, 6.28571429, 6.42857143, 6.57142857, 6.71428571,
            6.85714286, 7.
        ];
        $zs = [
            7.    , 7.0625, 7.125 , 7.1875, 7.25  , 7.3125, 7.375 , 7.4375,
            7.5   , 7.5625, 7.625 , 7.6875, 7.75  , 7.8125, 7.875 , 7.9375,
            8.    , 8.0625, 8.125 , 8.1875, 8.25  , 8.3125, 8.375 , 8.4375,
            8.5   , 8.5625, 8.625 , 8.6875, 8.75  , 8.8125, 8.875 , 8.9375,
            9.
        ];

        // And
        $func = function ($x, $y, $z) {
            return 2 * $x ** 3 + 3 * $y ** 2 - $z;
        };

        // And
        $data = [];
        foreach ($xs as $i => $x) {
            foreach ($ys as $j => $y) {
                foreach ($zs as $k => $z) {
                    $data[$i][$j][$k] = $func($x, $y, $z);
                }
            }
        }

        // When
        $interp = new RegularGridInterpolator([$xs, $ys, $zs], $data, 'linear');
        $result = $interp([2.1, 6.2, 8.3]);

        // Then
        $this->assertEqualsWithDelta(125.80469388, $result, 0.00001);
    }

    /**
     * @test SciPy documentation example 2
     * https://docs.scipy.org/doc/scipy/reference/generated/scipy.interpolate.RegularGridInterpolator.html#scipy.interpolate.RegularGridInterpolator
     *
     * from scipy.interpolate import RegularGridInterpolator
     * def f(x, y, z):
     *   return 2 * x**3 + 3 * y**2 - z
     * x = np.linspace(1, 4, 11)
     * y = np.linspace(4, 7, 22)
     * z = np.linspace(7, 9, 33)
     * data = f(*np.meshgrid(x, y, z, indexing='ij', sparse=True))
     * my_interpolating_function = RegularGridInterpolator((x, y, z), data)
     * pts = np.array([[3.3, 5.2, 7.1]])
     * my_interpolating_function(pts)
     * array([146.30069388])
     */
    public function testSciPyExample2()
    {
        // Given
        $xs = [1. , 1.3, 1.6, 1.9, 2.2, 2.5, 2.8, 3.1, 3.4, 3.7, 4.];
        $ys = [
            4.        , 4.14285714, 4.28571429, 4.42857143, 4.57142857,
            4.71428571, 4.85714286, 5.        , 5.14285714, 5.28571429,
            5.42857143, 5.57142857, 5.71428571, 5.85714286, 6.        ,
            6.14285714, 6.28571429, 6.42857143, 6.57142857, 6.71428571,
            6.85714286, 7.
        ];
        $zs = [
            7.    , 7.0625, 7.125 , 7.1875, 7.25  , 7.3125, 7.375 , 7.4375,
            7.5   , 7.5625, 7.625 , 7.6875, 7.75  , 7.8125, 7.875 , 7.9375,
            8.    , 8.0625, 8.125 , 8.1875, 8.25  , 8.3125, 8.375 , 8.4375,
            8.5   , 8.5625, 8.625 , 8.6875, 8.75  , 8.8125, 8.875 , 8.9375,
            9.
        ];

        // And
        $func = function ($x, $y, $z) {
            return 2 * $x ** 3 + 3 * $y ** 2 - $z;
        };

        // And
        $data = [];
        foreach ($xs as $i => $x) {
            foreach ($ys as $j => $y) {
                foreach ($zs as $k => $z) {
                    $data[$i][$j][$k] = $func($x, $y, $z);
                }
            }
        }

        // When
        $interp = new RegularGridInterpolator([$xs, $ys, $zs], $data, 'linear');
        $result = $interp([3.3, 5.2, 7.1]);

        // Then
        $this->assertEqualsWithDelta(146.30069388, $result, 0.00001);
    }

    /**
     * @test Interpolated point values are outside the domain of the input data grid. Values outside the domain are extrapolated.
     *       Linear method.
     *       This test will hit the condition in the findIndices method where i > gridSize - 2.
     *
     * https://docs.scipy.org/doc/scipy/reference/generated/scipy.interpolate.RegularGridInterpolator.html#scipy.interpolate.RegularGridInterpolator
     *
     * from scipy.interpolate import RegularGridInterpolator
     * def f(x, y, z):
     *   return 2 * x**3 + 3 * y**2 - z
     * x = np.linspace(1, 4, 11)
     * y = np.linspace(4, 7, 22)
     * z = np.linspace(7, 9, 33)
     * data = f(*np.meshgrid(x, y, z, indexing='ij', sparse=True))
     * my_interpolating_function = RegularGridInterpolator((x, y, z), data, method='linear', bounds_error=False, fill_value=None)
     * pts = np.array([[3.3, 7.2, 7.1]]) # 7.2 is outside the bounds of the grid
     * my_interpolating_function(pts)
     * array([220.48028571])
     */
    public function testInterpolatedPointValuesOutsideDomainOfInputDataGridAreExtrapolatedLinear()
    {
        // Given
        $xs = [1. , 1.3, 1.6, 1.9, 2.2, 2.5, 2.8, 3.1, 3.4, 3.7, 4.];
        $ys = [
            4.        , 4.14285714, 4.28571429, 4.42857143, 4.57142857,
            4.71428571, 4.85714286, 5.        , 5.14285714, 5.28571429,
            5.42857143, 5.57142857, 5.71428571, 5.85714286, 6.        ,
            6.14285714, 6.28571429, 6.42857143, 6.57142857, 6.71428571,
            6.85714286, 7.
        ];
        $zs = [
            7.    , 7.0625, 7.125 , 7.1875, 7.25  , 7.3125, 7.375 , 7.4375,
            7.5   , 7.5625, 7.625 , 7.6875, 7.75  , 7.8125, 7.875 , 7.9375,
            8.    , 8.0625, 8.125 , 8.1875, 8.25  , 8.3125, 8.375 , 8.4375,
            8.5   , 8.5625, 8.625 , 8.6875, 8.75  , 8.8125, 8.875 , 8.9375,
            9.
        ];

        // And
        $func = function ($x, $y, $z) {
            return 2 * $x ** 3 + 3 * $y ** 2 - $z;
        };

        // And
        $data = [];
        foreach ($xs as $i => $x) {
            foreach ($ys as $j => $y) {
                foreach ($zs as $k => $z) {
                    $data[$i][$j][$k] = $func($x, $y, $z);
                }
            }
        }

        // When
        $interp = new RegularGridInterpolator([$xs, $ys, $zs], $data, 'linear');
        $result = $interp([3.3, 7.2, 7.1]);  // 7.2 is outside the bounds of the grid

        // Then
        $this->assertEqualsWithDelta(220.48028571, $result, 0.00001);
    }

    /**
     * @test Interpolated point values are outside the domain of the input data grid. Values outside the domain are extrapolated.
     *       Nearest method.
     *       This test will hit the condition in the findIndices method where i > gridSize - 2.
     *
     * https://docs.scipy.org/doc/scipy/reference/generated/scipy.interpolate.RegularGridInterpolator.html#scipy.interpolate.RegularGridInterpolator
     *
     * from scipy.interpolate import RegularGridInterpolator
     * def f(x, y, z):
     *   return 2 * x**3 + 3 * y**2 - z
     * x = np.linspace(1, 4, 11)
     * y = np.linspace(4, 7, 22)
     * z = np.linspace(7, 9, 33)
     * data = f(*np.meshgrid(x, y, z, indexing='ij', sparse=True))
     * my_interpolating_function = RegularGridInterpolator((x, y, z), data, method='nearest', bounds_error=False, fill_value=None)
     * pts = np.array([[3.3, 7.2, 7.1]]) # 7.2 is outside the bounds of the grid
     * my_interpolating_function(pts)
     * array([220.48028571])
     */
    public function testInterpolatedPointValuesOutsideDomainOfInputDataGridAreExtrapolatedNearest()
    {
        // Given
        $xs = [1. , 1.3, 1.6, 1.9, 2.2, 2.5, 2.8, 3.1, 3.4, 3.7, 4.];
        $ys = [
            4.        , 4.14285714, 4.28571429, 4.42857143, 4.57142857,
            4.71428571, 4.85714286, 5.        , 5.14285714, 5.28571429,
            5.42857143, 5.57142857, 5.71428571, 5.85714286, 6.        ,
            6.14285714, 6.28571429, 6.42857143, 6.57142857, 6.71428571,
            6.85714286, 7.
        ];
        $zs = [
            7.    , 7.0625, 7.125 , 7.1875, 7.25  , 7.3125, 7.375 , 7.4375,
            7.5   , 7.5625, 7.625 , 7.6875, 7.75  , 7.8125, 7.875 , 7.9375,
            8.    , 8.0625, 8.125 , 8.1875, 8.25  , 8.3125, 8.375 , 8.4375,
            8.5   , 8.5625, 8.625 , 8.6875, 8.75  , 8.8125, 8.875 , 8.9375,
            9.
        ];

        // And
        $func = function ($x, $y, $z) {
            return 2 * $x ** 3 + 3 * $y ** 2 - $z;
        };

        // And
        $data = [];
        foreach ($xs as $i => $x) {
            foreach ($ys as $j => $y) {
                foreach ($zs as $k => $z) {
                    $data[$i][$j][$k] = $func($x, $y, $z);
                }
            }
        }

        // When
        $interp = new RegularGridInterpolator([$xs, $ys, $zs], $data, 'nearest');
        $result = $interp([3.3, 7.2, 7.1]);  // 7.2 is outside the bounds of the grid

        // Then
        $this->assertEqualsWithDelta(218.483, $result, 0.00001);
    }

    /**
     * @test Similar values
     *
     * >>> xs = [1, 2, 3];
     * >>> ys = [1, 2, 3];
     * >>> zs = [1, 2, 3];
     *
     * >>> def func(x, y, z):
     * ...   return 2 * x + 3 * y - z
     *
     * >>> values = [[[func(x, y, z) for z in zs] for y in ys] for x in xs]
     *
     * >>> my_interpolating_function = RegularGridInterpolator((xs, ys, zs), values, method='linear')
     * >>> my_interpolating_function(point)
     *
     * @dataProvider dataProviderForSimilarValues
     * @param        array $point
     * @param        float $expected
     */
    public function testSimilarValues(array $point, float $expected)
    {
        // Given
        $xs = [1, 2, 3];
        $ys = [1, 2, 3];
        $zs = [1, 2, 3];

        // And
        $func = function ($x, $y, $z) {
            return 2 * $x + 3 * $y - $z;
        };

        // And
        $data = [];
        foreach ($xs as $i => $x) {
            foreach ($ys as $j => $y) {
                foreach ($zs as $k => $z) {
                    $data[$i][$j][$k] = $func($x, $y, $z);
                }
            }
        }

        // When
        $interp = new RegularGridInterpolator([$xs, $ys, $zs], $data, 'linear');
        $result = $interp($point);

        // Then
        $this->assertEqualsWithDelta($expected, $result, 0.00001);
    }

    /**
     * @return array (point, expected)
     */
    public function dataProviderForSimilarValues(): array
    {
        return [
            [[1, 1, 1], 4.],
            [[1, 1, 2], 3.],
            [[1, 1, 3], 2.],
            [[1, 2, 1], 7.],
            [[1, 2, 2], 6.],
            [[1, 2, 3], 5.],
            [[1, 3, 1], 10.],
            [[1, 3, 2], 9.],
            [[1, 3, 3], 8.],
            [[2, 1, 1], 6.],
            [[2, 1, 2], 5.],
            [[2, 1, 3], 4.],
            [[2, 2, 1], 9.],
            [[2, 2, 2], 8.],
            [[2, 2, 3], 7.],
            [[2, 3, 1], 12.],
            [[2, 3, 2], 11.],
            [[2, 3, 3], 10.],
            [[3, 1, 1], 8.],
            [[3, 1, 2], 7.],
            [[3, 1, 3], 6.],
            [[3, 2, 1], 11.],
            [[3, 2, 2], 10.],
            [[3, 2, 3], 9.],
            [[3, 3, 1], 14.],
            [[3, 3, 2], 13.],
            [[3, 3, 3], 12.],
        ];
    }

    /**
     * @test Points and values not sorted
     *
     * >>> xs = [1, 2, 3]
     * >>> ys = [1, 2, 3]
     * >>> zs = [1, 2, 3]
     * >>>
     * >>> def func(x, y, z):
     * ...     return 2 * x + 3 * y - z
     * ...
     * >>>
     * >>> values = [[[func(x, y, z) for z in [3, 2, 1]] for y in [3, 2, 1]] for x in [3, 2, 1]]
     * >>>
     * >>> my_interpolating_function = RegularGridInterpolator((xs, ys, zs), values, method='linear')
     * >>> my_interpolating_function([1, 1, 2])
     * array([13.])
     */
    public function testPointsAndValuesNotSorted()
    {
        // Given
        $xs = [1, 2, 3];
        $ys = [1, 2, 3];
        $zs = [1, 2, 3];

        // And
        $func = function ($x, $y, $z) {
            return 2 * $x + 3 * $y - $z;
        };

        // And
        $data = [];
        foreach ([3, 2, 1] as $i => $x) {
            foreach ([3, 2, 1] as $j => $y) {
                foreach ([3, 2, 1] as $k => $z) {
                    $data[$i][$j][$k] = $func($x, $y, $z);
                }
            }
        }

        // When
        $interp = new RegularGridInterpolator([$xs, $ys, $zs], $data, 'linear');
        $result = $interp([1, 1, 2]);

        // Then
        $this->assertEqualsWithDelta(13, $result, 0.00001);
    }

    /**
     * @test Stack Overflow Example
     * https://stackoverflow.com/questions/30056577/correct-usage-of-scipy-interpolate-regulargridinterpolator
     *
     * from scipy.interpolate import RegularGridInterpolator
     * >>> x = np.linspace(0, 1, 3)
     * >>> x
     * array([0. , 0.5, 1. ])
     * >>> X, Y, Z = np.meshgrid(x, x, x, indexing='ij')
     * >>> X
     * array([[[0. , 0. , 0. ],
     * [0. , 0. , 0. ],
     * [0. , 0. , 0. ]],
     *
     * [[0.5, 0.5, 0.5],
     * [0.5, 0.5, 0.5],
     * [0.5, 0.5, 0.5]],
     *
     * [[1. , 1. , 1. ],
     * [1. , 1. , 1. ],
     * [1. , 1. , 1. ]]])
     * >>> Y
     * array([[[0. , 0. , 0. ],
     * [0.5, 0.5, 0.5],
     * [1. , 1. , 1. ]],
     *
     * [[0. , 0. , 0. ],
     * [0.5, 0.5, 0.5],
     * [1. , 1. , 1. ]],
     *
     * [[0. , 0. , 0. ],
     * [0.5, 0.5, 0.5],
     * [1. , 1. , 1. ]]])
     * >>> Z
     * array([[[0. , 0.5, 1. ],
     * [0. , 0.5, 1. ],
     * [0. , 0.5, 1. ]],
     *
     * [[0. , 0.5, 1. ],
     * [0. , 0.5, 1. ],
     * [0. , 0.5, 1. ]],
     *
     * [[0. , 0.5, 1. ],
     * [0. , 0.5, 1. ],
     * [0. , 0.5, 1. ]]])
     * >>>
     * >>>
     * >>> vals = np. sin(X) + np.\cos(Y) + np.tan(Z)
     * >>> vals
     * array([[[1.        , 1.54630249, 2.55740772],
     * [0.87758256, 1.42388505, 2.43499029],
     * [0.54030231, 1.0866048 , 2.09771003]],
     *
     * [[1.47942554, 2.02572803, 3.03683326],
     * [1.3570081 , 1.90331059, 2.91441583],
     * [1.01972784, 1.56603033, 2.57713557]],
     *
     * [[1.84147098, 2.38777347, 3.39887871],
     * [1.71905355, 2.26535604, 3.27646127],
     * [1.38177329, 1.92807578, 2.93918102]]])
     *
     * >>> rgi = RegularGridInterpolator(points=[x, x, x], values=vals)
     * >>> tst = (0.47, 0.49, 0.53)
     * >>> rgi(tst)
     * array(1.93765972)
     */
    public function testStackOverflowExample()
    {
        // Given
        $x = [0., 0.5, 1.];

        // And
        $vals = [
            [
                [1.        , 1.54630249, 2.55740772],
                [0.87758256, 1.42388505, 2.43499029],
                [0.54030231, 1.0866048 , 2.09771003]
            ],
            [
                [1.47942554, 2.02572803, 3.03683326],
                [1.3570081 , 1.90331059, 2.91441583],
                [1.01972784, 1.56603033, 2.57713557]],
            [
                [1.84147098, 2.38777347, 3.39887871],
                [1.71905355, 2.26535604, 3.27646127],
                [1.38177329, 1.92807578, 2.93918102]
            ]
        ];

        // And
        $tst = [0.47, 0.49, 0.53];

        // When
        $rgi    = new RegularGridInterpolator([$x, $x, $x], $vals, 'linear');
        $result = $rgi($tst);

        // Then
        $this->assertEqualsWithDelta(1.93765972, $result, 0.00001);
    }

    /**
     * @test   Bad method (not defined)
     * @throws BadDataException
     */
    public function testBadMethodException()
    {
        // Given
        $invalidMethod = 'methodDoesNotExist';

        // Then
        $this->expectException(BadDataException::class);

        // When
        $rgi = new RegularGridInterpolator([0], [0], $invalidMethod);
    }

    /**
     * @test   Bad values - there are two point arrays, but values have one dimension
     * @throws BadDataException
     */
    public function testBadValuesException()
    {
        // Given
        $points = [[0], [1]];
        $values = [0];

        // Then
        $this->expectException(BadDataException::class);

        // When
        $rgi = new RegularGridInterpolator($points, $values);
    }

    /**
     * @test   Bad pint dimension - The requested sample points xi have dimension 2, but this RegularGridInterpolator has dimension 1
     * @throws BadDataException
     */
    public function testInvokeBadPointDimensionException()
    {
        // Given
        $interp = new RegularGridInterpolator([0], [0]);

        // Then
        $this->expectException(BadDataException::class);

        // When
        $interp([0, 2]);
    }

    /**
     * Test fixture - Create a 4-D grid of 3 points in each dimension
     *
     * Based off of Python SciPy unit test fixture
     * def _get_sample_4d(self):
     *     # create a 4-D grid of 3 points in each dimension
     *     points = [(0., .5, 1.)] * 4
     *     values = np.asarray([0., .5, 1.])
     *     values0 = values[:, np.newaxis, np.newaxis, np.newaxis]
     *     values1 = values[np.newaxis, :, np.newaxis, np.newaxis]
     *     values2 = values[np.newaxis, np.newaxis, :, np.newaxis]
     *     values3 = values[np.newaxis, np.newaxis, np.newaxis, :]
     *     values = (values0 + values1 * 10 + values2 * 100 + values3 * 1000)
     *     return points, values
     *
     * PHP code to generate data would look like:
     * $points = [[0.0, 0.5, 1.0], [0.0, 0.5, 1.0], [0.0, 0.5, 1.0], [0.0, 0.5, 1.0]];
     * $values = [];
     * $v = [0, 0.5, 1.];
     * for ($x = 0; $x < 3; $x++) {
     *     for ($y = 0; $y < 3; $y++) {
     *         for ($z = 0; $z < 3; $z++) {
     *             for ($m = 0; $m < 3; $m++) {
     *                 $values[$x][$y][$z][$m] = $v[$x] + $v[$y] * 10 + $v[$z] * 100 + +$v[$m] * 1000;
     *             }
     *         }
     *     }
     * }
     * return [$points, $values];
     *
     * @return array (points, values)
     */
    private function getSample4d(): array
    {
        $points = [[0.0, 0.5, 1.0], [0.0, 0.5, 1.0], [0.0, 0.5, 1.0], [0.0, 0.5, 1.0]];
        $values = [
            [
                [
                    [0.0000e+00, 5.0000e+02, 1.0000e+03],
                    [5.0000e+01, 5.5000e+02, 1.0500e+03],
                    [1.0000e+02, 6.0000e+02, 1.1000e+03]
                ],
                [
                    [5.0000e+00, 5.0500e+02, 1.0050e+03],
                    [5.5000e+01, 5.5500e+02, 1.0550e+03],
                    [1.0500e+02, 6.0500e+02, 1.1050e+03]
                ],
                [
                    [1.0000e+01, 5.1000e+02, 1.0100e+03],
                    [6.0000e+01, 5.6000e+02, 1.0600e+03],
                    [1.1000e+02, 6.1000e+02, 1.1100e+03]
                ]
            ],
            [
                [
                    [5.0000e-01, 5.0050e+02, 1.0005e+03],
                    [5.0500e+01, 5.5050e+02, 1.0505e+03],
                    [1.0050e+02, 6.0050e+02, 1.1005e+03]
                ],
                [
                    [5.5000e+00, 5.0550e+02, 1.0055e+03],
                    [5.5500e+01, 5.5550e+02, 1.0555e+03],
                    [1.0550e+02, 6.0550e+02, 1.1055e+03]
                ],
                [
                    [1.0500e+01, 5.1050e+02, 1.0105e+03],
                    [6.0500e+01, 5.6050e+02, 1.0605e+03],
                    [1.1050e+02, 6.1050e+02, 1.1105e+03]
                ]
            ],
            [
                [
                    [1.0000e+00, 5.0100e+02, 1.0010e+03],
                    [5.1000e+01, 5.5100e+02, 1.0510e+03],
                    [1.0100e+02, 6.0100e+02, 1.1010e+03]
                ],
                [
                    [6.0000e+00, 5.0600e+02, 1.0060e+03],
                    [5.6000e+01, 5.5600e+02, 1.0560e+03],
                    [1.0600e+02, 6.0600e+02, 1.1060e+03]
                ],
                [
                    [1.1000e+01, 5.1100e+02, 1.0110e+03],
                    [6.1000e+01, 5.6100e+02, 1.0610e+03],
                    [1.1100e+02, 6.1100e+02, 1.1110e+03]
                ]
            ]
        ];

        return [$points, $values];
    }
}
