![MathPHP Logo](https://github.com/markrogoyski/math-php/blob/master/docs/image/MathPHPLogo.png?raw=true)

### MathPHP - Powerful Modern Math Library for PHP

The only library you need to integrate mathematical functions into your applications. It is a self-contained library in pure PHP with no external dependencies.

[![Coverage Status](https://coveralls.io/repos/github/markrogoyski/math-php/badge.svg?branch=master)](https://coveralls.io/github/markrogoyski/math-php?branch=master)
[![License](https://poser.pugx.org/markrogoyski/math-php/license)](https://packagist.org/packages/markrogoyski/math-php)

Features
--------
 * [Algebra](#algebra)
 * [Arithmetic](#arithmetic)
 * Expression
   - [Polynomial](#expression---polynomial)
 * [Finance](#finance)
 * Functions
   - [Map](#functions---map---single-array)
   - [Special Functions](#functions---special-functions)
 * Information Theory
   - [Entropy](#information-theory---entropy)
 * Linear Algebra
   - [Matrix](#linear-algebra---matrix)
   - [Vector](#linear-algebra---vector)
 * Numbers
   - [Arbitrary Integer](#number---arbitrary-length-integers)
   - [Complex](#number---complex-numbers)
   - [Quaternion](#number---quaternion)
   - [Rational](#number---rational-numbers)
 * Number Theory
   - [Integers](#number-theory---integers)
 * Numerical Analysis
   - [Interpolation](#numerical-analysis---interpolation)
   - [Numerical Differentiation](#numerical-analysis---numerical-differentiation)
   - [Numerical Integration](#numerical-analysis---numerical-integration)
   - [Root Finding](#numerical-analysis---root-finding)
 * Probability
     - [Combinatorics](#probability---combinatorics)
     - Distributions
         * [Continuous](#probability---continuous-distributions)
         * [Discrete](#probability---discrete-distributions)
         * [Multivariate](#probability---multivariate-distributions)
         * [Tables](#probability---distribution-tables)
 * [Sample Data](#sample-data)
 * [Search](#search)
 * Sequences
     - [Basic](#sequences---basic)
     - [Advanced](#sequences---advanced)
     - [NonInteger](#sequences---non-integer)
 * [Set Theory](#set-theory)
 * Statistics
     - [ANOVA](#statistics---anova)
     - [Averages](#statistics---averages)
     - [Circular](#statistics---circular)
     - [Correlation](#statistics---correlation)
     - [Descriptive](#statistics---descriptive)
     - [Distance](#statistics---distance)
     - [Distributions](#statistics---distributions)
     - [Divergence](#statistics---divergence)
     - [Effect Size](#statistics---effect-size)
     - [Experiments](#statistics---experiments)
     - [Kernel Density Estimation](#statistics---kernel-density-estimation)
     - Multivariate
        * [PCA (Principal Component Analysis)](#statistics---multivariate---principal-component-analysis)
        * [PLS (Partial Least Squares Regression)](#statistics---multivariate---partial-least-squares-regression)
     - [Outlier](#statistics---outlier)
     - [Random Variables](#statistics---random-variables)
     - [Regressions](#statistics---regressions)
     - [Significance Testing](#statistics---significance-testing)
 * [Trigonometry](#trigonometry)

Setup
-----

 Add the library to your `composer.json` file in your project:

```javascript
{
  "require": {
      "markrogoyski/math-php": "2.*"
  }
}
```

Use [composer](http://getcomposer.org) to install the library:

```bash
$ php composer.phar install
```

Composer will install MathPHP inside your vendor folder. Then you can add the following to your
.php files to use the library with Autoloading.

```php
require_once __DIR__ . '/vendor/autoload.php';
```

Alternatively, use composer on the command line to require and install MathPHP:

```
$ php composer.phar require markrogoyski/math-php:2.*
```

### Minimum Requirements
 * PHP 7.2

 Note: For PHP 7.0 and 7.1, use v1.0 (`markrogoyski/math-php:1.*`)

Usage
-----

### Algebra
```php
use MathPHP\Algebra;

// Greatest common divisor (GCD)
$gcd = Algebra::gcd(8, 12);

// Extended greatest common divisor - gcd(a, b) = a*a' + b*b'
$gcd = Algebra::extendedGcd(12, 8); // returns array [gcd, a', b']

// Least common multiple (LCM)
$lcm = Algebra::lcm(5, 2);

// Factors of an integer
$factors = Algebra::factors(12); // returns [1, 2, 3, 4, 6, 12]

// Linear equation of one variable: ax + b = 0
[$a, $b] = [2, 4]; // 2x + 4 = 0
$x       = Algebra::linear($a, $b);

// Quadratic equation: ax² + bx + c = 0
[$a, $b, $c] = [1, 2, -8]; // x² + 2x - 8
[$x₁, $x₂]   = Algebra::quadratic($a, $b, $c);

// Discriminant: Δ = b² - 4ac
[$a, $b, $c] = [2, 3, 4]; // 3² - 4(2)(4)
$Δ           = Algebra::discriminant($a, $b, $c);

// Cubic equation: z³ + a₂z² + a₁z + a₀ = 0
[$a₃, $a₂, $a₁, $a₀] = [2, 9, 3, -4]; // 2x³ + 9x² + 3x -4
[$x₁, $x₂, $x₃]      = Algebra::cubic($a₃, $a₂, $a₁, $a₀);

// Quartic equation: a₄z⁴ + a₃z³ + a₂z² + a₁z + a₀ = 0
[$a₄, $a₃, $a₂, $a₁, $a₀] = [1, -10, 35, -50, 24]; // z⁴ - 10z³ + 35z² - 50z + 24 = 0
[$z₁, $z₂, $z₃, $z₄]      = Algebra::quartic($a₄, $a₃, $a₂, $a₁, $a₀);
```

### Arithmetic
```php
use MathPHP\Arithmetic;

$√x  = Arithmetic::isqrt(8);     // 2 Integer square root
$³√x = Arithmetic::cubeRoot(-8); // -2
$ⁿ√x = Arithmetic::root(81, 4);  // nᵗʰ root (4ᵗʰ): 3

// Sum of digits
$digit_sum    = Arithmetic::digitSum(99);    // 18
$digital_root = Arithmetic::digitalRoot(99); // 9

// Equality of numbers within a tolerance
$x = 0.00000003458;
$y = 0.00000003455;
$ε = 0.0000000001;
$almostEqual = Arithmetic::almostEqual($x, $y, $ε); // true

// Copy sign
$magnitude = 5;
$sign      = -3;
$signed_magnitude = Arithmetic::copySign($magnitude, $sign); // -5

// Modulo (Differs from PHP remainder (%) operator for negative numbers)
$dividend = 12;
$divisor  = 5;
$modulo   = Arithmetic::modulo($dividend, $divisor);  // 2
$modulo   = Arithmetic::modulo(-$dividend, $divisor); // 3
```

### Expression - Polynomial
```php
use MathPHP\Expression\Polynomial;

// Polynomial x² + 2x + 3
$coefficients = [1, 2, 3]
$polynomial   = new Polynomial($coefficients);

// Evaluate for x = 3
$x = 3;
$y = $polynomial($x);  // 18: 3² + 2*3 + 3

// Calculus
$derivative = $polynomial->differentiate();  // Polynomial 2x + 2
$integral   = $polynomial->integrate();      // Polynomial ⅓x³ + x² + 3x

// Arithmetic
$sum        = $polynomial->add($polynomial);       // Polynomial 2x² + 4x + 6
$sum        = $polynomial->add(2);                 // Polynomial x² + 2x + 5
$difference = $polynomial->subtract($polynomial);  // Polynomial 0
$difference = $polynomial->subtract(2);            // Polynomial x² + 2x + 1
$product    = $polynomial->multiply($polynomial);  // Polynomial x⁴ + 4x³ + 10x² + 12x + 9
$product    = $polynomial->multiply(2);            // Polynomial 2x² + 4x + 6
$negated    = $polynomial->negate();               // Polynomial -x² - 2x - 3

// Data
$degree       = $polynomial->getDegree();        // 2
$coefficients = $polynomial->getCoefficients();  // [1, 2, 3]

// String representation
print($polynomial);  // x² + 2x + 3

// Roots
$polynomial = new Polynomial([1, -3, -4]);
$roots      = $polynomial->roots();         // [-1, 4]

// Companion matrix
$companion = $polynomial->companionMatrix();
```

### Finance
```php
use MathPHP\Finance;

// Financial payment for a loan or annuity with compound interest
$rate          = 0.035 / 12; // 3.5% interest paid at the end of every month
$periods       = 30 * 12;    // 30-year mortgage
$present_value = 265000;     // Mortgage note of $265,000.00
$future_value  = 0;
$beginning     = false;      // Adjust the payment to the beginning or end of the period
$pmt           = Finance::pmt($rate, $periods, $present_value, $future_value, $beginning);

// Interest on a financial payment for a loan or annuity with compound interest.
$period = 1; // First payment period
$ipmt   = Finance::ipmt($rate, $period, $periods, $present_value, $future_value, $beginning);

// Principle on a financial payment for a loan or annuity with compound interest
$ppmt = Finance::ppmt($rate, $period, $periods, $present_value, $future_value = 0, $beginning);

// Number of payment periods of an annuity.
$periods = Finance::periods($rate, $payment, $present_value, $future_value, $beginning);

// Annual Equivalent Rate (AER) of an annual percentage rate (APR)
$nominal = 0.035; // APR 3.5% interest
$periods = 12;    // Compounded monthly
$aer     = Finance::aer($nominal, $periods);

// Annual nominal rate of an annual effective rate (AER)
$nomial = Finance::nominal($aer, $periods);

// Future value for a loan or annuity with compound interest
$payment = 1189.97;
$fv      = Finance::fv($rate, $periods, $payment, $present_value, $beginning)

// Present value for a loan or annuity with compound interest
$pv = Finance::pv($rate, $periods, $payment, $future_value, $beginning)

// Net present value of cash flows
$values = [-1000, 100, 200, 300, 400];
$npv    = Finance::npv($rate, $values);

// Interest rate per period of an annuity
$beginning = false; // Adjust the payment to the beginning or end of the period
$rate      = Finance::rate($periods, $payment, $present_value, $future_value, $beginning);

// Internal rate of return
$values = [-100, 50, 40, 30];
$irr    = Finance::irr($values); // Rate of return of an initial investment of $100 with returns of $50, $40, and $30

// Modified internal rate of return
$finance_rate      = 0.05; // 5% financing
$reinvestment_rate = 0.10; // reinvested at 10%
$mirr              = Finance::mirr($values, $finance_rate); // rate of return of an initial investment of $100 at 5% financing with returns of $50, $40, and $30 reinvested at 10%

// Discounted payback of an investment
$values  = [-1000, 100, 200, 300, 400, 500];
$rate    = 0.1;
$payback = Finance::payback($values, $rate); // The payback period of an investment with a $1,000 investment and future returns of $100, $200, $300, $400, $500 and a discount rate of 0.10

// Profitability index
$values              = [-100, 50, 50, 50];
$profitability_index = Finance::profitabilityIndex($values, $rate); // The profitability index of an initial $100 investment with future returns of $50, $50, $50 with a 10% discount rate
```

### Functions - Map - Single Array
```php
use MathPHP\Functions\Map;

$x = [1, 2, 3, 4];

$sums        = Map\Single::add($x, 2);      // [3, 4, 5, 6]
$differences = Map\Single::subtract($x, 1); // [0, 1, 2, 3]
$products    = Map\Single::multiply($x, 5); // [5, 10, 15, 20]
$quotients   = Map\Single::divide($x, 2);   // [0.5, 1, 1.5, 2]
$x²          = Map\Single::square($x);      // [1, 4, 9, 16]
$x³          = Map\Single::cube($x);        // [1, 8, 27, 64]
$x⁴          = Map\Single::pow($x, 4);      // [1, 16, 81, 256]
$√x          = Map\Single::sqrt($x);        // [1, 1.414, 1.732, 2]
$∣x∣         = Map\Single::abs($x);         // [1, 2, 3, 4]
$maxes       = Map\Single::max($x, 3);      // [3, 3, 3, 4]
$mins        = Map\Single::min($x, 3);      // [1, 2, 3, 3]
$reciprocals = Map\Single::reciprocal($x);  // [1, 1/2, 1/3, 1/4]
```

### Functions - Map - Multiple Arrays
```php
use MathPHP\Functions\Map;

$x = [10, 10, 10, 10];
$y = [1,   2,  5, 10];

// Map function against elements of two or more arrays, item by item (by item ...)
$sums        = Map\Multi::add($x, $y);      // [11, 12, 15, 20]
$differences = Map\Multi::subtract($x, $y); // [9, 8, 5, 0]
$products    = Map\Multi::multiply($x, $y); // [10, 20, 50, 100]
$quotients   = Map\Multi::divide($x, $y);   // [10, 5, 2, 1]
$maxes       = Map\Multi::max($x, $y);      // [10, 10, 10, 10]
$mins        = Map\Multi::mins($x, $y);     // [1, 2, 5, 10]

// All functions work on multiple arrays; not limited to just two
$x    = [10, 10, 10, 10];
$y    = [1,   2,  5, 10];
$z    = [4,   5,  6,  7];
$sums = Map\Multi::add($x, $y, $z); // [15, 17, 21, 27]
```

### Functions - Special Functions
```php
use MathPHP\Functions\Special;

// Gamma function Γ(z)
$z = 4;
$Γ = Special::gamma($z);
$Γ = Special::gammaLanczos($z);   // Lanczos approximation
$Γ = Special::gammaStirling($z);  // Stirling approximation
$l = Special::logGamma($z);
$c = Special::logGammaCorr($z);   // Log gamma correction

// Incomplete gamma functions - γ(s,t), Γ(s,x), P(s,x)
[$x, $s] = [1, 2];
$γ = Special::lowerIncompleteGamma($x, $s);
$Γ = Special::upperIncompleteGamma($x, $s);
$P = Special::regularizedLowerIncompleteGamma($x, $s);

// Beta function
[$x, $y] = [1, 2];
$β  = Special::beta($x, $y);
$lβ = Special::logBeta($x, $y);

// Incomplete beta functions
[$x, $a, $b] = [0.4, 2, 3];
$B  = Special::incompleteBeta($x, $a, $b);
$Iₓ = Special::regularizedIncompleteBeta($x, $a, $b);

// Multivariate beta function
$αs = [1, 2, 3];
$β  = Special::multivariateBeta($αs);

// Error function (Gauss error function)
$error = Special::errorFunction(2);              // same as erf
$error = Special::erf(2);                        // same as errorFunction
$error = Special::complementaryErrorFunction(2); // same as erfc
$error = Special::erfc(2);                       // same as complementaryErrorFunction

// Hypergeometric functions
$pFq = Special::generalizedHypergeometric($p, $q, $a, $b, $c, $z);
$₁F₁ = Special::confluentHypergeometric($a, $b, $z);
$₂F₁ = Special::hypergeometric($a, $b, $c, $z);

// Sign function (also known as signum or sgn)
$x    = 4;
$sign = Special::signum($x); // same as sgn
$sign = Special::sgn($x);    // same as signum

// Logistic function (logistic sigmoid function)
$x₀ = 2; // x-value of the sigmoid's midpoint
$L  = 3; // the curve's maximum value
$k  = 4; // the steepness of the curve
$x  = 5;
$logistic = Special::logistic($x₀, $L, $k, $x);

// Sigmoid function
$t = 2;
$sigmoid = Special::sigmoid($t);

// Softmax function
$𝐳    = [1, 2, 3, 4, 1, 2, 3];
$σ⟮𝐳⟯ⱼ = Special::softmax($𝐳);

// Log of the error term in the Stirling-De Moivre factorial series
$err = Special::stirlingError($n);
```

### Information Theory - Entropy
```php
use MathPHP\InformationTheory\Entropy;

// Probability distributions
$p = [0.2, 0.5, 0.3];
$q = [0.1, 0.4, 0.5];

// Shannon entropy
$bits  = Entropy::shannonEntropy($p);         // log₂
$nats  = Entropy::shannonNatEntropy($p);      // ln
$harts = Entropy::shannonHartleyEntropy($p);  // log₁₀

// Cross entropy
$H⟮p、q⟯ = Entropy::crossEntropy($p, $q);       // log₂

// Joint entropy
$P⟮x、y⟯ = [1/2, 1/4, 1/4, 0];
H⟮x、y⟯ = Entropy::jointEntropy($P⟮x、y⟯);        // log₂

// Rényi entropy
$α    = 0.5;
$Hₐ⟮X⟯ = Entropy::renyiEntropy($p, $α);         // log₂

// Perplexity
$perplexity = Entropy::perplexity($p);         // log₂
```

### Linear Algebra - Matrix
```php
use MathPHP\LinearAlgebra\Matrix;
use MathPHP\LinearAlgebra\MatrixFactory;

// Create an m × n matrix from an array of arrays
$matrix = [
    [1, 2, 3],
    [4, 5, 6],
    [7, 8, 9],
];
$A = MatrixFactory::create($matrix);

// Basic matrix data
$array = $A->getMatrix();  // Original array of arrays
$rows  = $A->getM();       // number of rows
$cols  = $A->getN();       // number of columns

// Basic matrix element getters (zero-based indexing)
$row = $A->getRow(2);
$col = $A->getColumn(2);
$Aᵢⱼ = $A->get(2, 2);
$Aᵢⱼ = $A[2][2];

// Row operations
[$mᵢ, $mⱼ, $k] = [1, 2, 5];
$R = $A->rowInterchange($mᵢ, $mⱼ);
$R = $A->rowExclude($mᵢ);             // Exclude row $mᵢ
$R = $A->rowMultiply($mᵢ, $k);        // Multiply row mᵢ by k
$R = $A->rowDivide($mᵢ, $k);          // Divide row mᵢ by k
$R = $A->rowAdd($mᵢ, $mⱼ, $k);        // Add k * row mᵢ to row mⱼ
$R = $A->rowAddScalar($mᵢ, $k);       // Add k to each item of row mᵢ
$R = $A->rowAddVector($mᵢ, $V);       // Add Vector V to row mᵢ
$R = $A->rowSubtract($mᵢ, $mⱼ, $k);   // Subtract k * row mᵢ from row mⱼ
$R = $A->rowSubtractScalar($mᵢ, $k);  // Subtract k from each item of row mᵢ

// Column operations
[$nᵢ, $nⱼ, $k] = [1, 2, 5];
$R = $A->columnInterchange($nᵢ, $nⱼ);
$R = $A->columnExclude($nᵢ);          // Exclude column $nᵢ
$R = $A->columnMultiply($nᵢ, $k);     // Multiply column nᵢ by k
$R = $A->columnAdd($nᵢ, $nⱼ, $k);     // Add k * column nᵢ to column nⱼ
$R = $A->columnAddVector($nᵢ, $V);    // Add Vector V to column nᵢ

// Matrix augmentations - return a new Matrix
$⟮A∣B⟯ = $A->augment($B);        // Augment on the right - standard augmentation
$⟮A∣I⟯ = $A->augmentIdentity();  // Augment with the identity matrix
$⟮A∣B⟯ = $A->augmentBelow($B);
$⟮A∣B⟯ = $A->augmentAbove($B);
$⟮B∣A⟯ = $A->augmentLeft($B);

// Matrix arithmetic operations - return a new Matrix
$A＋B = $A->add($B);
$A⊕B  = $A->directSum($B);
$A⊕B  = $A->kroneckerSum($B);
$A−B  = $A->subtract($B);
$AB   = $A->multiply($B);
$２A  = $A->scalarMultiply(2);
$A／2 = $A->scalarDivide(2);
$−A   = $A->negate();
$A∘B  = $A->hadamardProduct($B);
$A⊗B  = $A->kroneckerProduct($B);

// Matrix operations - return a new Matrix
$Aᵀ 　 = $A->transpose();
$D  　 = $A->diagonal();
$A⁻¹   = $A->inverse();
$Mᵢⱼ   = $A->minorMatrix($mᵢ, $nⱼ);        // Square matrix with row mᵢ and column nⱼ removed
$Mk    = $A->leadingPrincipalMinor($k);    // kᵗʰ-order leading principal minor
$CM    = $A->cofactorMatrix();
$B     = $A->meanDeviation();              // optional parameter to specify data direction (variables in 'rows' or 'columns')
$S     = $A->covarianceMatrix();           // optional parameter to specify data direction (variables in 'rows' or 'columns')
$adj⟮A⟯ = $A->adjugate();
$Mᵢⱼ   = $A->submatrix($mᵢ, $nᵢ, $mⱼ, $nⱼ) // Submatrix of A from row mᵢ, column nᵢ to row mⱼ, column nⱼ
$H     = $A->householder();

// Matrix value operations - return a value
$tr⟮A⟯   = $A->trace();
$|A|    = $a->det();              // Determinant
$Mᵢⱼ    = $A->minor($mᵢ, $nⱼ);    // First minor
$Cᵢⱼ    = $A->cofactor($mᵢ, $nⱼ);
$rank⟮A⟯ = $A->rank();

// Matrix vector operations - return a new Vector
$AB = $A->vectorMultiply($X₁);
$M  = $A->rowSums();
$M  = $A->columnSums();
$M  = $A->rowMeans();
$M  = $A->columnMeans();

// Matrix norms - return a value
$‖A‖₁ = $A->oneNorm();
$‖A‖F = $A->frobeniusNorm(); // Hilbert–Schmidt norm
$‖A‖∞ = $A->infinityNorm();
$max   = $A->maxNorm();

// Matrix reductions
$ref  = $A->ref();   // Matrix in row echelon form
$rref = $A->rref();  // Matrix in reduced row echelon form

// Matrix decompositions
// LU decomposition
$LU = $A->luDecomposition();
$L  = $LU->L;  // lower triangular matrix
$U  = $LU->U;  // upper triangular matrix
$P  = $LU-P;   // permutation matrix

// QR decomposition
$QR = $A->qrDecomposition();
$Q  = $QR->Q;  // orthogonal matrix
$R  = $QR->R;  // upper triangular matrix

// SVD (Singular Value Decomposition)
$SVD = $A->svd();
$U   = $A->U;  // m x m orthogonal matrix
$V   = $A->V;  // n x n orthogonal matrix
$S   = $A->S;  // m x n diagonal matrix of singular values
$D   = $A->D;  // Vector of diagonal elements from S

// Crout decomposition
$LU = $A->croutDecomposition();
$L  = $LU->L;  // lower triangular matrix
$U  = $LU->U;  // normalized upper triangular matrix

// Cholesky decomposition
$LLᵀ = $A->choleskyDecomposition();
$L   = $LLᵀ->L;   // lower triangular matrix
$LT  = $LLᵀ->LT;  // transpose of lower triangular matrix

// Eigenvalues and eigenvectors
$eigenvalues   = $A->eigenvalues();   // array of eigenvalues
$eigenvecetors = $A->eigenvectors();  // Matrix of eigenvectors

// Solve a linear system of equations: Ax = b
$b = new Vector(1, 2, 3);
$x = $A->solve($b);

// Map a function over each element
$func = function($x) {
    return $x * 2;
};
$R = $A->map($func);  // using closure
$R = $A->map('abs');  // using callable

// Map a function over each row
$array = $A->mapRows('array_reverse');  // using callable returns matrix-like array of arrays
$array = $A->mapRows('array_sum');     // using callable returns array of aggregate calculations

// Walk maps a function to all values without mutation or returning a value
$A->walk($func);

// Matrix comparisons
$bool = $A->isEqual($B);

// Matrix properties - return a bool
$bool = $A->isSquare();
$bool = $A->isSymmetric();
$bool = $A->isSkewSymmetric();
$bool = $A->isSingular();
$bool = $A->isNonsingular();           // Same as isInvertible
$bool = $A->isInvertible();            // Same as isNonsingular
$bool = $A->isPositiveDefinite();
$bool = $A->isPositiveSemidefinite();
$bool = $A->isNegativeDefinite();
$bool = $A->isNegativeSemidefinite();
$bool = $A->isLowerTriangular();
$bool = $A->isUpperTriangular();
$bool = $A->isTriangular();
$bool = $A->isDiagonal();
$bool = $A->isRectangularDiagonal();
$bool = $A->isUpperBidiagonal();
$bool = $A->isLowerBidiagonal();
$bool = $A->isBidiagonal();
$bool = $A->isTridiagonal();
$bool = $A->isUpperHessenberg();
$bool = $A->isLowerHessenberg();
$bool = $A->isOrthogonal();
$bool = $A->isNormal();
$bool = $A->isIdempotent();
$bool = $A->isNilpotent();
$bool = $A->isInvolutory();
$bool = $A->isSignature();
$bool = $A->isRef();
$bool = $A->isRref();

// Other representations of matrix data
$vectors = $A->asVectors();                 // array of column vectors
$D       = $A->getDiagonalElements();       // array of the diagonal elements
$d       = $A->getSuperdiagonalElements();  // array of the superdiagonal elements
$d       = $A->getSubdiagonalElements();    // array of the subdiagonal elements

// String representation - Print a matrix
print($A);
/*
 [1, 2, 3]
 [2, 3, 4]
 [3, 4, 5]
 */

// PHP Predefined Interfaces
$json = json_encode($A); // JsonSerializable
$Aᵢⱼ  = $A[$mᵢ][$nⱼ];    // ArrayAccess
```

#### Linear Algebra - Matrix Construction (Factory)
```php
$matrix = [
    [1, 2, 3],
    [4, 5, 6],
    [7, 8, 9],
];

// Matrix factory creates most appropriate matrix
$A = MatrixFactory::create($matrix);

// Matrix factory can create a matrix from an array of column vectors
use MathPHP\LinearAlgebra\Vector;
$X₁ = new Vector([1, 4, 7]);
$X₂ = new Vector([2, 5, 8]);
$X₃ = new Vector([3, 6, 9]);
$A  = MatrixFactory::createFromVectors([$X₁, $X₂, $X₃]);

// Create from row or column vector
$A = MatrixFactory::createFromRowVector([1, 2, 3]);    // 1 × n matrix consisting of a single row of n elements
$A = MatrixFactory::createFromColumnVector([1, 2, 3]); // m × 1 matrix consisting of a single column of m elements

// Specialized matrices
[$m, $n, $k, $angle, $size]   = [4, 4, 2, 3.14159, 2];
$identity_matrix              = MatrixFactory::identity($n);                   // Ones on the main diagonal
$zero_matrix                  = MatrixFactory::zero($m, $n);                   // All zeros
$ones_matrix                  = MatrixFactory::one($m, $n);                    // All ones
$eye_matrix                   = MatrixFactory::eye($m, $n, $k);                // Ones (or other value) on the k-th diagonal
$exchange_matrix              = MatrixFactory::exchange($n);                   // Ones on the reverse diagonal
$downshift_permutation_matrix = MatrixFactory::downshiftPermutation($n);       // Permutation matrix that pushes the components of a vector down one notch with wraparound
$upshift_permutation_matrix   = MatrixFactory::upshiftPermutation($n);         // Permutation matrix that pushes the components of a vector up one notch with wraparound
$diagonal_matrix              = MatrixFactory::diagonal([1, 2, 3]);            // 3 x 3 diagonal matrix with zeros above and below the diagonal
$hilbert_matrix               = MatrixFactory::hilbert($n);                    // Square matrix with entries being the unit fractions
$vandermonde_matrix           = MatrixFactory::vandermonde([1, 2, 3], 4);      // 4 x 3 Vandermonde matrix
$random_matrix                = MatrixFactory::random($m, $n);                 // m x n matrix of random integers
$givens_matrix                = MatrixFactory::givens($m, $n, $angle, $size);  // givens rotation matrix
```

### Linear Algebra - Vector
```php
use MathPHP\LinearAlgebra\Vector;

// Vector
$A = new Vector([1, 2]);
$B = new Vector([2, 4]);

// Basic vector data
$array = $A->getVector();
$n     = $A->getN();           // number of elements
$M     = $A->asColumnMatrix(); // Vector as an nx1 matrix
$M     = $A->asRowMatrix();    // Vector as a 1xn matrix

// Basic vector elements (zero-based indexing)
$item = $A->get(1);

// Vector numeric operations - return a value
$sum               = $A->sum();
$│A│               = $A->length();                            // same as l2Norm
$max               = $A->max();
$min               = $A->min();
$A⋅B               = $A->dotProduct($B);                      // same as innerProduct
$A⋅B               = $A->innerProduct($B);                    // same as dotProduct
$A⊥⋅B              = $A->perpDotProduct($B);
$radAngle          = $A->angleBetween($B);                    // angle in radians
$degAngle          = $A->angleBetween($B, $inDegrees = true); // angle in degrees
$taxicabDistance   = $A->l1Distance($B);                      // same as minkowskiDistance($B, 1)
$euclidDistance    = $A->l2Distance($B);                      // same as minkowskiDistance($B, 2)
$minkowskiDistance = $A->minkowskiDistance($B, $p = 2);

// Vector arithmetic operations - return a Vector
$A＋B  = $A->add($B);
$A−B   = $A->subtract($B);
$A×B   = $A->multiply($B);
$A／B  = $A->divide($B);
$kA    = $A->scalarMultiply($k);
$A／k  = $A->scalarDivide($k);

// Vector operations - return a Vector or Matrix
$A⨂B  = $A->outerProduct($B);  // Same as direct product
$AB    = $A->directProduct($B); // Same as outer product
$AxB   = $A->crossProduct($B);
$A⨂B   = $A->kroneckerProduct($B);
$Â     = $A->normalize();
$A⊥    = $A->perpendicular();
$projᵇA = $A->projection($B);   // projection of A onto B
$perpᵇA = $A->perp($B);         // perpendicular of A on B

// Vector norms - return a value
$l₁norm = $A->l1Norm();
$l²norm = $A->l2Norm();
$pnorm  = $A->pNorm();
$max    = $A->maxNorm();

// String representation
print($A);  // [1, 2]

// PHP standard interfaces
$n    = count($A);                // Countable
$json = json_encode($A);          // JsonSerializable
$Aᵢ   = $A[$i];                   // ArrayAccess
foreach ($A as $element) { ... }  // Iterator
```

### Number - Arbitrary Length Integers
```php
use MathPHP\Number;
use MathPHP\Functions;

// Create arbitrary-length big integers from int or string
$bigInt = new Number\ArbitraryInteger('876937869482938749389832');

// Unary functions
$−bigInt  = $bigInt->negate();
$√bigInt  = $bigInt->isqrt();       // Integer square root
$│bitInt│ = $bigInt->abs();         // Absolute value
$bigInt！  = $bigInt->fact();
$bool     = $bigInt->isPositive();

// Binary functions
$sum              = $bigInt->add($bigInt);
$difference       = $bigInt->subtract($bigInt);
$product          = $bigInt->multiply($bigInt);
$quotient         = $bigInt->intdiv($divisor);
$mod              = $bigInt->mod($divisor);
[$quotient, $mod] = $bigInt->fullIntdiv($divisor);
$pow              = $bigInt->pow($exponent);
$shifted          = $bigInt->leftShift(2);

// Comparison functions
$bool = $bigInt->equals($bigInt);
$bool = $bigInt->greaterThan($bigInt);
$bool = $bigInt->lessThan($bigInt);

// Conversions
$int    = $bigInt->toInt();
$float  = $bigInt->toFloat();
$binary = $bigInt->toBinary();
$string = (string) $bigInt;

// Functions
$ackermann    = Functions\ArbitraryInteger::ackermann($bigInt);
$randomBigInt = Functions\ArbitaryInteger::rand($intNumberOfBytes);
```

### Number - Complex Numbers
```php
use MathPHP\Number\Complex;

[$r, $i] = [2, 4];
$complex = new Complex($r, $i);

// Accessors
$r = $complex->r;
$i = $complex->i;

// Unary functions
$conjugate = $complex->complexConjugate();
$│c│       = $complex->abs();     // absolute value (modulus)
$arg⟮c⟯     = $complex->arg();     // argument (phase)
$√c        = $complex->sqrt();    // positive square root
[$z₁, $z₂] = $complex->roots();
$c⁻¹       = $complex->inverse();
$−c        = $complex->negate();
[$r, $θ]   = $complex->polarForm();

// Binary functions
$c＋c = $complex->add($complex);
$c−c  = $complex->subtract($complex);
$c×c  = $complex->multiply($complex);
$c／c = $complex->divide($complex);

// Other functions
$bool   = $complex->equals($complex);
$string = (string) $complex;
```

### Number - Quaternion
```php
Use MathPHP\Number\Quaternion;

$r = 4;
$i = 1;
$j = 2;
$k = 3;

$quaternion = new Quaternion($r, $i, $j, $k);

// Get individual parts
[$r, $i, $j, $k] = [$quaternion->r, $quaternion->i, $quaternion->j, $quaternion->k];

// Unary functions
$conjugate    = $quaternion->complexConjugate();
$│q│          = $quaternion->abs();  // absolute value (magnitude)
$quaternion⁻¹ = $quaternion->inverse();
$−q           = $quaternion->negate();

// Binary functions
$q＋q = $quaternion->add($quaternion);
$q−q  = $quaternion->subtract($quaternion);
$q×q  = $quaternion->multiply($quaternion);
$q／q = $quaternion->divide($quaternion);

// Other functions
$bool = $quaternion->equals($quaternion);
```

### Number - Rational Numbers
```php
use MathPHP\Number\Rational;

$whole       = 0;
$numerator   = 2;
$denominator = 3;

$rational = new Rational($whole, $numerator, $denominator);  // ²/₃

// Get individual parts
$whole       = $rational->getWholePart();
$numerator   = $rational->getNumerator();
$denominator = $rational->getDenominator();

// Unary functions
$│rational│ = $rational->abs();
$inverse    = $rational->inverse();

// Binary functions
$sum            = $rational->add($rational);
$diff           = $rational->subtract($rational);
$product        = $rational->multiply($rational);
$quotient       = $rational->divide($rational);
$exponentiation = $rational->pow(2);

// Other functions
$bool   = $rational->equals($rational);
$float  = $rational->toFloat();
$string = (string) $rational;
```

### Number Theory - Integers
```php
use MathPHP\NumberTheory\Integer;

$n = 225;

// Prime factorization
$factors = Integer::primeFactorization($n);

// Divisor function
$int  = Integer::numberOfDivisors($n);
$int  = Integer::sumOfDivisors($n);

// Aliquot sums
$int  = Integer::aliquotSum($n);        // sum-of-divisors - n
$bool = Integer::isPerfectNumber($n);   // n = aliquot sum
$bool = Integer::isDeficientNumber($n); // n > aliquot sum
$bool = Integer::isAbundantNumber($n);  // n < aliquot sum

// Totients
$int  = Integer::totient($n);        // Jordan's totient k=1 (Euler's totient)
$int  = Integer::totient($n, 2);     // Jordan's totient k=2
$int  = Integer::cototient($n);      // Cototient
$int  = Integer::reducedTotient($n); // Carmichael's function

// Möbius function
$int  = Integer::mobius($n);

// Radical/squarefree kernel
$int  = Integer::radical($n);

// Squarefree
$bool = Integer::isSquarefree($n);

// Refactorable number
$bool = Integer::isRefactorableNumber($n);

// Sphenic number
$bool = Integer::isSphenicNumber($n);

// Perfect powers
$bool    = Integer::isPerfectPower($n);
[$m, $k] = Integer::perfectPower($n);

// Coprime
$bool = Integer::coprime(4, 35);

// Even and odd
$bool = Integer::isEven($n);
$bool = Integer::isOdd($n);
```

### Numerical Analysis - Interpolation
```php
use MathPHP\NumericalAnalysis\Interpolation;

// Interpolation is a method of constructing new data points with the range
// of a discrete set of known data points.
// Each integration method can take input in two ways:
//  1) As a set of points (inputs and outputs of a function)
//  2) As a callback function, and the number of function evaluations to
//     perform on an interval between a start and end point.

// Input as a set of points
$points = [[0, 1], [1, 4], [2, 9], [3, 16]];

// Input as a callback function
$f⟮x⟯ = function ($x) {
    return $x**2 + 2 * $x + 1;
};
[$start, $end, $n] = [0, 3, 4];

// Lagrange Polynomial
// Returns a function p(x) of x
$p = Interpolation\LagrangePolynomial::interpolate($points);                // input as a set of points
$p = Interpolation\LagrangePolynomial::interpolate($f⟮x⟯, $start, $end, $n); // input as a callback function

$p(0) // 1
$p(3) // 16

// Nevilles Method
// More accurate than Lagrange Polynomial Interpolation given the same input
// Returns the evaluation of the interpolating polynomial at the $target point
$target = 2;
$result = Interpolation\NevillesMethod::interpolate($target, $points);                // input as a set of points
$result = Interpolation\NevillesMethod::interpolate($target, $f⟮x⟯, $start, $end, $n); // input as a callback function

// Newton Polynomial (Forward)
// Returns a function p(x) of x
$p = Interpolation\NewtonPolynomialForward::interpolate($points);                // input as a set of points
$p = Interpolation\NewtonPolynomialForward::interpolate($f⟮x⟯, $start, $end, $n); // input as a callback function

$p(0) // 1
$p(3) // 16

// Natural Cubic Spline
// Returns a piecewise polynomial p(x)
$p = Interpolation\NaturalCubicSpline::interpolate($points);                // input as a set of points
$p = Interpolation\NaturalCubicSpline::interpolate($f⟮x⟯, $start, $end, $n); // input as a callback function

$p(0) // 1
$p(3) // 16

// Clamped Cubic Spline
// Returns a piecewise polynomial p(x)

// Input as a set of points
$points = [[0, 1, 0], [1, 4, -1], [2, 9, 4], [3, 16, 0]];

// Input as a callback function
$f⟮x⟯ = function ($x) {
    return $x**2 + 2 * $x + 1;
};
$f’⟮x⟯ = function ($x) {
    return 2*$x + 2;
};
[$start, $end, $n] = [0, 3, 4];

$p = Interpolation\ClampedCubicSpline::interpolate($points);                       // input as a set of points
$p = Interpolation\ClampedCubicSpline::interpolate($f⟮x⟯, $f’⟮x⟯, $start, $end, $n); // input as a callback function

$p(0); // 1
$p(3); // 16

// Regular Grid Interpolation
// Returns a scalar

// Points defining the regular grid
$xs = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
$ys = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
$zs = [110, 111, 112, 113, 114, 115, 116, 117, 118, 119];

// Data on the regular grid in n dimensions
$data = [];
$func = function ($x, $y, $z) {
    return 2 * $x + 3 * $y - $z;
};
foreach ($xs as $i => $x) {
    foreach ($ys as $j => $y) {
        foreach ($zs as $k => $z) {
            $data[$i][$j][$k] = $func($x, $y, $z);
        }
    }
}

// Constructing a RegularGridInterpolator
$rgi = new Interpolation\RegularGridInterpolator([$xs, $ys, $zs], $data, 'linear');  // 'nearest' method also available

// Interpolating coordinates on the regular grid
$coordinates   = [2.21, 12.1, 115.9];
$interpolation = $rgi($coordinates);  // -75.18
```

### Numerical Analysis - Numerical Differentiation
```php
use MathPHP\NumericalAnalysis\NumericalDifferentiation;

// Numerical Differentiation approximates the derivative of a function.
// Each Differentiation method can take input in two ways:
//  1) As a set of points (inputs and outputs of a function)
//  2) As a callback function, and the number of function evaluations to
//     perform on an interval between a start and end point.

// Input as a callback function
$f⟮x⟯ = function ($x) {
    return $x**2 + 2 * $x + 1;
};

// Three Point Formula
// Returns an approximation for the derivative of our input at our target

// Input as a set of points
$points = [[0, 1], [1, 4], [2, 9]];

$target = 0;
[$start, $end, $n] = [0, 2, 3];
$derivative = NumericalDifferentiation\ThreePointFormula::differentiate($target, $points);                // input as a set of points
$derivative = NumericalDifferentiation\ThreePointFormula::differentiate($target, $f⟮x⟯, $start, $end, $n); // input as a callback function

// Five Point Formula
// Returns an approximation for the derivative of our input at our target

// Input as a set of points
$points = [[0, 1], [1, 4], [2, 9], [3, 16], [4, 25]];

$target = 0;
[$start, $end, $n] = [0, 4, 5];
$derivative = NumericalDifferentiation\FivePointFormula::differentiate($target, $points);                // input as a set of points
$derivative = NumericalDifferentiation\FivePointFormula::differentiate($target, $f⟮x⟯, $start, $end, $n); // input as a callback function

// Second Derivative Midpoint Formula
// Returns an approximation for the second derivative of our input at our target

// Input as a set of points
$points = [[0, 1], [1, 4], [2, 9];

$target = 1;
[$start, $end, $n] = [0, 2, 3];
$derivative = NumericalDifferentiation\SecondDerivativeMidpointFormula::differentiate($target, $points);                // input as a set of points
$derivative = NumericalDifferentiation\SecondDerivativeMidpointFormula::differentiate($target, $f⟮x⟯, $start, $end, $n); // input as a callback function
```

### Numerical Analysis - Numerical Integration
```php
use MathPHP\NumericalAnalysis\NumericalIntegration;

// Numerical integration approximates the definite integral of a function.
// Each integration method can take input in two ways:
//  1) As a set of points (inputs and outputs of a function)
//  2) As a callback function, and the number of function evaluations to
//     perform on an interval between a start and end point.

// Trapezoidal Rule (closed Newton-Cotes formula)
$points = [[0, 1], [1, 4], [2, 9], [3, 16]];
$∫f⟮x⟯dx = NumericalIntegration\TrapezoidalRule::approximate($points); // input as a set of points

$f⟮x⟯ = function ($x) {
    return $x**2 + 2 * $x + 1;
};
[$start, $end, $n] = [0, 3, 4];
$∫f⟮x⟯dx = NumericalIntegration\TrapezoidalRule::approximate($f⟮x⟯, $start, $end, $n); // input as a callback function

// Simpsons Rule (closed Newton-Cotes formula)
$points = [[0, 1], [1, 4], [2, 9], [3, 16], [4,3]];
$∫f⟮x⟯dx = NumericalIntegration\SimpsonsRule::approximate($points); // input as a set of points

$f⟮x⟯ = function ($x) {
    return $x**2 + 2 * $x + 1;
};
[$start, $end, $n] = [0, 3, 5];
$∫f⟮x⟯dx = NumericalIntegration\SimpsonsRule::approximate($f⟮x⟯, $start, $end, $n); // input as a callback function

// Simpsons 3/8 Rule (closed Newton-Cotes formula)
$points = [[0, 1], [1, 4], [2, 9], [3, 16]];
$∫f⟮x⟯dx = NumericalIntegration\SimpsonsThreeEighthsRule::approximate($points); // input as a set of points

$f⟮x⟯ = function ($x) {
    return $x**2 + 2 * $x + 1;
};
[$start, $end, $n] = [0, 3, 5];
$∫f⟮x⟯dx = NumericalIntegration\SimpsonsThreeEighthsRule::approximate($f⟮x⟯, $start, $end, $n); // input as a callback function

// Booles Rule (closed Newton-Cotes formula)
$points = [[0, 1], [1, 4], [2, 9], [3, 16], [4, 25]];
$∫f⟮x⟯dx = NumericalIntegration\BoolesRule::approximate($points); // input as a set of points

$f⟮x⟯ = function ($x) {
    return $x**3 + 2 * $x + 1;
};
[$start, $end, $n] = [0, 4, 5];
$∫f⟮x⟯dx = NumericalIntegration\BoolesRuleRule::approximate($f⟮x⟯, $start, $end, $n); // input as a callback function

// Rectangle Method (open Newton-Cotes formula)
$points = [[0, 1], [1, 4], [2, 9], [3, 16]];
$∫f⟮x⟯dx = NumericalIntegration\RectangleMethod::approximate($points); // input as a set of points

$f⟮x⟯ = function ($x) {
    return $x**2 + 2 * $x + 1;
};
[$start, $end, $n] = [0, 3, 4];
$∫f⟮x⟯dx = NumericalIntegration\RectangleMethod::approximate($f⟮x⟯, $start, $end, $n); // input as a callback function

// Midpoint Rule (open Newton-Cotes formula)
$points = [[0, 1], [1, 4], [2, 9], [3, 16]];
$∫f⟮x⟯dx = NumericalIntegration\MidpointRule::approximate($points); // input as a set of points

$f⟮x⟯ = function ($x) {
    return $x**2 + 2 * $x + 1;
};
[$start, $end, $n] = [0, 3, 4];
$∫f⟮x⟯dx = NumericalIntegration\MidpointRule::approximate($f⟮x⟯, $start, $end, $n); // input as a callback function
```

### Numerical Analysis - Root Finding
```php
use MathPHP\NumericalAnalysis\RootFinding;

// Root-finding methods solve for a root of a polynomial.

// f(x) = x⁴ + 8x³ -13x² -92x + 96
$f⟮x⟯ = function($x) {
    return $x**4 + 8 * $x**3 - 13 * $x**2 - 92 * $x + 96;
};

// Newton's Method
$args     = [-4.1];  // Parameters to pass to callback function (initial guess, other parameters)
$target   = 0;       // Value of f(x) we a trying to solve for
$tol      = 0.00001; // Tolerance; how close to the actual solution we would like
$position = 0;       // Which element in the $args array will be changed; also serves as initial guess. Defaults to 0.
$x        = RootFinding\NewtonsMethod::solve($f⟮x⟯, $args, $target, $tol, $position); // Solve for x where f(x) = $target

// Secant Method
$p₀  = -1;      // First initial approximation
$p₁  = 2;       // Second initial approximation
$tol = 0.00001; // Tolerance; how close to the actual solution we would like
$x   = RootFinding\SecantMethod::solve($f⟮x⟯, $p₀, $p₁, $tol); // Solve for x where f(x) = 0

// Bisection Method
$a   = 2;       // The start of the interval which contains a root
$b   = 5;       // The end of the interval which contains a root
$tol = 0.00001; // Tolerance; how close to the actual solution we would like
$x   = RootFinding\BisectionMethod::solve($f⟮x⟯, $a, $b, $tol); // Solve for x where f(x) = 0

// Fixed-Point Iteration
// f(x) = x⁴ + 8x³ -13x² -92x + 96
// Rewrite f(x) = 0 as (x⁴ + 8x³ -13x² + 96)/92 = x
// Thus, g(x) = (x⁴ + 8x³ -13x² + 96)/92
$g⟮x⟯ = function($x) {
    return ($x**4 + 8 * $x**3 - 13 * $x**2 + 96)/92;
};
$a   = 0;       // The start of the interval which contains a root
$b   = 2;       // The end of the interval which contains a root
$p   = 0;       // The initial guess for our root
$tol = 0.00001; // Tolerance; how close to the actual solution we would like
$x   = RootFinding\FixedPointIteration::solve($g⟮x⟯, $a, $b, $p, $tol); // Solve for x where f(x) = 0
```

### Probability - Combinatorics
```php
use MathPHP\Probability\Combinatorics;

[$n, $x, $k] = [10, 3, 4];

// Factorials
$n！  = Combinatorics::factorial($n);
$n‼︎   = Combinatorics::doubleFactorial($n);
$x⁽ⁿ⁾ = Combinatorics::risingFactorial($x, $n);
$x₍ᵢ₎ = Combinatorics::fallingFactorial($x, $n);
$！n  = Combinatorics::subfactorial($n);

// Permutations
$nPn = Combinatorics::permutations($n);     // Permutations of n things, taken n at a time (same as factorial)
$nPk = Combinatorics::permutations($n, $k); // Permutations of n things, taking only k of them

// Combinations
$nCk  = Combinatorics::combinations($n, $k);                            // n choose k without repetition
$nC′k = Combinatorics::combinations($n, $k, Combinatorics::REPETITION); // n choose k with repetition (REPETITION const = true)

// Central binomial coefficient
$cbc = Combinatorics::centralBinomialCoefficient($n);

// Catalan number
$Cn = Combinatorics::catalanNumber($n);

// Lah number
$L⟮n、k⟯ = Combinatorics::lahNumber($n, $k)

// Multinomial coefficient
$groups    = [5, 2, 3];
$divisions = Combinatorics::multinomial($groups);
```

### Probability - Continuous Distributions
```php
use MathPHP\Probability\Distribution\Continuous;

$p = 0.1;

// Beta distribution
$α      = 1; // shape parameter
$β      = 1; // shape parameter
$x      = 2;
$beta   = new Continuous\Beta($α, $β);
$pdf    = $beta->pdf($x);
$cdf    = $beta->cdf($x);
$icdf   = $beta->inverse($p);
$μ      = $beta->mean();
$median = $beta->median();
$mode   = $beta->mode();
$σ²     = $beta->variance();

// Cauchy distribution
$x₀     = 2; // location parameter
$γ      = 3; // scale parameter
$x      = 1;
$cauchy = new Continuous\Cauchy(x₀, γ);
$pdf    = $cauchy->pdf(x);
$cdf    = $cauchy->cdf(x);
$icdf   = $cauchy->inverse($p);
$μ      = $cauchy->mean();
$median = $cauchy->median();
$mode   = $cauchy->mode();

// χ²-distribution (Chi-Squared)
$k      = 2; // degrees of freedom
$x      = 1;
$χ²     = new Continuous\ChiSquared($k);
$pdf    = $χ²->pdf($x);
$cdf    = $χ²->cdf($x);
$μ      = $χ²->mean($x);
$median = $χ²->median();
$mode   = $χ²->mode();
$σ²     = $χ²->variance();

// Dirac delta distribution
$x     = 1;
$dirac = new Continuous\DiracDelta();
$pdf   = $dirac->pdf($x);
$cdf   = $dirac->cdf($x);
$icdf  = $dirac->inverse($p);
$μ     = $dirac->mean();

// Exponential distribution
$λ           = 1; // rate parameter
$x           = 2;
$exponential = new Continuous\Exponential($λ);
$pdf         = $exponential->pdf($x);
$cdf         = $exponential->cdf($x);
$icdf        = $exponential->inverse($p);
$μ           = $exponential->mean();
$median      = $exponential->median();
$σ²          = $exponential->variance();

// F-distribution
$d₁   = 3; // degree of freedom v1
$d₂   = 4; // degree of freedom v2
$x    = 2;
$f    = new Continuous\F($d₁, $d₂);
$pdf  = $f->pdf($x);
$cdf  = $f->cdf($x);
$μ    = $f->mean();
$mode = $f->mode();
$σ²   = $f->variance();

// Gamma distribution
$k      = 2; // shape parameter
$θ      = 3; // scale parameter
$x      = 4;
$gamma  = new Continuous\Gamma($k, $θ);
$pdf    = $gamma->pdf($x);
$cdf    = $gamma->cdf($x);
$μ      = $gamma->mean();
$median = $gamma->median();
$mode   = $gamma->mode();
$σ²     = $gamma->variance();

// Laplace distribution
$μ       = 1;   // location parameter
$b       = 1.5; // scale parameter (diversity)
$x       = 1;
$laplace = new Continuous\Laplace($μ, $b);
$pdf     = $laplace->pdf($x);
$cdf     = $laplace->cdf($x);
$icdf    = $laplace->inverse($p);
$μ       = $laplace->mean();
$median  = $laplace->median();
$mode    = $laplace->mode();
$σ²      = $laplace->variance();

// Logistic distribution
$μ        = 2;   // location parameter
$s        = 1.5; // scale parameter
$x        = 3;
$logistic = new Continuous\Logistic($μ, $s);
$pdf      = $logistic->pdf($x);
$cdf      = $logistic->cdf($x);
$icdf     = $logistic->inverse($p);
$μ        = $logistic->mean();
$median   = $logistic->median();
$mode     = $logistic->mode();
$σ²       = $logisitic->variance();

// Log-logistic distribution (Fisk distribution)
$α           = 1; // scale parameter
$β           = 1; // shape parameter
$x           = 2;
$logLogistic = new Continuous\LogLogistic($α, $β);
$pdf         = $logLogistic->pdf($x);
$cdf         = $logLogistic->cdf($x);
$icdf        = $logLogistic->inverse($p);
$μ           = $logLogistic->mean();
$median      = $logLogistic->median();
$mode        = $logLogistic->mode();
$σ²          = $logLogistic->variance();

// Log-normal distribution
$μ         = 6;   // scale parameter
$σ         = 2;   // location parameter
$x         = 4.3;
$logNormal = new Continuous\LogNormal($μ, $σ);
$pdf       = $logNormal->pdf($x);
$cdf       = $logNormal->cdf($x);
$icdf      = $logNormal->inverse($p);
$μ         = $logNormal->mean();
$median    = $logNormal->median();
$mode      = $logNormal->mode();
$σ²        = $logNormal->variance();

// Noncentral T distribution
$ν            = 50; // degrees of freedom
$μ            = 10; // noncentrality parameter
$x            = 8;
$noncenetralT = new Continuous\NoncentralT($ν, $μ);
$pdf          = $noncenetralT->pdf($x);
$cdf          = $noncenetralT->cdf($x);
$μ            = $noncenetralT->mean();

// Normal distribution
$σ      = 1;
$μ      = 0;
$x      = 2;
$normal = new Continuous\Normal($μ, $σ);
$pdf    = $normal->pdf($x);
$cdf    = $normal->cdf($x);
$icdf   = $normal->inverse($p);
$μ      = $normal->mean();
$median = $normal->median();
$mode   = $normal->mode();
$σ²     = $normal->variance();

// Pareto distribution
$a      = 1; // shape parameter
$b      = 1; // scale parameter
$x      = 2;
$pareto = new Continuous\Pareto($a, $b);
$pdf    = $pareto->pdf($x);
$cdf    = $pareto->cdf($x);
$icdf   = $pareto->inverse($p);
$μ      = $pareto->mean();
$median = $pareto->median();
$mode   = $pareto->mode();
$σ²     = $pareto->variance();

// Standard normal distribution
$z              = 2;
$standardNormal = new Continuous\StandardNormal();
$pdf            = $standardNormal->pdf($z);
$cdf            = $standardNormal->cdf($z);
$icdf           = $standardNormal->inverse($p);
$μ              = $standardNormal->mean();
$median         = $standardNormal->median();
$mode           = $standardNormal->mode();
$σ²             = $standardNormal->variance();

// Student's t-distribution
$ν        = 3;   // degrees of freedom
$p        = 0.4; // proportion of area
$x        = 2;
$studentT = new Continuous\StudentT::pdf($ν);
$pdf      = $studentT->pdf($x);
$cdf      = $studentT->cdf($x);
$t        = $studentT->inverse2Tails($p);  // t such that the area greater than t and the area beneath -t is p
$μ        = $studentT->mean();
$median   = $studentT->median();
$mode     = $studentT->mode();
$σ²       = $studentT->variance();

// Uniform distribution
$a       = 1; // lower boundary of the distribution
$b       = 4; // upper boundary of the distribution
$x       = 2;
$uniform = new Continuous\Uniform($a, $b);
$pdf     = $uniform->pdf($x);
$cdf     = $uniform->cdf($x);
$μ       = $uniform->mean();
$median  = $uniform->median();
$mode    = $uniform->mode();
$σ²      = $uniform->variance();

// Weibull distribution
$k       = 1; // shape parameter
$λ       = 2; // scale parameter
$x       = 2;
$weibull = new Continuous\Weibull($k, $λ);
$pdf     = $weibull->pdf($x);
$cdf     = $weibull->cdf($x);
$icdf    = $weibull->inverse($p);
$μ       = $weibull->mean();
$median  = $weibull->median();
$mode    = $weibull->mode();

// Other CDFs - All continuous distributions - Replace {$distribution} with desired distribution.
$between = $distribution->between($x₁, $x₂);  // Probability of being between two points, x₁ and x₂
$outside = $distribution->outside($x₁, $x);   // Probability of being between below x₁ and above x₂
$above   = $distribution->above($x);          // Probability of being above x to ∞

// Random Number Generator
$random  = $distribution->rand();  // A random number with a given distribution
```

### Probability - Discrete Distributions
```php
use MathPHP\Probability\Distribution\Discrete;

// Bernoulli distribution (special case of binomial where n = 1)
$p         = 0.3;
$k         = 0;
$bernoulli = new Discrete\Bernoulli($p);
$pmf       = $bernoulli->pmf($k);
$cdf       = $bernoulli->cdf($k);
$μ         = $bernoulli->mean();
$median    = $bernoulli->median();
$mode      = $bernoulli->mode();
$σ²        = $bernoulli->variance();

// Binomial distribution
$n        = 2;   // number of events
$p        = 0.5; // probability of success
$r        = 1;   // number of successful events
$binomial = new Discrete\Binomial($n, $p);
$pmf      = $binomial->pmf($r);
$cdf      = $binomial->cdf($r);
$μ        = $binomial->mean();
$σ²       = $binomial->variance();

// Categorical distribution
$k             = 3;                                    // number of categories
$probabilities = ['a' => 0.3, 'b' => 0.2, 'c' => 0.5]; // probabilities for categorices a, b, and c
$categorical   = new Discrete\Categorical($k, $probabilities);
$pmf_a         = $categorical->pmf('a');
$mode          = $categorical->mode();

// Geometric distribution (failures before the first success)
$p         = 0.5; // success probability
$k         = 2;   // number of trials
$geometric = new Discrete\Geometric($p);
$pmf       = $geometric->pmf($k);
$cdf       = $geometric->cdf($k);
$μ         = $geometric->mean();
$median    = $geometric->median();
$mode      = $geometric->mode();
$σ²        = $geometric->variance();

// Hypergeometric distribution
$N        = 50; // population size
$K        = 5;  // number of success states in the population
$n        = 10; // number of draws
$k        = 4;  // number of observed successes
$hypergeo = new Discrete\Hypergeometric($N, $K, $n);
$pmf      = $hypergeo->pmf($k);
$cdf      = $hypergeo->cdf($k);
$μ        = $hypergeo->mean();
$mode     = $hypergeo->mode();
$σ²       = $hypergeo->variance();

// Negative binomial distribution (Pascal)
$r                = 1;   // number of failures until the experiment is stopped
$P                = 0.5; // probability of success on an individual trial
$x                = 2;   // number of successes
$negativeBinomial = new Discrete\NegativeBinomial($r, $p);
$pmf              = $negativeBinomial->pmf($x);
$cdf              = $negativeBinomial->cdf($x);
$μ                = $negativeBinomial->mean();
$mode             = $negativeBinomial->mode();
$σ²               = $negativeBinomial->variance();

// Pascal distribution (Negative binomial)
$r      = 1;   // number of failures until the experiment is stopped
$P      = 0.5; // probability of success on an individual trial
$x      = 2;   // number of successes
$pascal = new Discrete\Pascal($r, $p);
$pmf    = $pascal->pmf($x);
$cdf    = $pascal->cdf($x);
$μ      = $pascal->mean();
$mode   = $pascal->mode();
$σ²     = $pascal->variance();

// Poisson distribution
$λ       = 2; // average number of successful events per interval
$k       = 3; // events in the interval
$poisson = new Discrete\Poisson($λ);
$pmf     = $poisson->pmf($k);
$cdf     = $poisson->cdf($k);
$μ       = $poisson->mean();
$median  = $poisson->median();
$mode    = $poisson->mode();
$σ²      = $poisson->variance();

// Shifted geometric distribution (probability to get one success)
$p                = 0.5; // success probability
$k                = 2;   // number of trials
$shiftedGeometric = new Discrete\ShiftedGeometric($p);
$pmf              = $shiftedGeometric->pmf($k);
$cdf              = $shiftedGeometric->cdf($k);
$μ                = $shiftedGeometric->mean();
$median           = $shiftedGeometric->median();
$mode             = $shiftedGeometric->mode();
$σ²               = $shiftedGeometric->variance();

// Uniform distribution
$a       = 1; // lower boundary of the distribution
$b       = 4; // upper boundary of the distribution
$k       = 2; // percentile
$uniform = new Discrete\Uniform($a, $b);
$pmf     = $uniform->pmf();
$cdf     = $uniform->cdf($k);
$μ       = $uniform->mean();
$median  = $uniform->median();
$σ²      = $uniform->variance();

// Zipf distribution
$k    = 2;   // rank
$s    = 3;   // exponent
$N    = 10;  // number of elements
$zipf = new Discrete\Zipf($s, $N);
$pmf  = $zipf->pmf($k);
$cdf  = $zipf->cdf($k);
$μ    = $zipf->mean();
$mode = $zipf->mode();
```

### Probability - Multivariate Distributions
```php
use MathPHP\Probability\Distribution\Multivariate;

// Dirichlet distribution
$αs        = [1, 2, 3];
$xs        = [0.07255081, 0.27811903, 0.64933016];
$dirichlet = new Multivariate\Dirichlet($αs);
$pdf       = $dirichlet->pdf($xs);

// Normal distribution
$μ      = [1, 1.1];
$∑      = MatrixFactory::create([
    [1, 0],
    [0, 1],
]);
$X      = [0.7, 1.4];
$normal = new Multivariate\Normal($μ, $∑);
$pdf    = $normal->pdf($X);

// Hypergeometric distribution
$quantities   = [5, 10, 15];   // Suppose there are 5 black, 10 white, and 15 red marbles in an urn.
$choices      = [2, 2, 2];     // If six marbles are chosen without replacement, the probability that exactly two of each color are chosen is:
$distribution = new Multivariate\Hypergeometric($quantities);
$probability  = $distribution->pmf($choices);    // 0.0795756

// Multinomial distribution
$frequencies   = [7, 2, 3];
$probabilities = [0.40, 0.35, 0.25];
$multinomial   = new Multivariate\Multinomial($probabilities);
$pmf           = $multinomial->pmf($frequencies);
```

### Probability - Distribution Tables
```php
use MathPHP\Probability\Distribution\Table;

// Provided solely for completeness' sake.
// It is statistics tradition to provide these tables.
// MathPHP has dynamic distribution CDF functions you can use instead.

// Standard Normal Table (Z Table)
$table       = Table\StandardNormal::Z_SCORES;
$probability = $table[1.5][0];                 // Value for Z of 1.50

// t Distribution Tables
$table   = Table\TDistribution::ONE_SIDED_CONFIDENCE_LEVEL;
$table   = Table\TDistribution::TWO_SIDED_CONFIDENCE_LEVEL;
$ν       = 5;  // degrees of freedom
$cl      = 99; // confidence level
$t       = $table[$ν][$cl];

// t Distribution Tables
$table = Table\TDistribution::ONE_SIDED_ALPHA;
$table = Table\TDistribution::TWO_SIDED_ALPHA;
$ν     = 5;     // degrees of freedom
$α     = 0.001; // alpha value
$t     = $table[$ν][$α];

// χ² Distribution Table
$table = Table\ChiSquared::CHI_SQUARED_SCORES;
$df    = 2;    // degrees of freedom
$p     = 0.05; // P value
$χ²    = $table[$df][$p];
```

### Sample Data
```php
use MathPHP\SampleData;

// Famous sample data sets to experiment with

// Motor Trend Car Road Tests (mtcars)
$mtCars      = new SampleData\MtCars();
$rawData     = $mtCars->getData();                     // [[21, 6, 160, ... ], [30.4, 4, 71.1, ... ], ... ]
$labeledData = $mtCars->getLabeledData();              // ['Mazda RX4' => ['mpg' => 21, 'cyl' => 6, 'disp' => 160, ... ], 'Honda Civic' => [ ... ], ...]
$modelData   = $mtCars->getModelData('Ferrari Dino');  // ['mpg' => 19.7, 'cyl' => 6, 'disp' => 145, ... ]
$mpgs        = $mtCars->getMpg();                      // ['Mazda RX4' => 21, 'Honda civic' => 30.4, ... ]
// Getters for Mpg, Cyl, Disp, Hp, Drat, Wt, Qsec, Vs, Am, Gear, Carb

// Edgar Anderson's Iris Data (iris)
$iris         = new SampleData\Iris();
$rawData      = $iris->getData();         // [[5.1, 3.5, 1.4, 0.2, 'setosa'], [4.9, 3.0, 1.4, 0.2, 'setosa'], ... ]
$labeledData  = $iris->getLabeledData();  // [['sepalLength' => 5.11, 'sepalWidth' => 3.5, 'petalLength' => 1.4, 'petalWidth' => 0.2, 'species' => 'setosa'], ... ]
$petalLengths = $iris->getSepalLength();  // [5.1, 4.9, 4.7, ... ]
// Getters for SepalLength, SepalWidth, PetalLength, PetalWidth, Species

// The Effect of Vitamin C on Tooth Growth in Guinea Pigs (ToothGrowth)
$toothGrowth = new SampleData\ToothGrowth();
$rawData     = $toothGrowth->getData();         // [[4.2, 'VC', 0.5], [11.5, 'VC', '0.5], ... ]
$labeledData = $toothGrowth->getLabeledData();  // [['len' => 4.2, 'supp' => 'VC', 'dose' => 0.5], ... ]
$lengths     = $toothGrowth->getLen();          // [4.2, 11.5, ... ]
// Getters for Len, Supp, Dose

// Results from an Experiment on Plant Growth (PlantGrowth)
$plantGrowth = new SampleData\PlantGrowth();
$rawData     = $plantGrowth->getData();         // [[4.17, 'ctrl'], [5.58, 'ctrl'], ... ]
$labeledData = $plantGrowth->getLabeledData();  // [['weight' => 4.17, 'group' => 'ctrl'], ['weight' => 5.58, 'group' => 'ctrl'], ... ]
$weights     = $plantGrowth->getWeight();       // [4.17, 5.58, ... ]
// Getters for Weight, Group

// Violent Crime Rates by US State (USArrests)
$usArrests   = new SampleData\UsArrests();
$rawData     = $usArrests->rawData();              // [[13.2, 236, 58, 21.2], [10.0, 263, 48, 44.5], ... ]
$labeledData = $usArrests->getLabeledData();       // ['Alabama' => ['murder' => 13.2, 'assault' => 236, 'urbanPop' => 58, 'rape' => 21.2], ... ]
$stateData   = $usArrests->getStateData('Texas');  // ['murder' => 12.7, 'assault' => 201, 'urbanPop' => 80, 'rape' => 25.5]
$murders     = $usArrests->getMurders();           // ['Alabama' => 13.2, 'Alaska' => 10.1, ... ]
// Getters for Murder, Assault, UrbanPop, Rape

// Data from Cereals (cereal)
$cereal  = new SampleData\Cereal();
$cereals = $cereal->getCereals();    // ['B1', 'B2', 'B3', 'M1', 'M2', ... ]
$X       = $cereal->getXData();      // [[0.002682755, 0.003370673, 0.004085942, ... ], [0.002781597, 0.003474863, 0.004191472, ... ], ... ]
$Y       = $cereal->getYData();      // [[18373, 41.61500, 6.565000, ... ], [18536, 41.40500, 6.545000, ... ], ... ]
$Ysc     = $cereal->getYscData();    // [[-0.1005049, 0.6265746, -1.1716630, ... ], [0.9233889, 0.1882929, -1.3185289, ... ], ... ]
// Labeled data: getLabeledXData(), getLabeledYData(), getLabeledYscData()

// Data from People (people)
$people      = new SampleData\People();
$rawData     = $people->getData();         // [198, 92, -1, ... ], [184, 84, -1, ... ], ... ]
$labeledData = $people->getLabeledData();  // ['Lars' => ['height' => 198, 'weight' => 92, 'hairLength' => -1, ... ]]
$names       = $people->getNames();
// Getters for names, height, weight, hairLength, shoeSize, age, income, beer, wine, sex, swim, region, iq
```

### Search
```php
use MathPHP\Search;

// Search lists of numbers to find specific indexes

$list = [1, 2, 3, 4, 5];

$index   = Search::sorted($list, 2);   // Find the array index where an item should be inserted to maintain sorted order
$index   = Search::argMax($list);      // Find the array index of the maximum value
$index   = Search::nanArgMax($list);   // Find the array index of the maximum value, ignoring NANs
$index   = Search::argMin($list);      // Find the array index of the minimum value
$index   = Search::nanArgMin($list);   // Find the array index of the minimum value, ignoring NANs
$indices = Search::nonZero($list);     // Find the array indices of the scalar values that are non-zero
```

### Sequences - Basic
```php
use MathPHP\Sequence\Basic;

$n = 5; // Number of elements in the sequence

// Arithmetic progression
$d           = 2;  // Difference between the elements of the sequence
$a₁          = 1;  // Starting number for the sequence
$progression = Basic::arithmeticProgression($n, $d, $a₁);
// [1, 3, 5, 7, 9] - Indexed from 1

// Geometric progression (arⁿ⁻¹)
$a           = 2; // Scalar value
$r           = 3; // Common ratio
$progression = Basic::geometricProgression($n, $a, $r);
// [2(3)⁰, 2(3)¹, 2(3)², 2(3)³] = [2, 6, 18, 54] - Indexed from 1

// Square numbers (n²)
$squares = Basic::squareNumber($n);
// [0², 1², 2², 3², 4²] = [0, 1, 4, 9, 16] - Indexed from 0

// Cubic numbers (n³)
$cubes = Basic::cubicNumber($n);
// [0³, 1³, 2³, 3³, 4³] = [0, 1, 8, 27, 64] - Indexed from 0

// Powers of 2 (2ⁿ)
$po2 = Basic::powersOfTwo($n);
// [2⁰, 2¹, 2², 2³, 2⁴] = [1,  2,  4,  8,  16] - Indexed from 0

// Powers of 10 (10ⁿ)
$po10 = Basic::powersOfTen($n);
// [10⁰, 10¹, 10², 10³,  10⁴] = [1, 10, 100, 1000, 10000] - Indexed from 0

// Factorial (n!)
$fact = Basic::factorial($n);
// [0!, 1!, 2!, 3!, 4!] = [1,  1,  2,  6,  24] - Indexed from 0

// Digit sum
$digit_sum = Basic::digitSum($n);
// [0, 1, 2, 3, 4] - Indexed from 0

// Digital root
$digit_root = Basic::digitalRoot($n);
// [0, 1, 2, 3, 4] - Indexed from 0
```

### Sequences - Advanced
```php
use MathPHP\Sequence\Advanced;

$n = 6; // Number of elements in the sequence

// Fibonacci (Fᵢ = Fᵢ₋₁ + Fᵢ₋₂)
$fib = Advanced::fibonacci($n);
// [0, 1, 1, 2, 3, 5] - Indexed from 0

// Lucas numbers
$lucas = Advanced::lucasNumber($n);
// [2, 1, 3, 4, 7, 11] - Indexed from 0

// Pell numbers
$pell = Advanced::pellNumber($n);
// [0, 1, 2, 5, 12, 29] - Indexed from 0

// Triangular numbers (figurate number)
$triangles = Advanced::triangularNumber($n);
// [1, 3, 6, 10, 15, 21] - Indexed from 1

// Pentagonal numbers (figurate number)
$pentagons = Advanced::pentagonalNumber($n);
// [1, 5, 12, 22, 35, 51] - Indexed from 1

// Hexagonal numbers (figurate number)
$hexagons = Advanced::hexagonalNumber($n);
// [1, 6, 15, 28, 45, 66] - Indexed from 1

// Heptagonal numbers (figurate number)
$heptagons = Advanced::heptagonalNumber($n);
// [1, 4, 7, 13, 18, 27] - Indexed from 1

// Look-and-say sequence (describe the previous term!)
$look_and_say = Advanced::lookAndSay($n);
// ['1', '11', '21', '1211', '111221', '312211'] - Indexed from 1

// Lazy caterer's sequence (central polygonal numbers)
$lazy_caterer = Advanced::lazyCaterers($n);
// [1, 2, 4, 7, 11, 16] - Indexed from 0

// Magic squares series (magic constants; magic sums)
$magic_squares = Advanced::magicSquares($n);
// [0, 1, 5, 15, 34, 65] - Indexed from 0

// Perfect numbers
$perfect_numbers = Advanced::perfectNumbers($n);
// [6, 28, 496, 8128, 33550336, 8589869056] - Indexed from 0

// Perfect powers sequence
$perfect_powers = Advanced::perfectPowers($n);
// [4, 8, 9, 16, 25, 27] - Indexed from 0

// Not perfect powers sequence
$not_perfect_powers = Advanced::notPerfectPowers($n);
// [2, 3, 5, 6, 7, 10] - Indexed from 0

// Prime numbers up to n (n is not the number of elements in the sequence)
$primes = Advanced::primesUpTo(30);
// [2, 3, 5, 7, 11, 13, 17, 19, 23, 29] - Indexed from 0
```

### Sequences - Non-Integer
```php
use MathPHP\Sequence\NonInteger;

$n = 4; // Number of elements in the sequence

// Harmonic sequence
$harmonic = NonInteger::harmonic($n);
// [1, 3/2, 11/6, 25/12] - Indexed from 1

// Generalized harmonic sequence
$m           = 2;  // exponent
$generalized = NonInteger::generalizedHarmonic($n, $m);
// [1, 5 / 4, 49 / 36, 205 / 144] - Indexed from 1

// Hyperharmonic sequence
$r             = 2;  // depth of recursion
$hyperharmonic = NonInteger::hyperharmonic($n, $r);
// [1, 5/2, 26/6, 77/12] - Indexed from 1
```

### Set Theory
```php
use MathPHP\SetTheory\Set;
use MathPHP\SetTheory\ImmutableSet;

// Sets and immutable sets
$A = new Set([1, 2, 3]);          // Can add and remove members
$B = new ImmutableSet([3, 4, 5]); // Cannot modify set once created

// Basic set data
$set         = $A->asArray();
$cardinality = $A->length();
$bool        = $A->isEmpty();

// Set membership
$true = $A->isMember(2);
$true = $A->isNotMember(8);

// Add and remove members
$A->add(4);
$A->add(new Set(['a', 'b']));
$A->addMulti([5, 6, 7]);
$A->remove(7);
$A->removeMulti([5, 6]);
$A->clear();

// Set properties against other sets - return boolean
$bool = $A->isDisjoint($B);
$bool = $A->isSubset($B);         // A ⊆ B
$bool = $A->isProperSubset($B);   // A ⊆ B & A ≠ B
$bool = $A->isSuperset($B);       // A ⊇ B
$bool = $A->isProperSuperset($B); // A ⊇ B & A ≠ B

// Set operations with other sets - return a new Set
$A∪B  = $A->union($B);
$A∩B  = $A->intersect($B);
$A＼B = $A->difference($B);          // relative complement
$AΔB  = $A->symmetricDifference($B);
$A×B  = $A->cartesianProduct($B);

// Other set operations
$P⟮A⟯ = $A->powerSet();
$C   = $A->copy();

// Print a set
print($A); // Set{1, 2, 3, 4, Set{a, b}}

// PHP Interfaces
$n = count($A);                 // Countable
foreach ($A as $member) { ... } // Iterator

// Fluent interface
$A->add(5)->add(6)->remove(4)->addMulti([7, 8, 9]);
```

### Statistics - ANOVA
```php
use MathPHP\Statistics\ANOVA;

// One-way ANOVA
$sample1 = [1, 2, 3];
$sample2 = [3, 4, 5];
$sample3 = [5, 6, 7];
   ⋮            ⋮

$anova = ANOVA::oneWay($sample1, $sample2, $sample3);
print_r($anova);
/* Array (
    [ANOVA] => Array (             // ANOVA hypothesis test summary data
            [treatment] => Array (
                    [SS] => 24     // Sum of squares (between)
                    [df] => 2      // Degrees of freedom
                    [MS] => 12     // Mean squares
                    [F]  => 12     // Test statistic
                    [P]  => 0.008  // P value
                )
            [error] => Array (
                    [SS] => 6      // Sum of squares (within)
                    [df] => 6      // Degrees of freedom
                    [MS] => 1      // Mean squares
                )
            [total] => Array (
                    [SS] => 30     // Sum of squares (total)
                    [df] => 8      // Degrees of freedom
                )
        )
    [total_summary] => Array (     // Total summary data
            [n]        => 9
            [sum]      => 36
            [mean]     => 4
            [SS]       => 174
            [variance] => 3.75
            [sd]       => 1.9364916731037
            [sem]      => 0.6454972243679
        )
    [data_summary] => Array (      // Data summary (each input sample)
            [0] => Array ([n] => 3 [sum] => 6  [mean] => 2 [SS] => 14  [variance] => 1 [sd] => 1 [sem] => 0.57735026918963)
            [1] => Array ([n] => 3 [sum] => 12 [mean] => 4 [SS] => 50  [variance] => 1 [sd] => 1 [sem] => 0.57735026918963)
            [2] => Array ([n] => 3 [sum] => 18 [mean] => 6 [SS] => 110 [variance] => 1 [sd] => 1 [sem] => 0.57735026918963)
        )
) */

// Two-way ANOVA
/*        | Factor B₁ | Factor B₂ | Factor B₃ | ⋯
Factor A₁ |  4, 6, 8  |  6, 6, 9  |  8, 9, 13 | ⋯
Factor A₂ |  4, 8, 9  | 7, 10, 13 | 12, 14, 16| ⋯
    ⋮           ⋮           ⋮           ⋮         */
$factorA₁ = [
  [4, 6, 8],    // Factor B₁
  [6, 6, 9],    // Factor B₂
  [8, 9, 13],   // Factor B₃
];
$factorA₂ = [
  [4, 8, 9],    // Factor B₁
  [7, 10, 13],  // Factor B₂
  [12, 14, 16], // Factor B₃
];
       ⋮

$anova = ANOVA::twoWay($factorA₁, $factorA₂);
print_r($anova);
/* Array (
    [ANOVA] => Array (          // ANOVA hypothesis test summary data
            [factorA] => Array (
                    [SS] => 32                 // Sum of squares
                    [df] => 1                  // Degrees of freedom
                    [MS] => 32                 // Mean squares
                    [F]  => 5.6470588235294    // Test statistic
                    [P]  => 0.034994350619895  // P value
                )
            [factorB] => Array (
                    [SS] => 93                 // Sum of squares
                    [df] => 2                  // Degrees of freedom
                    [MS] => 46.5               // Mean squares
                    [F]  => 8.2058823529412    // Test statistic
                    [P]  => 0.0056767297582031 // P value
                )
            [interaction] => Array (
                    [SS] => 7                  // Sum of squares
                    [df] => 2                  // Degrees of freedom
                    [MS] => 3.5                // Mean squares
                    [F]  => 0.61764705882353   // Test statistic
                    [P]  => 0.5555023440712    // P value
                )
            [error] => Array (
                    [SS] => 68                 // Sum of squares (within)
                    [df] => 12                 // Degrees of freedom
                    [MS] => 5.6666666666667    // Mean squares
                )
            [total] => Array (
                    [SS] => 200                // Sum of squares (total)
                    [df] => 17                 // Degrees of freedom
                )
        )
    [total_summary] => Array (    // Total summary data
            [n]        => 18
            [sum]      => 162
            [mean]     => 9
            [SS]       => 1658
            [variance] => 11.764705882353
            [sd]       => 3.4299717028502
            [sem]      => 0.80845208345444
        )
    [summary_factorA]     => Array ( ... )   // Summary data of factor A
    [summary_factorB]     => Array ( ... )   // Summary data of factor B
    [summary_interaction] => Array ( ... )   // Summary data of interactions of factors A and B
) */
```

### Statistics - Averages
```php
use MathPHP\Statistics\Average;

$numbers = [13, 18, 13, 14, 13, 16, 14, 21, 13];

// Mean, median, mode
$mean   = Average::mean($numbers);
$median = Average::median($numbers);
$mode   = Average::mode($numbers); // Returns an array — may be multimodal

// Weighted mean
$weights       = [12, 1, 23, 6, 12, 26, 21, 12, 1];
$weighted_mean = Average::weightedMean($numbers, $weights)

// Other means of a list of numbers
$geometric_mean      = Average::geometricMean($numbers);
$harmonic_mean       = Average::harmonicMean($numbers);
$contraharmonic_mean = Average::contraharmonicMean($numbers);
$quadratic_mean      = Average::quadraticMean($numbers);  // same as rootMeanSquare
$root_mean_square    = Average::rootMeanSquare($numbers); // same as quadraticMean
$trimean             = Average::trimean($numbers);
$interquartile_mean  = Average::interquartileMean($numbers); // same as iqm
$interquartile_mean  = Average::iqm($numbers);               // same as interquartileMean
$cubic_mean          = Average::cubicMean($numbers);

// Truncated mean (trimmed mean)
$trim_percent   = 25;  // 25 percent of observations trimmed from each end of distribution
$truncated_mean = Average::truncatedMean($numbers, $trim_percent);

// Generalized mean (power mean)
$p                = 2;
$generalized_mean = Average::generalizedMean($numbers, $p); // same as powerMean
$power_mean       = Average::powerMean($numbers, $p);       // same as generalizedMean

// Lehmer mean
$p           = 3;
$lehmer_mean = Average::lehmerMean($numbers, $p);

// Moving averages
$n       = 3;
$weights = [3, 2, 1];
$SMA     = Average::simpleMovingAverage($numbers, $n);             // 3 n-point moving average
$CMA     = Average::cumulativeMovingAverage($numbers);
$WMA     = Average::weightedMovingAverage($numbers, $n, $weights);
$EPA     = Average::exponentialMovingAverage($numbers, $n);

// Means of two numbers
[$x, $y]       = [24, 6];
$agm           = Average::arithmeticGeometricMean($x, $y); // same as agm
$agm           = Average::agm($x, $y);                     // same as arithmeticGeometricMean
$log_mean      = Average::logarithmicMean($x, $y);
$heronian_mean = Average::heronianMean($x, $y);
$identric_mean = Average::identricMean($x, $y);

// Averages report
$averages = Average::describe($numbers);
print_r($averages);
/* Array (
    [mean]                => 15
    [median]              => 14
    [mode]                => Array ( [0] => 13 )
    [geometric_mean]      => 14.789726414533
    [harmonic_mean]       => 14.605077399381
    [contraharmonic_mean] => 15.474074074074
    [quadratic_mean]      => 15.235193176035
    [trimean]             => 14.5
    [iqm]                 => 14
    [cubic_mean]          => 15.492307432707
) */
```

### Statistics - Circular
```php
use MathPHP\Statistics\Circular;

$angles = [1.51269877, 1.07723915, 0.81992282];

$θ = Circular::mean($angles);
$R = Circular::resultantLength($angles);
$ρ = Circular::meanResultantLength($angles);
$V = Circular::variance($angles);
$ν = Circular::standardDeviation($angles);

// Descriptive circular statistics report
$stats = Circular::describe($angles);
print_r($stats);
/* Array (
    [n]                     => 3
    [mean]                  => 1.1354043006436
    [resultant_length]      => 2.8786207547493
    [mean_resultant_length] => 0.9595402515831
    [variance]              => 0.040459748416901
    [sd]                    => 0.28740568481722
); */
```

### Statistics - Correlation
```php
use MathPHP\Statistics\Correlation;

$X = [1, 2, 3, 4, 5];
$Y = [2, 3, 4, 4, 6];

// Covariance
$σxy = Correlation::covariance($X, $Y);  // Has optional parameter to set population (defaults to sample covariance)

// Weighted covariance
$w    = [2, 3, 1, 1, 5];
$σxyw = Correlation::weightedCovariance($X, $Y, $w);

// r - Pearson product-moment correlation coefficient (Pearson's r)
$r = Correlation::r($X, $Y);  // Has optional parameter to set population (defaults to sample correlation coefficient)

// Weighted correlation coefficient
$rw = Correlation::weightedCorrelationCoefficient($X, $Y, $w);

// R² - Coefficient of determination
$R² = Correlation::r2($X, $Y);  // Has optional parameter to set population (defaults to sample coefficient of determination)

// τ - Kendall rank correlation coefficient (Kendall's tau)
$τ = Correlation::kendallsTau($X, $Y);

// ρ - Spearman's rank correlation coefficient (Spearman's rho)
$ρ = Correlation::spearmansRho($X, $Y);

// Descriptive correlation report
$stats = Correlation::describe($X, $Y);
print_r($stats);
/* Array (
    [cov] => 2.25
    [r]   => 0.95940322360025
    [r2]  => 0.92045454545455
    [tau] => 0.94868329805051
    [rho] => 0.975
) */

// Confidence ellipse - create an ellipse surrounding the data at a specified standard deviation
$sd           = 1;
$num_points   = 11; // Optional argument specifying number of points of the ellipse
$ellipse_data = Correlation::confidenceEllipse($X, $Y, $sd, $num_points);

```

### Statistics - Descriptive
```php
use MathPHP\Statistics\Descriptive;

$numbers = [13, 18, 13, 14, 13, 16, 14, 21, 13];

// Range and midrange
$range    = Descriptive::range($numbers);
$midrange = Descriptive::midrange($numbers);

// Variance (population and sample)
$σ² = Descriptive::populationVariance($numbers); // n degrees of freedom
$S² = Descriptive::sampleVariance($numbers);     // n - 1 degrees of freedom

// Variance (Custom degrees of freedom)
$df = 5;                                    // degrees of freedom
$S² = Descriptive::variance($numbers, $df); // can specify custom degrees of freedom

// Weighted sample variance
$weights = [0.1, 0.2, 0.1, 0.1, 0.1, 0.1, 0.1, 0.1];
$σ²w     = Descriptive::weightedSampleVariance($numbers, $weights, $biased = false);

// Standard deviation (For a sample; uses sample variance)
$σ = Descriptive::sd($numbers);                // same as standardDeviation;
$σ = Descriptive::standardDeviation($numbers); // same as sd;

// SD+ (Standard deviation for a population; uses population variance)
$SD＋ = Descriptive::sd($numbers, Descriptive::POPULATION); // POPULATION constant = true
$SD＋ = Descriptive::standardDeviation($numbers, true);     // same as sd with POPULATION constant

// Coefficient of variation (cᵥ)
$cᵥ = Descriptive::coefficientOfVariation($numbers);

// MAD - mean/median absolute deviations
$mean_mad   = Descriptive::meanAbsoluteDeviation($numbers);
$median_mad = Descriptive::medianAbsoluteDeviation($numbers);

// Quartiles (inclusive and exclusive methods)
// [0% => 13, Q1 => 13, Q2 => 14, Q3 => 17, 100% => 21, IQR => 4]
$quartiles = Descriptive::quartiles($numbers);          // Has optional parameter to specify method. Default is Exclusive
$quartiles = Descriptive::quartilesExclusive($numbers);
$quartiles = Descriptive::quartilesInclusive($numbers);

// IQR - Interquartile range
$IQR = Descriptive::interquartileRange($numbers); // Same as IQR; has optional parameter to specify quartile method.
$IQR = Descriptive::iqr($numbers);                // Same as interquartileRange; has optional parameter to specify quartile method.

// Percentiles
$twentieth_percentile    = Descriptive::percentile($numbers, 20);
$ninety_fifth_percentile = Descriptive::percentile($numbers, 95);

// Midhinge
$midhinge = Descriptive::midhinge($numbers);

// Describe a list of numbers - descriptive stats report
$stats = Descriptive::describe($numbers); // Has optional parameter to set population or sample calculations
print_r($stats);
/* Array (
    [n]          => 9
    [min]        => 13
    [max]        => 21
    [mean]       => 15
    [median]     => 14
    [mode]       => Array ( [0] => 13 )
    [range]      => 8
    [midrange]   => 17
    [variance]   => 8
    [sd]         => 2.8284271247462
    [cv]         => 0.18856180831641
    [mean_mad]   => 2.2222222222222
    [median_mad] => 1
    [quartiles]  => Array (
            [0%]   => 13
            [Q1]   => 13
            [Q2]   => 14
            [Q3]   => 17
            [100%] => 21
            [IQR]  => 4
        )
    [midhinge]   => 15
    [skewness]   => 1.4915533665654
    [ses]        => 0.71713716560064
    [kurtosis]   => 0.1728515625
    [sek]        => 1.3997084244475
    [sem]        => 0.94280904158206
    [ci_95]      => Array (
            [ci]          => 1.8478680091392
            [lower_bound] => 13.152131990861
            [upper_bound] => 16.847868009139
        )
    [ci_99]      => Array (
            [ci]          => 2.4285158135783
            [lower_bound] => 12.571484186422
            [upper_bound] => 17.428515813578
        )
) */

// Five number summary - five most important sample percentiles
$summary = Descriptive::fiveNumberSummary($numbers);
// [min, Q1, median, Q3, max]
```

### Statistics - Distance
```php
use MathPHP\Statistics\Distance;

// Probability distributions
$X = [0.2, 0.5, 0.3];
$Y = [0.1, 0.4, 0.5];

// Distances
$DB⟮X、Y⟯   = Distance::bhattacharyya($X, $Y);
$H⟮X、Y⟯    = Distance::hellinger($X, $Y);
$D⟮X、Y⟯    = Distance::minkowski($X, $Y, $p = 2);
$d⟮X、Y⟯    = Distance::euclidean($X, $Y);          // L² distance
$d₁⟮X、Y⟯   = Distance::manhattan($X, $Y);          // L¹ distance, taxicab geometry, city block distance
$JSD⟮X‖Y⟯   = Distance::jensenShannon($X, $Y);
$d⟮X、Y⟯    = Distance::canberra($X, Y);
brayCurtis = Distance::brayCurtis($X, $Y);
$cosine    = Distance::cosine($X, $Y);
$cos⟮α⟯     = Distance::cosineSimilarity($X, $Y);

// Mahalanobis distance
$x    = new Matrix([[6], [5]]);
$data = new Matrix([
    [4, 4, 5, 2, 3, 6, 9, 7, 4, 5],
    [3, 7, 5, 7, 9, 5, 6, 2, 2, 7],
]);
$otherData = new Matrix([
    [4, 4, 5, 2, 3, 6, 9, 7, 4, 5],
    [3, 7, 5, 7, 9, 5, 6, 2, 2, 7],
]);
$y = new Matrix([[2], [2]]);
$D = Distance::mahalanobis($x, $data);          // Mahalanobis distance from x to the centroid of the data.
$D = Distance::mahalanobis($x, $data, $y);      // Mahalanobis distance between $x and $y using the data.
$D = Distance::mahalanobis($data, $otherData);  // Mahalanobis distance between the centroids of two sets of data.
```

### Statistics - Distributions
```php
use MathPHP\Statistics\Distribution;

$grades = ['A', 'A', 'B', 'B', 'B', 'B', 'C', 'C', 'D', 'F'];

// Frequency distributions (frequency and relative frequency)
$frequencies          = Distribution::frequency($grades);         // [ A => 2,   B => 4,   C => 2,   D => 1,   F => 1   ]
$relative_frequencies = Distribution::relativeFrequency($grades); // [ A => 0.2, B => 0.4, C => 0.2, D => 0.1, F => 0.1 ]

// Cumulative frequency distributions (cumulative and cumulative relative)
$cumulative_frequencies          = Distribution::cumulativeFrequency($grades);         // [ A => 2,   B => 6,   C => 8,   D => 9,   F => 10  ]
$cumulative_relative_frequencies = Distribution::cumulativeRelativeFrequency($grades); // [ A => 0.2, B => 0.6, C => 0.8, D => 0.9, F => 1   ]

// Ranking of data
$values                       = [1, 2, 2, 3];
$ordinal_ranking              = Distribution::ordinalRanking($values);              // 1, 2, 3, 4
$standard_competition_ranking = Distribution::standardCompetitionRanking($values);  // 1, 2, 2, 4
$modified_competition_ranking = Distribution::modifiedCompetitionRanking($values);  // 1, 3, 3, 4
$fractional_ranking           = Distribution::fractionalRanking($values);           // 1, 2.5, 2.5, 4

// Stem and leaf plot
// Return value is array where keys are the stems, values are the leaves
$values             = [44, 46, 47, 49, 63, 64, 66, 68, 68, 72, 72, 75, 76, 81, 84, 88, 106];
$stem_and_leaf_plot = Distribution::stemAndLeafPlot($values);
// [4 => [4, 6, 7, 9], 5 => [], 6 => [3, 4, 6, 8, 8], 7 => [2, 2, 5, 6], 8 => [1, 4, 8], 9 => [], 10 => [6]]

// Optional second parameter will print stem and leaf plot to STDOUT
Distribution::stemAndLeafPlot($values, Distribution::PRINT);
/*
 4 | 4 6 7 9
 5 |
 6 | 3 4 6 8 8
 7 | 2 2 5 6
 8 | 1 4 8
 9 |
10 | 6
*/
```

### Statistics - Divergence
```php
use MathPHP\Statistics\Divergence;

// Probability distributions
$X = [0.2, 0.5, 0.3];
$Y = [0.1, 0.4, 0.5];

// Divergences
$Dkl⟮X‖Y⟯ = Divergence::kullbackLeibler($X, $Y);
$JSD⟮X‖Y⟯ = Divergence::jensenShannon($X, $Y);
```

### Statistics - Effect Size
```php
use MathPHP\Statistics\EffectSize;

$SSt = 24;  // Sum of squares treatment
$SSE = 300; // Sum of squares error
$SST = 600; // Sum of squares total
$dft = 1;   // Degrees of freedom treatment
$MSE = 18;  // Mean squares error

// η² - Eta-squared
$η²  = EffectSize::etaSquared($SSt, $SST);
$η²p = EffectSize::partialEtaSquared($SSt, $SSE);

// ω² - Omega-squared
$ω² = EffectSize::omegaSquared($SSt, $dft, $SST, $MSE);

// Cohen's ƒ²
$ƒ² = EffectSize::cohensF($η²);
$ƒ² = EffectSize::cohensF($ω²);
$ƒ² = EffectSize::cohensF($R²);

// Cohen's q
[$r₁, $r₂] = [0.1, 0.2];
$q = EffectSize::cohensQ($r₁, $r₂);

// Cohen's d
[$μ₁, $σ₁] = [6.7, 1.2];
[$μ₂, $σ₂] = [6, 1];
$d = EffectSize::cohensD($μ₁, $μ₂, $σ₁, $σ₂);

// Hedges' g
[$μ₁, $σ₁, $n₁] = [6.7, 1.2, 15];
[$μ₂, $σ₂, $n₂] = [6, 1, 15];
$g = EffectSize::hedgesG($μ₁, $μ₂, $σ₁, $σ₂, $n₁, $n₂);

// Glass' Δ
$Δ = EffectSize::glassDelta($μ₁, $μ₂, $σ₂);
```

### Statistics - Experiments
```php
use MathPHP\Statistics\Experiment;

$a = 28;   // Exposed and event present
$b = 129;  // Exposed and event absent
$c = 4;    // Non-exposed and event present
$d = 133;  // Non-exposed and event absent

// Risk ratio (relative risk) - RR
$RR = Experiment::riskRatio($a, $b, $c, $d);
// ['RR' => 6.1083, 'ci_lower_bound' => 2.1976, 'ci_upper_bound' => 16.9784, 'p' => 0.0005]

// Odds ratio (OR)
$OR = Experiment::oddsRatio($a, $b, $c, $d);
// ['OR' => 7.2171, 'ci_lower_bound' => 2.4624, 'ci_upper_bound' => 21.1522, 'p' => 0.0003]

// Likelihood ratios (positive and negative)
$LL = Experiment::likelihoodRatio($a, $b, $c, $d);
// ['LL+' => 7.4444, 'LL-' => 0.3626]

$sensitivity = 0.67;
$specificity = 0.91;
$LL          = Experiment::likelihoodRatioSS($sensitivity, $specificity);
```

### Statistics - Kernel Density Estimation
```php
use MathPHP\Statistics\KernelDensityEstimation

$data = [-2.76, -1.09, -0.5, -0.15, 0.22, 0.69, 1.34, 1.75];
$x    = 0.5;

// Density estimator with default bandwidth (normal distribution approximation) and kernel function (standard normal)
$kde     = new KernelDensityEstimation($data);
$density = $kde->evaluate($x)

// Custom bandwidth
$h = 0.1;
$kde->setBandwidth($h);

// Library of built-in kernel functions
$kde->setKernelFunction(KernelDensityEstimation::STANDARD_NORMAL);
$kde->setKernelFunction(KernelDensityEstimation::NORMAL);
$kde->setKernelFunction(KernelDensityEstimation::UNIFORM);
$kde->setKernelFunction(KernelDensityEstimation::TRIANGULAR);
$kde->setKernelFunction(KernelDensityEstimation::EPANECHNIKOV);
$kde->setKernelFunction(KernelDensityEstimation::TRICUBE);

// Set custom kernel function (user-provided callable)
$kernel = function ($x) {
  if (abs($x) > 1) {
      return 0;
  } else {
      return 70 / 81 * ((1 - abs($x) ** 3) ** 3);
  }
};
$kde->setKernelFunction($kernel);

// All customization optionally can be done in the constructor
$kde = new KernelDesnsityEstimation($data, $h, $kernel);
```

### Statistics - Multivariate - Principal Component Analysis
```php
use MathPHP\Statistics\Multivariate\PCA;
use MathPHP\LinearAlgebra\MatrixFactory;

// Given
$matrix = MatrixFactory::create($data);  // observations of possibly correlated variables
$center = true;                          // do mean centering of data
$scale  = true;                          // do standardization of data

// Build a principal component analysis model to explore
$model = new PCA($matrix, $center, $scale);

// Scores and loadings of the PCA model
$scores      = $model->getScores();       // Matrix of transformed standardized data with the loadings matrix
$loadings    = $model->getLoadings();     // Matrix of unit eigenvectors of the correlation matrix
$eigenvalues = $model->getEigenvalues();  // Vector of eigenvalues of components

// Residuals, limits, critical values and more
$R²         = $model->getR2();           // array of R² values
$cumR²      = $model->getCumR2();        // array of cummulative R² values
$Q          = $model->getQResiduals();   // Matrix of Q residuals
$T²         = $model->getT2Distances();  // Matrix of T² distances
$T²Critical = $model->getCriticalT2();   // array of critical limits of T²
$QCritical  = $model->getCriticalQ();    // array of critical limits of Q
```

### Statistics - Multivariate - Partial Least Squares Regression
```php
use MathPHP\Statistics\Multivariate\PLS;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\SampleData;

// Given
$cereal = new SampleData\Cereal();
$X      = MatrixFactory::createNumeric($cereal->getXData());
$Y      = MatrixFactory::createNumeric($cereal->getYData());

// Build a partial least squares regression to explore
$numberOfComponents = 5;
$scale              = true;
$pls                = new PLS($X, $Y, $numberOfComponents, $scale);

// PLS model data
$C = $pls->getYLoadings();     // Loadings for Y values (each loading column transforms F to U)
$W = $pls->getXLoadings();     // Loadings for X values (each loading column transforms E into T)
$T = $pls->getXScores();       // Scores for the X values (latent variables of X)
$U = $pls->getYScores();       // Scores for the Y values (latent variables of Y)
$B = $pls->getCoefficients();  // Regression coefficients (matrix that best transforms E into F)
$P = $pls->getProjections();   // Projection matrix (each projection column transforms T into Ê)

// Predict values (use regression model to predict new values of Y given values for X)
$yPredictions = $pls->predict($xMatrix);
```

### Statistics - Outlier
```php
use MathPHP\Statistics\Outlier;

$data = [199.31, 199.53, 200.19, 200.82, 201.92, 201.95, 202.18, 245.57];
$n    = 8;    // size of data
$𝛼    = 0.05; // significance level

// Grubb's test - two sided test
$grubbsStatistic = Outlier::grubbsStatistic($data, Outlier::TWO_SIDED);
$criticalValue   = Outlier::grubbsCriticalValue($𝛼, $n, Outlier::TWO_SIDED);

// Grubbs' test - one sided test of minimum value
$grubbsStatistic = Outlier::grubbsStatistic($data, Outlier::ONE_SIDED_LOWER);
$criticalValue   = Outlier::grubbsCriticalValue($𝛼, $n, Outlier::ONE_SIDED);

// Grubbs' test - one sided test of maximum value
$grubbsStatistic = Outlier::grubbsStatistic($data, Outlier::ONE_SIDED_UPPER);
$criticalValue   = Outlier::grubbsCriticalValue($𝛼, $n, Outlier::ONE_SIDED);
```

### Statistics - Random Variables
```php
use MathPHP\Statistics\RandomVariable;

$X = [1, 2, 3, 4];
$Y = [2, 3, 4, 5];

// Central moment (nth moment)
$second_central_moment = RandomVariable::centralMoment($X, 2);
$third_central_moment  = RandomVariable::centralMoment($X, 3);

// Skewness (population, sample, and alternative general method)
$skewness = RandomVariable::skewness($X);            // Optional type parameter to choose skewness type calculation. Defaults to sample skewness (similar to Excel's SKEW).
$skewness = RandomVariable::sampleSkewness($X);      // Same as RandomVariable::skewness($X, RandomVariable::SAMPLE_SKEWNESS) - Similar to Excel's SKEW, SAS and SPSS, R (e1071) skewness type 2
$skewness = RandomVariable::populationSkewness($X);  // Same as RandomVariable::skewness($X, RandomVariable::POPULATION_SKEWNESS) - Similar to Excel's SKEW.P, classic textbook definition, R (e1071) skewness type 1
$skewness = RandomVariable::alternativeSkewness($X); // Same as RandomVariable::skewness($X, RandomVariable::ALTERNATIVE_SKEWNESS) - Alternative, classic definition of skewness
$SES      = RandomVariable::ses(count($X));          // standard error of skewness

// Kurtosis (excess)
$kurtosis    = RandomVariable::kurtosis($X);           // Optional type parameter to choose kurtosis type calculation. Defaults to population kurtosis (similar to Excel's KURT).
$kurtosis    = RandomVariable::sampleKurtosis($X);     // Same as RandomVariable::kurtosis($X, RandomVariable::SAMPLE_KURTOSIS) -  Similar to R (e1071) kurtosis type 1
$kurtosis    = RandomVariable::populationKurtosis($X); // Same as RandomVariable::kurtosis($X, RandomVariable::POPULATION_KURTOSIS) - Similar to Excel's KURT, SAS and SPSS, R (e1071) kurtosis type 2
$platykurtic = RandomVariable::isPlatykurtic($X);      // true if kurtosis is less than zero
$leptokurtic = RandomVariable::isLeptokurtic($X);      // true if kurtosis is greater than zero
$mesokurtic  = RandomVariable::isMesokurtic($X);       // true if kurtosis is zero
$SEK         = RandomVariable::sek(count($X));         // standard error of kurtosis

// Standard error of the mean (SEM)
$sem = RandomVariable::standardErrorOfTheMean($X); // same as sem
$sem = RandomVariable::sem($X);                    // same as standardErrorOfTheMean

// Confidence interval
$μ  = 90; // sample mean
$n  = 9;  // sample size
$σ  = 36; // standard deviation
$cl = 99; // confidence level
$ci = RandomVariable::confidenceInterval($μ, $n, $σ, $cl); // Array( [ci] => 30.91, [lower_bound] => 59.09, [upper_bound] => 120.91 )
```

### Statistics - Regressions
```php
use MathPHP\Statistics\Regression;

$points = [[1,2], [2,3], [4,5], [5,7], [6,8]];

// Simple linear regression (least squares method)
$regression = new Regression\Linear($points);
$parameters = $regression->getParameters();          // [m => 1.2209302325581, b => 0.6046511627907]
$equation   = $regression->getEquation();            // y = 1.2209302325581x + 0.6046511627907
$y          = $regression->evaluate(5);              // Evaluate for y at x = 5 using regression equation
$ci         = $regression->ci(5, 0.5);               // Confidence interval for x = 5 with p-value of 0.5
$pi         = $regression->pi(5, 0.5);               // Prediction interval for x = 5 with p-value of 0.5; Optional number of trials parameter.
$Ŷ          = $regression->yHat();
$r          = $regression->r();                      // same as correlationCoefficient
$r²         = $regression->r2();                     // same as coefficientOfDetermination
$se         = $regression->standardErrors();         // [m => se(m), b => se(b)]
$t          = $regression->tValues();                // [m => t, b => t]
$p          = $regression->tProbability();           // [m => p, b => p]
$F          = $regression->fStatistic();
$p          = $regression->fProbability();
$h          = $regression->leverages();
$e          = $regression->residuals();
$D          = $regression->cooksD();
$DFFITS     = $regression->dffits();
$SStot      = $regression->sumOfSquaresTotal();
$SSreg      = $regression->sumOfSquaresRegression();
$SSres      = $regression->sumOfSquaresResidual();
$MSR        = $regression->meanSquareRegression();
$MSE        = $regression->meanSquareResidual();
$MSTO       = $regression->meanSquareTotal();
$error      = $regression->errorSd();                // Standard error of the residuals
$V          = $regression->regressionVariance();
$n          = $regression->getSampleSize();          // 5
$points     = $regression->getPoints();              // [[1,2], [2,3], [4,5], [5,7], [6,8]]
$xs         = $regression->getXs();                  // [1, 2, 4, 5, 6]
$ys         = $regression->getYs();                  // [2, 3, 5, 7, 8]
$ν          = $regression->degreesOfFreedom();

// Linear regression through a fixed point (least squares method)
$force_point = [0,0];
$regression  = new Regression\LinearThroughPoint($points, $force_point);
$parameters  = $regression->getParameters();
$equation    = $regression->getEquation();
$y           = $regression->evaluate(5);
$Ŷ           = $regression->yHat();
$r           = $regression->r();
$r²          = $regression->r2();
 ⋮                     ⋮

// Theil–Sen estimator (Sen's slope estimator, Kendall–Theil robust line)
$regression  = new Regression\TheilSen($points);
$parameters  = $regression->getParameters();
$equation    = $regression->getEquation();
$y           = $regression->evaluate(5);
 ⋮                     ⋮

// Use Lineweaver-Burk linearization to fit data to the Michaelis–Menten model: y = (V * x) / (K + x)
$regression  = new Regression\LineweaverBurk($points);
$parameters  = $regression->getParameters();  // [V, K]
$equation    = $regression->getEquation();    // y = Vx / (K + x)
$y           = $regression->evaluate(5);
 ⋮                     ⋮

// Use Hanes-Woolf linearization to fit data to the Michaelis–Menten model: y = (V * x) / (K + x)
$regression  = new Regression\HanesWoolf($points);
$parameters  = $regression->getParameters();  // [V, K]
$equation    = $regression->getEquation();    // y = Vx / (K + x)
$y           = $regression->evaluate(5);
 ⋮                     ⋮

// Power law regression - power curve (least squares fitting)
$regression = new Regression\PowerLaw($points);
$parameters = $regression->getParameters();   // [a => 56.483375436574, b => 0.26415375648621]
$equation   = $regression->getEquation();     // y = 56.483375436574x^0.26415375648621
$y          = $regression->evaluate(5);
 ⋮                     ⋮

// LOESS - Locally Weighted Scatterplot Smoothing (Local regression)
$α          = 1/3;                         // Smoothness parameter
$λ          = 1;                           // Order of the polynomial fit
$regression = new Regression\LOESS($points, $α, $λ);
$y          = $regression->evaluate(5);
$Ŷ          = $regression->yHat();
 ⋮                     ⋮
```

### Statistics - Significance Testing
```php
use MathPHP\Statistics\Significance;

// Z test - One sample (z and p values)
$Hₐ = 20;   // Alternate hypothesis (M Sample mean)
$n  = 200;  // Sample size
$H₀ = 19.2; // Null hypothesis (μ Population mean)
$σ  = 6;    // SD of population (Standard error of the mean)
$z  = Significance:zTest($Hₐ, $n, $H₀, $σ);           // Same as zTestOneSample
$z  = Significance:zTestOneSample($Hₐ, $n, $H₀, $σ);  // Same as zTest
/* [
  'z'  => 1.88562, // Z score
  'p1' => 0.02938, // one-tailed p value
  'p2' => 0.0593,  // two-tailed p value
] */

// Z test - Two samples (z and p values)
$μ₁ = 27;   // Sample mean of population 1
$μ₂ = 33;   // Sample mean of population 2
$n₁ = 75;   // Sample size of population 1
$n₂ = 50;   // Sample size of population 2
$σ₁ = 14.1; // Standard deviation of sample mean 1
$σ₂ = 9.5;  // Standard deviation of sample mean 2
$z  = Significance::zTestTwoSample($μ₁, $μ₂, $n₁, $n₂, $σ₁, $σ₂);
/* [
  'z'  => -2.36868418147285,  // z score
  'p1' => 0.00893,            // one-tailed p value
  'p2' => 0.0179,             // two-tailed p value
] */

// Z score
$M = 8; // Sample mean
$μ = 7; // Population mean
$σ = 1; // Population SD
$z = Significance::zScore($M, $μ, $σ);

// T test - One sample (from sample data)
$a     = [3, 4, 4, 5, 5, 5, 6, 6, 7, 8]; // Data set
$H₀    = 300;                            // Null hypothesis (μ₀ Population mean)
$tTest = Significance::tTest($a, $H₀)
print_r($tTest);
/* Array (
    [t]    => 0.42320736951516  // t score
    [df]   => 9                 // degrees of freedom
    [p1]   => 0.34103867713806  // one-tailed p value
    [p2]   => 0.68207735427613  // two-tailed p value
    [mean] => 5.3               // sample mean
    [sd]   => 1.4944341180973   // standard deviation
) */

// T test - One sample (from summary data)
$Hₐ    = 280; // Alternate hypothesis (M Sample mean)
$s     = 50;  // Standard deviation of sample
$n     = 15;  // Sample size
$H₀    = 300; // Null hypothesis (μ₀ Population mean)
$tTest = Significance::tTestOneSampleFromSummaryData($Hₐ, $s, $n, $H₀);
print_r($tTest);
/* Array (
    [t]    => -1.549193338483    // t score
    [df]   => 14                 // degreees of freedom
    [p1]   => 0.071820000122611  // one-tailed p value
    [p2]   => 0.14364000024522   // two-tailed p value
    [mean] => 280                // sample mean
    [sd]   => 50                 // standard deviation
) */

// T test - Two samples (from sample data)
$x₁    = [27.5, 21.0, 19.0, 23.6, 17.0, 17.9, 16.9, 20.1, 21.9, 22.6, 23.1, 19.6, 19.0, 21.7, 21.4];
$x₂    = [27.1, 22.0, 20.8, 23.4, 23.4, 23.5, 25.8, 22.0, 24.8, 20.2, 21.9, 22.1, 22.9, 20.5, 24.4];
$tTest = Significance::tTest($x₁, $x₂);
print_r($tTest);
/* Array (
    [t]     => -2.4553600286929   // t score
    [df]    => 24.988527070145    // degrees of freedom
    [p1]    => 0.010688914613979  // one-tailed p value
    [p2]    => 0.021377829227958  // two-tailed p value
    [mean1] => 20.82              // mean of sample x₁
    [mean2] => 22.98667           // mean of sample x₂
    [sd1]   => 2.804894           // standard deviation of x₁
    [sd2]   => 1.952605           // standard deviation of x₂
) */

// T test - Two samples (from summary data)
$μ₁    = 42.14; // Sample mean of population 1
$μ₂    = 43.23; // Sample mean of population 2
$n₁    = 10;    // Sample size of population 1
$n₂    = 10;    // Sample size of population 2
$σ₁    = 0.683; // Standard deviation of sample mean 1
$σ₂    = 0.750; // Standard deviation of sample mean 2
$tTest = Significance::tTestTwoSampleFromSummaryData($μ₁, $μ₂, $n₁, $n₂, $σ₁, $σ₂);
print_r($tTest);
/* Array (
   [t] => -3.3972305988708     // t score
   [df] => 17.847298548027     // degrees of freedom
   [p1] => 0.0016211251126198  // one-tailed p value
   [p2] => 0.0032422502252396  // two-tailed p value
   [mean1] => 42.14
   [mean2] => 43.23
   [sd1] => 0.6834553
   [sd2] => 0.7498889
] */

// T score
$Hₐ = 280; // Alternate hypothesis (M Sample mean)
$s  = 50;  // SD of sample
$n  = 15;  // Sample size
$H₀ = 300; // Null hypothesis (μ₀ Population mean)
$t  = Significance::tScore($Hₐ, $s, $n, $H);

// χ² test (chi-squared goodness of fit test)
$observed = [4, 6, 17, 16, 8, 9];
$expected = [10, 10, 10, 10, 10, 10];
$χ²       = Significance::chiSquaredTest($observed, $expected);
// ['chi-square' => 14.2, 'p' => 0.014388]
```

### Trigonometry
```php
use MathPHP\Trigonometry;

$n      = 9;
$points = Trigonometry::unitCircle($n); // Produce n number of points along the unit circle
```

Unit Tests
----------

Beyond 100% code coverage!

MathPHP has thousands of unit tests testing individual functions directly with numerous data inputs to achieve 100% test coverage.
MathPHP unit tests also test mathematical axioms which indirectly test the same functions in multiple different ways ensuring that those math properties all work out according to the axioms.

```bash
$ cd tests
$ phpunit
```

[![Coverage Status](https://coveralls.io/repos/github/markrogoyski/math-php/badge.svg?branch=master)](https://coveralls.io/github/markrogoyski/math-php?branch=master)

Standards
---------

MathPHP conforms to the following standards:

 * PSR-1  - Basic coding standard (http://www.php-fig.org/psr/psr-1/)
 * PSR-4  - Autoloader (http://www.php-fig.org/psr/psr-4/)
 * PSR-12 - Extended coding style guide (http://www.php-fig.org/psr/psr-12/)

License
-------

MathPHP is licensed under the MIT License.
