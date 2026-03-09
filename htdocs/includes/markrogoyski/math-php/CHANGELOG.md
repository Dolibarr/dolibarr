# MathPHP Change Log

## v2.8.1 - 2023-05-18

### Improvements
* Internal improvements to improve conformance with static analysis tools

## v2.8.0 - 2023-05-07

### New Features
* Matrix `rowAddVector`
* Matrix `columnAddVector`

### Improvements
* Better error handling and exception message in `Sequence\NonIntenger::hyperharmonic`
* Internal code improvements to conform to static analysis checks

### Backwards Incompatible Changes
* Helper method names changed (public abstract methods but not part of published interface)
  * `NumericalDifferentiation::isTargetInPoints` changed to `assertTargetInPoints`
  * `NumericalDifferentiation::isSpacingConstant` changed to `assertSpacingConstant`

## v2.7.0 - 2022-12-31

### Improvements
* Improved algorithm for `regularizedIncompleteBeta`: Addresses issue 458
* Issue 456: Improved PHPDoc blocks: Changed "number" to "int|float"
* Added PHP 8.2 for CI test target

## v2.6.0 - 2022-04-10

### Improvements
* `Average::truncatedMean` behavior at 50% trim made consistent
* PHP 8.1 compatibility improvements

### Backwards Incompatible Changes
# `Average::truncatedMean` throws exception if trim percent greater than 50% rather than error or unpredictable results.

## v2.5.0 - 2021-11-21

### New Features
* Special function `logbeta`
* Special function `logGamma`
* Special function `logGammaCorr`
* Special function `stirlingError`

### Improvements
* Improvements in StudentT continuous distribution
* Improvements in special function `gamma`
* Improvements in special function `beta`

### Bug Fixes
* Issue 393 (regularizedIncompleteBeta NAN)
* Issue 429 (Linear regression CI division by zero)

## v2.4.0 - 2021-07-27

### New Features
* Complex Exponential (`exp`)
* Complex Exponentiation (`pow`)
* Zipf's Law Discrete Distribution
* Generalized harmonic non-integer sequence

### Improvements
* Fixed Complex `polarForm` to compute the right values
* Fixed `hyperharnomic` non-integer sequence. Previously was computing the wrong thing
* Fixed how `ArbitraryInterger` handles `pow` of negative exponents

### Backwards Incompatible Changes
* Complex `polarForm` now returns an array rather than a Complex number, as the Complex return was incorrect
* Interface to `hyperharmonic` non-integer sequence changed due to previous implementation being incorrect

## v2.3.0 - 2021-07-14

### New Features
* Matrix SVD (Singular Value Decomposition)
* Polynomial companion matrix

## v2.2.0 - 2021-07-11

### New Features
* PLS (Partial Least Squares Regression)

### Improvements
* Add custom `__debugInfo` to `NumericMatrix`

## v2.1.0 - 2021-07-07

### New Features
* Quaternion numbers

## v2.0.0 - 2021-05-09

### New Features
* Matrix Improvements
  * `walk` method to map a function to all values without mutation or returning a value
  * `MatrixFactory` creates more matrix types
  * `MatrixFactory::createNumeric` to create `NumericMatrix` types
  * `MatrixFactory::createFromRowVector`
  * `MatrixFactory::createFromColumnVector`
  * Internal `ObjectMatrix` improvements
    * Add `trace`
    * Add `scalarMultiply`
  * Add initial `ComplexMatrix`
* Sample data People

### Improvements
* Bug fixes
  * Issue 414 fixed - PCA/Eigenvalue convergence
  * Issue 413 fixed - matrix solve with singular matrix using RREF

## Migration - Upgrading to v2.0 from v1.0
* PHP minimum version now 7.2 (was 7.0)
* Deprecated code removed (backwards-incompatible change)
  * `MathPHP\Statistics\Distance::kullbackLeiblerDivergence` removed (Use `MathPHP\Statistics\Divergence::kullbackLeibler` instead)
  * `MathPHP\Statistics\Distance::jensenShannonDivergence` removed (Use `MathPHP\Statistics\Divergence::jensenShannon` instead)
  * Matrix Decompositions no longer implement `\ArrayAccess` interface to access decomposition matrixes. Use properties instead.
    * `MathPHP\LinearAlgebra\Decomposition\Cholesky`
      * `$cholesky['L']`, `$cholesky['Lᵀ']`, `$cholesky['LT']` removed, use `$cholesky->L`, `$cholesky->Lᵀ`, `$cholesky->LT` instead.
    * `MathPHP\LinearAlgebra\Decomposition\Crout`
      * `$crout['L']`, `$crout['U']` removed, use `$crout->L`, `$crout->U` instead.
    * `MathPHP\LinearAlgebra\Decomposition\LU`
      * `$LU['L']`, `LU['U']`, `LU['P']` removed, use `$LU->L`, `$LU->U`, `$LU->P` instead.
    * `MathPHP\LinearAlgebra\Decomposition\QR`
      * `$QR['Q']`, `$QR['R']` removed, use `$QR->Q`, `$QR->R` instead.
* Methods renamed (backwards-incompatible change)
  * `MathPHP\Statistics\Distance::bhattacharyyaDistance` renamed to `MathPHP\Statistics\Distance::bhattacharyya`
  * `MathPHP\Statistics\Distance::hellingerDistance` renamed to `MathPHP\Statistics\Distance::hellinger`
* Moved Functionality (backwards-incompatible change)
  * `MathPHP\Functions\Polynomial` moved to `MathPHP\Expression\Polynomial`
  * `MathPHP\Functions\Piecewise` moved to `MathPHP\Expression\Piecewise`
* Matrix internal refactoring
  * Note: These changes will not affect any client code as long as matrices were created using `MatrixFactory`.
  * `Matrix` is not a base abstract class for all matrix classes to extend
  * `Matrix` renamed `NumericMatrix`
  * `Matrix` base method `createZeroValue`
    * Use case is various `ObjectMatrix` classes that implement `ObjectArithmetic`
  * `RowVector` removed. Use `MatrixFactory::createFromRowVector` instead
  * `ColumnVector` removed. Use `MatrixFactory::createFromColumnVector` instead

## v1.11.0 - 2021-05-09

### Improvements
* Bugfix (Issue 413): Matrix solve with singular matrix using RREF
* Bugfix (Issue 414): PCA/Eigenvalue convergence

## v1.10.0 - 2020-12-19

### Improvements
* Bugfix (Issue 356): Fix Finance IRR NANs

## v1.9.0 - 2020-12-13

### New Features
* Vector min and max
* Arithmetic isqrt (integer square root)

### Improvements
* Remove Travis CI (Moved CI to Github Actions in v1.8.0 release)
* Rearrange non-code files

## v1.8.0 - 2020-12-11

### Improvements
* Improve permutations algorithm to be more efficient and more numerically stable
* Qualify PHP function names with root namespace
* Move CI to Github Actions

## v1.7.0 - 2020-11-15

### New Features
* Algebra linear equation of one variable
* Rational number inverse
* Rational number pow

### Improvements
* Improve combinations algorithm to be more efficient and more numerically stable
* Internal Matrix class reorganization

## v1.6.0 - 2020-10-22

### New Features
* Special function regularized lower incomplete gamma
* Cereal sample data set

### Improvements
* Define boundary condition for lower incomplete gamma function

## v1.5.0 - 2020-10-12

### New Features
* Matrix LU solve
* Matrix QR solve

### Improvements
* Bugfix (Issue 386) Matrix solve improvements
* Matrix solve has optional method parameter to force a solve method
* Bugfix ArbitraryInteger multiplication sign not taken into account

## v1.4.0 - 2020-10-02

### New Features
* Multivariate Regular Grid Interpolation
* Jensen-Shannon Distance
* Canberra Distance
* Search Sorted
* Search ArgMax
* Search NanArgMax
* Search ArgMin
* Search NanArgMin
* Search NonZero

### Improvements
 * Divergence factored out of Distance into new Divergence class

### Backwards Incompatible Changes
 * Legacy Distance divergences marked as deprecated (To be removed in v2.0.0)

## v1.3.0 - 2020-08-24

### New Features
* LinearAlgebra\Vector
  * Angle between two vectors
  * L¹ distance of two vectors
  * L² distance of two vectors
  * Minkowski distance of two vectors
* Statistics\Distance
  * Minkowski distance
  * Euclidean distance (L² distance)
  * Manhattan distance (Taxicab geometry, L¹ distance, etc.)
  * Cosine distance
  * Cosine similarity

## v1.2.0 - 2020-07-24

### New Features
* Ranking
  * Ordinal ranking
  * Standard competition ranking
  * Modified competition ranking
  * Fractional ranking

### Improvements
* (Issue 380) Fixed Spearman's Rho calculation when there are rank ties

## v1.1.0 - 2020-04-19

### New Features
- Arithmetic modulo

### Improvements
- Improved matrix multiplication performance using cache-oblivious algorithm optimization

## v1.0.0 - 2020-04-14

Initial version 1.0.0 release!

## v0.62.0 - 2020-04-08

### Improvements
- Internal improvements

## v0.61.0 - 2020-03-22

### New Features
* Multivariate Hypergeometric distribution

## v0.60.0 - 2020-02-27

### New Features
- Sample Data
  - MtCars
  - Iris
  - ToothGrowth
  - PlantGrowth
  - UsArrests

## v0.59.0 - 2020-02-19

### New Features
- Add population and sample kurtosis
- Changed default kurtosis algorithm to the more common population kurtosis
- kurtosis now takes an optional parameter to set the kurtosis type algorithm

## v0.58.0 - 2020-02-06

### Improvements
* Changed default skewness algorithm to the more common sample skewness
* skewness now takes an optional parameter to set the skewness type algorithm
* Improvements to skewness algorithms

## v0.57.0 - 2020-01-07

### New Features
* Number\Rational basic getters
  * getWholePart
  * getNumerator
  * getDenominator
* Set Theory n-ary Cartesian product

### Improvements
* Data direction control for Matrix meanDeviation and covarianceMatrix
* Algebra factors performance improvement

## v0.56.0 - 2019-12-03

### New Features
* Number Theory
  * isDeficientNumber
  * isAbundantNumber
  * aliquotSum
  * radical
  * totient
  * cototient
  * reducedTotient
  * mobius
  * isSquarefree
  * isRefactorableNumber
  * isSphenicNumber
  * numberOfDivisors
  * sumOfDivisors

### Improvements
* Optimization of prime factorization algorithm

## v0.55.0 - 2019-11-19

### New Features
- Arbitrary length integers

### Improvements
- Factorial optimization

## v0.54.0 - 2019-10-12

### New Features
- Matrix isNilpotent
- Matrix isRectangularDiagonal
- Matrix mapRows
- MathPHP logo

### Improvements
* MatrixFactory random matrix custom lower and upper bounds for random number
* PSR-12 style compliance
* Bugfix: powerIteration random failure - [Issue 346](https://github.com/markrogoyski/math-php/issues/346)

## v0.53.0 - 2019-09-09

### New Features
* Matrix QR decomposition using Householder reflections
* Matrix Householder transformation
* MatrixFactory random matrix
* MatrixFactory givens rotation matrix
* Matrix isIdempotent
* Matrix Eigenvalue power iteration
* Matrix Eigenvalue jacobi method
* Arithmetic root (nᵗʰ root)
* Vector arithmetic multiply and divide
* Vector Iterator interface

### Improvements
* Internal improvements to Matrix
* Matrix decompositions returned as objects
* Matrix Cholesky decomposition provides L transpose

## v0.52.0 - 2019-07-11

### New Features
* Grubb's test for statistical outliers

## v0.51.0 - 2019-06-05

### New Features
* Matrix rowSums
* Matrix columnSums
* Matrix rowMeans
* Matrix columnMeans
* Matrix isNormal
* MatrixFactory diagonal matrix creation method
* MatrixFactory vandermonde matrix creation method

### Improvements
* Set custom Matrix tolerances
* Various internal improvements

### Backwards Incompatible Changes
* Remove Matrix sampleMeans (use rowMeans or columnMeans instead)
* MatrixFactory create method only works with 2d arrays. 1d arrays no longer work. (use diagonal and vandermonde factory methods instead)
* Statistics methods throw exceptions instead of returning null on bad input
* Change return type of LagrangePolynomial to Polynomial

## v0.50.0 - 2019-04-22

### New Features
* Matrix isOrthogonal
* Matrix isEqual
* Harmonic sequence
* Hyperharmonic sequence
* Map\Single reciprocal

### Improvements
* Support methods for almost equal
* Matrix getDiagonalElements works for non-square matrices
* Use more efficient algorithm in Matrix isSymmetric
* Use more efficient algorithm in Matrix isSkewSymmetric

### Backwards Incompatible Changes
* Statistics methods throw exceptions instead of returning null on bad input

## v0.49.0 - 2019-02-23

### New Features
- Matrix augmentAbove
- Matrix augmentLeft

### Improvements
- Object matrix multiplication

## v0.48.0 - 2018-12-15

### New Features
- Matrix submatrix
- Mahalanobis distance
- Bernoulli distribution mean, median, mode and variance
- Binomial distribution mean and variance
- Geometric distribution mean, median, mode and variance
- Hypergeometric distribution mode and variance
- NegativeBinomial (Pascal) distribution CDF, mean, mode and variance
- Poisson distribution mean, median, mode and variance
- Discrete Uniform distribution variance

### Improvements
- Binomial distribution PMF uses more numerically stable multiplication method
- Fix potential divide by zero in TheilSen regression

### Backwards Incompatible Changes
- Multinomial distribution moved from Discrete to Multivariate namespace

## v0.47.0 - 2018-11-21

### New Features
* Beta distribution median, mode, variance
* Cauchy distribution variance
* ChiSquared distribution mode, variance
* Exponential distribution median, mode, variance
* F distribution mode, variance
* Gamma distribution median, mode, variance
* Laplace distribution mode, variance
* Logistic distribution mode, vaiance
* LogLogistic distribution median, mode, variance
* LogNormal distribution mode, variance
* Normal distribution mode, variance
* StandardNormal distribution mode, variance
* StudentT distribution mode, variance
* Uniform distribution median, mode, variance
* Weibull distribution median, mode

### Improvements
* Normal distribution rand algorithm changed to Box–Muller transform

## v0.46.0 - 2018-10-28

### New Features
* NumberTheory isPerfectNumber
* Sequence perfectNumber

### Improvements
* Improve README documentation for continuous distributions
* Updates to build tools
* General improvements

## v0.45.0 - 2018-09-24

### Improvements
- Add Beta distribution inverse quantile function
- Improvements to Weibull distribution
- Improvements to Cauchy distribution
- Improvements to Laplace distribution
- Improvements to Logistic distribution
- Improvements to LogNormal distribution
- Improvements to Normal distribution
- Improvements to Pareto distribution
- Improvements to Algebra cubic/quartic complex root handling

## v0.44.0 - 2018-08-29

### Improvements
- [[Issue 271]](https://github.com/markrogoyski/math-php/issues/271) Improvements to documentation
- [[Issue 269]](https://github.com/markrogoyski/math-php/issues/269) Add closed-form inverse function for Exponential distribution

## v0.43.0 - 2018-05-21

### New Features
* Arithmetic copySign
* Matrix negate
* Matrix isSkewSymmetric

## v0.42.0 - 2018-05-09

### New Features
* Weighted mean
* Weighted sample variance
* Weighted covariance
* Weighted correlation coefficient

### Improvements
* Minor code improvements

## v0.41.0 - 2018-04-23

### New Features
* Arithmetic almostEqual

### Improvements
* Statistics\Average::mode improved to work with non-integer values
* Various minor code improvements

## v0.40.0 - 2018-03-22

### New Features
* Simpler interface for Significance ```tTest``` for one and two samples

### Improvements
* T test for two samples uses more robust Welch test
* Improvements to Normal and Standard Normal continuous distributions
* General improvements to continuous distributions

## v0.39.0 - 2018-02-27

### Improvements
* Upgrade unit testing framework to PHPUnit 6
* Update unit tests for PHPUnit 6 compatibility
* Add PHP 7.2 to continuous integration tests

## v0.38.0 - 2017-12-10

### Improvements
* Percentile reimplemented to use linear interpolation between closest ranks method - Second variant, C = 1
* General code improvements
* Better error and exception handling

## v0.37.0 - 2017-10-23

### Improvements
- Change probability distributions to be objects instead of static methods

### Backwards Incompatible Changes
- Change probability distributions to be objects instead of static methods

## v0.36.0 - 2017-09-26

### New Features
* Rational number
* Gamma distribution mean

### Improvements
* Add .gitignore file

## v0.35.0 - 2017-08-20

### New Features
* Matrix isTridiagonal
* Matrix isUpperHessenberg
* Matrix isLowerHessenberg
* Matrix getSuperdiagonalElements
* Matrix getSubdiagonalElements

### Improvements
* [Issue 242 - documentation improvement](https://github.com/markrogoyski/math-php/issues/242)

## v0.34.0 - 2017-08-12

### New Features
- Multivariate normal distribution

## v0.33.0 - 2017-08-04

### New Features
- Kernel density estimation

## v0.32.0 - 2017-07-24

### New Features
* Matrix Crout decomposition
* Categorical discrete distribution

## v0.31.0 - 2017-07-02

### New Features
* Hypergeometric distribution
* Discrete uniform distribution

## v0.30.0 - 2017-06-11

### New Features
* Dirichlet multivariate distribution
* Gamma distribution
* Initial eigenvalue matrix method
* Initial eigenvector matrix method
* Confidence ellipse

### Improvements
* Internal Bitwise addition

## v0.29.0 - 2017-05-21

### New Features
- Matrix rank
- ObjectArithmetic interface
- Polynomial implements ObjectArithmetic
- ObjectSquareMatrix
- Polynomial negate

### Improvements
- Refactor Matrix REF algorithm
- Refactor Matrix RREF algorithm
- Support functions for better handling of infinitesimal floating-point zero-like quantities
- Fix bug in Polynomial degree calculation
- Refactored Polynomial::add() to be simpler and faster

## v0.28.0 - 2017-05-02

### New Features
* Matrix adjugate
* Polynomial subtract

### Improvements
* Internal refactoring/improvements
  * Tests namespace for unit tests
  * Standardize method naming convention
  * Update PHPUnit exception assertion
  * Replace class strings in tests with class constants

## v0.27.0 - 2017-04-23

### New Features
* Matrix
  * Cholesky decomposition
  * isRref
  * Exchange matrix
  * isInvolutory
  * isSignature
  * Hilbert matrix
  * isUpperBidiagonal
  * isLowerBidiagonal
  * isBidiagonal
* Quartic function roots
* Trigonometry unit circle
* Integer
  * isOdd
  * isEven

## v0.26.0 - 2017-04-15

### New Features
* Initial Complex number class
* Complex number support to quadratic and cubic equations
* Initial Eigenvalue strategy class (2x2 and 3x3 matrices using root equations)
* Matrix
  * isLowerTriangular
  * isUpperTriangular
  * isTriangular
  * isDiagonal
* Beta function convenience method

### Improvements
* Add BadDataException to LeastSquares regression method trait if degrees of freedom is 0
* Complex Root of Quadratic Function

## v0.25.0 - 2017-04-01

### New Features
* Matrix
  * isSingular
  * isNonsingular
  * isInvertible
  * leadingPrincipalMinor
  * isPositiveDefinite
  * isPositiveSemidefinite
  * isNegativeDefinite
  * isNegativeSemidefinite
* Number Theory
  * Integer coprime
* Arithmetic
  * digitSum
  * digitalRoot
* Basic sequences
  * digitSum
  * digitalRoot

## v0.24.0 - 2017-03-26

### New Features
* Arithmetic cube root
* Algebra cubic equation
* Matrix Kronecker sum
* Vector Kronecker product
* Number theory prime factorization

### Improvements
* Improved quadratic equation edge case handling

## v0.23.0 - 2017-03-12

### New Features
* Number Theory - Integers
 * Perfect powers
* Advanced Sequences
 * Perfect powers
 * Not perfect powers
 * Primes up to n
* Algebra
 * Quadratic equation

## v0.22.0 - 2017-01-31

### New Features
* Circular statistics (directional statistics)
 * Circular mean
 * Resultant length
 * Mean resultant length
 * Circular variance
 * Circular standard deviation
 * Describe
* Finance profitability index

### Improvements
* Update Finance payback to be both simple and discounted payback

## v0.21.0 - 2017-01-23

### New Features
* Finance interest payment
* Finance principle payment on an annuity
* Finance payback
* Make files for unit tests, linting, and code coverage

## v0.20.0 - 2017-01-12

### New Features
* Finance net present value
* Finance rate function
* Finance internal rate of return
* Finance modified internal rate of return
* Finance payment periods of an annuity

### Improvements
* Update Newton's Method to handle non-convergence and infinite slopes.

## v0.19.0 - 2016-12-31

### New Features
* Matrix sample mean
* Matrix mean deviation form
* Covariance matrix
* Matrix representation as array of column vectors
* Finance future value
* Finance present value

## v0.18.0 - 2016-12-28

### New Features
* Joint entropy
* Rényi entropy
* Perplexity
* Matrix scalar division
* Finance: Annual Equivalent Rate (AER)

### Improvements
* Fix vector pnorm to take absolute value of each element

### Backwards Incompatible Changes
* Refactor distances and divergences from InformationTheory\Entropy to Statistics\Distance

## v0.17.0 - 2016-12-21

### New Features
* Two-sample z significance test

## v.0.16.0 - 2016-12-18

### New Features
* Information Theory
 * Shannon entropy (bits, nats, hartleys)
 * Cross entropy
 * Bhattacharyya distance
 * Kullback-Leibler divergence
 * Hellinger distance
 * Jensen-Shannon divergence
* Linear Algebra
 * vectorMultiply method on Matrix to return Vector when multiply with a Vector

## v0.15.0 - 2016-11-10

### New Features
* Lazy caterer's sequence
* Magic squares sequence

## v0.14.0 - 2016-10-28

### New Features
* Look-and-say sequence

## v0.13.0 - 2016-10-17

### New Features
* Custom exception classes

### Improvements
* Refactor exceptions to use custom exception classes

## v0.12.0 - 2016-10-06

### New Features
* Softmax function
* Effect size η² (Eta-squared)
* Effect size η²p (Partial eta-squared)
* Effect size ω² (omega-squared)
* Effect size Cohen's ƒ²
* Effect size Cohen's q
* Effect size Cohen's d
* Effect size Hedges' g
* Effect size Glass' Δ (glass' delta)

### Improvements
* Replace mt_rand with random_int

## v0.11.0 - 2016-10-01

### Backwards Incompatible Changes
* Change root namespace from Math to MathPHP
  * (Run composer update to update autoloader)

## v0.10.0 - 2016-09-28

### New Features
* Clamped Cubic Spline Interpolation
* Custom variable in Polynomial class

## v0.9.0 - 2016-09-27

### New Features
* Natural cubic spline interpolation
* Vector direct product

## v0.8.0 - 2016-09-22

### New Features
* Set Theory
* Matrix kronecker product
* Matrix augment below

### Backwards Incompatible Changes
* Some null return values changed to NAN when computation is invalid


## v0.7.0 - 2016-09-19

### New Features
* Matrix solve linear system of equations
* Noncentral T distribution
* Piecewise function class
* Initial Finance class (pmt function)
* Vector scalar multiplication
* Vector normalization
* Vector scalar division
* Vector perpendicular operator
* Vector projections
* Vector perp and perp dot product

### Improvements
* Add getters to Polynomial for degree and coefficients
* Improvements to gamma function

## v0.6.1 - 2016-09-11

### Improvements
* Fix matrix determinant calculation

## v0.6.0 - 2016-09-10

### New Features
* Polynomial class
* Vector cross product

## v0.5.0 - 2016-09-07

### New Features
* Numerical Differentiation (\Math\NumericalAnalysis\NumericalDifferentiation)
  * Three Point Formula (\Math\NumericalAnalysis\NumericalDifferentiation\ThreePointFormula)
  * Five Point Formula (\Math\NumericalAnalysis\NumericalDifferentiation\FivePointFormula)
  * SecondDerivativeMidpointFormula (\Math\NumericalAnalysis\NumericalDifferentiation\SecondDerivativeMidpointFormula)
* Two-way ANOVA (\Math\Statistics\ANOVA)

## v0.4.0 - 2016-09-07

### New Features

* Nevilles Method (\Math\NumericalAnalysis\Interpolation)
* Newton Polynomial (\Math\NumericalAnalysis\Interpolation)

## v0.3.0 - 2016-09-06

### New Features

* Lagrange polynomials (\Math\NumericalAnalysis\Interpolation)
* Function arithmetic (\Math\Functions\Arithmetic)

## v0.2.0 - 2016-09-05

### New Features

* One-way ANOVA (```Math\Statistics\ANOVA```)
* χ² Table (```Math\Probability\Distribution\Table```)
* Five number summary (```Math\Statistics\Descriptive```)
* Simple sum of squares (```Math\Statistics\RandomVariable```)

### Improvements

* Refactor probability distribution tables (```Math\Probability\Distribution\Table```)
* Minor refactors

### Backwards Incompatible Changes

* Move probability distribution tables to new namespace
  * From ```Math\Probability``` to ```Math\Probability\Distribution\Table```

## v0.1.0 - 2016-09-02

### New Features

 * Algebra
 * Functions
   - Map
   - Special Functions
 * Linear Algebra
   - Matrix
   - Vector
 * Numerical Analysis
   - Numerical Integration
   - Root Finding
 * Probability
     - Combinatorics
     - Distributions
         * Continuous
         * Discrete
     - Standard Normal Table (Z Table)
     - t Distribution Table
 * Sequences
     - Basic
     - Advanced
 * Statistics
     - Averages
     - Correlation
     - Descriptive
     - Distributions
     - Experiments
     - Random Variables
     - Regressions
     - Significance Testing