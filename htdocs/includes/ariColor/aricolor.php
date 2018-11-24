<?php
/**
 * Plugin Name:   ariColor
 * Plugin URI:    http://aristath.github.io/ariColor/
 * Description:   A PHP library for color manipulation in WordPress themes and plugins
 * Author:        Aristeides Stathopoulos
 * Author URI:    http://aristeides.com
 * Version:       1.1.0
 * Text Domain:   aricolor
 *
 * GitHub Plugin URI: aristath/ariColor
 * GitHub Plugin URI: https://github.com/aristath/ariColor
 *
 * @package     ariColor
 * @category    Core
 * @author      Aristeides Stathopoulos
 * @copyright   Copyright (c) 2016, Aristeides Stathopoulos
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly.
// if ( ! defined( 'ABSPATH' ) ) {
// 	exit;
// }

if ( ! class_exists( 'ariColor' ) ) {
	/**
	 * The color calculations class.
	 */
	class ariColor {

		/**
		 * An array of our instances.
		 *
		 * @static
		 * @access public
		 * @since 1.0.0
		 * @var array
		 */
		public static $instances = array();

		/**
		 * The color initially set.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var mixed
		 */
		public $color;

		/**
		 * A fallback color in case of failure.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var mixed
		 */
		public $fallback = '#ffffff';

		/**
		 * Fallback object from the fallback color.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var object
		 */
		public $fallback_obj;

		/**
		 * The mode we're using for this color.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var string
		 */
		public $mode = 'hex';

		/**
		 * An array containing all word-colors (white/blue/red etc)
		 * and their corresponding HEX codes.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var array
		 */
		public $word_colors = array();

		/**
		 * The hex code of the color.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var string
		 */
		public $hex;

		/**
		 * Red value.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var int
		 */
		public $red   = 0;

		/**
		 * Green value.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var int
		 */
		public $green = 0;

		/**
		 * Blue value.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var int
		 */
		public $blue  = 0;

		/**
		 * Alpha value (min:0, max: 1)
		 *
		 * @access public
		 * @since 1.0.0
		 * @var float
		 */
		public $alpha = 1;

		/**
		 * Hue value.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var float
		 */
		public $hue;

		/**
		 * Saturation value.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var float
		 */
		public $saturation;

		/**
		 * Lightness value.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var float
		 */
		public $lightness;

		/**
		 * Chroma value.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var float
		 */
		public $chroma;

		/**
		 * An array containing brightnesses.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var array
		 */
		public $brightness = array();

		/**
		 * Luminance value.
		 *
		 * @access public
		 * @since 1.0.0
		 * @var float
		 */
		public $luminance;

		/**
		 * The class constructor.
		 *
		 * @access protected
		 * @since 1.0.0
		 * @param string|array $color The color.
		 * @param string       $mode  The color mode. Leave empty to auto-detect.
		 */
		protected function __construct( $color = '', $mode = 'auto' ) {
			$this->color = $color;

			if ( is_array( $color ) && isset( $color['fallback'] ) ) {
				$this->fallback = $color['fallback'];
				$this->fallback_obj = self::newColor( $this->fallback );
			}

			if ( ! method_exists( $this, 'from_' . $mode ) ) {
				$mode = $this->get_mode( $color );
			}

			$this->mode = $mode;

			if ( ! $mode ) {
				return;
			}

			$this->mode = $mode;
			$method = 'from_' . $mode;
			// Call the from_{$color_mode} method.
			$this->$method();
		}

		/**
		 * Gets an instance for this color.
		 * We use a separate instance per color
		 * because there's no need to create a completely new instance each time we call this class.
		 * Instead using instances helps us improve performance & footprint.
		 *
		 * @static
		 * @access public
		 * @since 1.0.0
		 * @param string|array $color The color.
		 * @param string       $mode  Mode to be used.
		 * @return ariColor (object)
		 */
		public static function newColor( $color, $mode = 'auto' ) {

			// Get an md5 for this color.
			$color_md5 = ( is_array( $color ) ) ? md5( json_encode( $color ) . $mode ) : md5( $color . $mode );
			// Set the instance if it does not already exist.
			if ( ! isset( self::$instances[ $color_md5 ] ) ) {
				self::$instances[ $color_md5 ] = new self( $color, $mode );
			}
			return self::$instances[ $color_md5 ];
		}

		/**
		 * Alias of the newColor method.
		 *
		 * @static
		 * @access public
		 * @since 1.1
		 * @param string|array $color The color.
		 * @param string       $mode  Mode to be used.
		 * @return ariColor (object)
		 */
		public static function new_color( $color, $mode = 'auto' ) {
			return self::newColor( $color, $mode );
		}

		/**
		 * Allows us to get a new instance by modifying a property of the existing one.
		 *
		 * @access public
		 * @since 1.0.0
		 * @param string           $property   Can be one of the following:
		 *                             red,
		 *                             green,
		 *                             blue,
		 *                             alpha,
		 *                             hue,
		 *                             saturation,
		 *                             lightness,
		 *                             brightness.
		 * @param int|float|string $value      The new value.
		 * @return ariColor|null
		 */
		public function getNew( $property = '', $value = '' ) {

			if ( in_array( $property, array( 'red', 'green', 'blue', 'alpha' ), true ) ) {
				// Check if we're changing any of the rgba values.
				$value = max( 0, min( 255, $value ) );
				if ( 'red' === $property ) {
					return self::new_color( 'rgba(' . $value . ',' . $this->green . ',' . $this->blue . ',' . $this->alpha . ')', 'rgba' );
				} elseif ( 'green' === $property ) {
					return self::new_color( 'rgba(' . $this->red . ',' . $value . ',' . $this->blue . ',' . $this->alpha . ')', 'rgba' );
				} elseif ( 'blue' === $property ) {
					return self::new_color( 'rgba(' . $this->red . ',' . $this->green . ',' . $value . ',' . $this->alpha . ')', 'rgba' );
				} elseif ( 'alpha' === $property ) {
					return self::new_color( 'rgba(' . $this->red . ',' . $this->green . ',' . $this->blue . ',' . $value . ')', 'rgba' );
				}
			} elseif ( in_array( $property, array( 'hue', 'saturation', 'lightness' ), true ) ) {
				// Check if we're changing any of the hsl values.
				$value = ( 'hue' === $property ) ? max( 0, min( 360, $value ) ) : max( 0, min( 100, $value ) );

				if ( 'hue' === $property ) {
					return self::new_color( 'hsla(' . $value . ',' . $this->saturation . '%,' . $this->lightness . '%,' . $this->alpha . ')', 'hsla' );
				} elseif ( 'saturation' === $property ) {
					return self::new_color( 'hsla(' . $this->hue . ',' . $value . '%,' . $this->lightness . '%,' . $this->alpha . ')', 'hsla' );
				} elseif ( 'lightness' === $property ) {
					return self::new_color( 'hsla(' . $this->hue . ',' . $this->saturation . '%,' . $value . '%,' . $this->alpha . ')', 'hsla' );
				}
			} elseif ( 'brightness' === $property ) {
				// Check if we're changing the brightness.
				if ( $value < $this->brightness['total'] ) {
					$red   = max( 0, min( 255, $this->red - ( $this->brightness['total'] - $value ) ) );
					$green = max( 0, min( 255, $this->green - ( $this->brightness['total'] - $value ) ) );
					$blue  = max( 0, min( 255, $this->blue - ( $this->brightness['total'] - $value ) ) );
				} elseif ( $value > $this->brightness['total'] ) {
					$red   = max( 0, min( 255, $this->red + ( $value - $this->brightness['total'] ) ) );
					$green = max( 0, min( 255, $this->green + ( $value - $this->brightness['total'] ) ) );
					$blue  = max( 0, min( 255, $this->blue + ( $value - $this->brightness['total'] ) ) );
				} else {
					// If it's not smaller and it's not greater, then it's equal.
					return $this;
				}
				return self::new_color( 'rgba(' . $red . ',' . $green . ',' . $blue . ',' . $this->alpha . ')', 'rgba' );
			}
			return null;
		}

		/**
		 * Allias for the getNew method.
		 *
		 * @access public
		 * @since 1.1.0
		 * @param string           $property   Can be one of the following:
		 *                             red,
		 *                             green,
		 *                             blue,
		 *                             alpha,
		 *                             hue,
		 *                             saturation,
		 *                             lightness,
		 *                             brightness.
		 * @param int|float|string $value      The new value.
		 * @return ariColor|null
		 */
		public function get_new( $property = '', $value = '' ) {
			return $this->getNew( $property, $value );
		}

		/**
		 * Figure out what mode we're using.
		 *
		 * @access public
		 * @since 1.0.0
		 * @param string|array $color The color we're querying.
		 * @return string
		 */
		public function get_mode( $color ) {

			// Check if value is an array.
			if ( is_array( $color ) ) {
				// Does the array have an 'rgba' key?
				if ( isset( $color['rgba'] ) ) {
					$this->color = $color['rgba'];
					return 'rgba';
				} elseif ( isset( $color['color'] ) ) {
					// Does the array have a 'color' key?
					$this->color = $color['color'];
					if ( is_string( $color['color'] ) && false !== strpos( $color['color'], 'rgba' ) ) {
						return 'rgba';
					}
					return 'hex';
				}
				// Is this a simple array with 4 items?
				if ( 4 === count( $color ) && isset( $color[0] ) && isset( $color[1] ) && isset( $color[2] ) && isset( $color[3] ) ) {
					$this->color = 'rgba(' . intval( $color[0] ) . ',' . intval( $color[1] ) . ',' . intval( $color[2] ) . ',' . intval( $color[3] ) . ')';
					return 'rgba';
				} elseif ( 3 === count( $color ) && isset( $color[0] ) && isset( $color[1] ) && isset( $color[2] ) ) {
					// Is this a simple array with 3 items?
					$this->color = 'rgba(' . intval( $color[0] ) . ',' . intval( $color[1] ) . ',' . intval( $color[2] ) . ',1)';
					return 'rgba';
				}

				// Check for other keys in the array and get values from there.
				$finders_keepers = array(
					'r'       => 'red',
					'g'       => 'green',
					'b'       => 'blue',
					'a'       => 'alpha',
					'red'     => 'red',
					'green'   => 'green',
					'blue'    => 'blue',
					'alpha'   => 'alpha',
					'opacity' => 'alpha',
				);
				$found = false;
				foreach ( $finders_keepers as $finder => $keeper ) {
					if ( isset( $color[ $finder ] ) ) {
						$found = true;
						$this->$keeper = $color[ $finder ];
					}
				}

				// We failed, use fallback.
				if ( ! $found ) {
					$this->from_fallback();
					return $this->mode;
				}

				// We did not fail, so use rgba values recovered above.
				$this->color = 'rgba(' . $this->red . ',' . $this->green . ',' . $this->blue . ',' . $this->alpha . ')';
				return 'rgba';
			}

			$color = trim( strtolower( $color ) );

			if ( 'transparent' === $color ) {
				$color = 'rgba(255,255,255,0)';
				$this->color = $color;
			}

			// If a string and 3 or 6 characters long, add # since it's a hex.
			if ( 3 === strlen( $this->color ) || 6 === strlen( $this->color ) && false === strpos( $this->color, '#' ) ) {
				$this->color = '#' . $this->color;
				$color = $this->color;
			}

			// If we got this far, it's not an array.
			// Check for key identifiers in the value.
			$finders_keepers = array(
				'#'    => 'hex',
				'rgba' => 'rgba',
				'rgb'  => 'rgb',
				'hsla' => 'hsla',
				'hsl'  => 'hsl',
			);
			foreach ( $finders_keepers as $finder => $keeper ) {
				if ( false !== strrpos( $color, $finder ) ) {

					// Make sure hex colors have 6 digits and not more.
					if ( '#' === $finder && 7 < strlen( $color ) ) {
						$this->color = substr( $color, 0, 7 );
					}

					return $keeper;
				}
			}
			// Perhaps we're using a word like "orange"?
			$wordcolors = $this->get_word_colors();
			if ( array_key_exists( $color, $wordcolors ) ) {
				$this->color = '#' . $wordcolors[ $color ];
				return 'hex';
			}
			// Fallback to hex.

			$this->color = $this->fallback;
			return 'hex';
		}

        protected function sanitize_hex_color( $color ) {
            if ( '' === $color ) {
                return '';
            }

            // 3 or 6 hex digits, or the empty string.
            if ( preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
                return $color;
            }
        }

        protected function maybe_hash_hex_color( $color ) {
            if ( $unhashed = $this->sanitize_hex_color_no_hash( $color ) ) {
                return '#' . $unhashed;
            }

            return $color;
        }

        protected function sanitize_hex_color_no_hash( $color ) {
            $color = ltrim( $color, '#' );

            if ( '' === $color ) {
                return '';
            }

            return $this->sanitize_hex_color( '#' . $color ) ? $color : null;
        }

        /**
		 * Starts with a HEX color and calculates all other properties.
		 *
		 * @access protected
		 * @since 1.0.0
		 * @return void
		 */
		protected function from_hex() {

			// Is this perhaps a word-color?
            $word_colors = $this->get_word_colors();
			if ( array_key_exists( $this->color, $word_colors ) ) {
				$this->color = '#' . $word_colors[ $this->color ];
			}
			// Sanitize color.
			$this->hex = $this->sanitize_hex_color( $this->maybe_hash_hex_color( $this->color ) );
			$hex = ltrim( $this->hex, '#' );

			// Fallback if needed.
			if ( ! $hex || 3 > strlen( $hex ) ) {
				$this->from_fallback();
				return;
			}
			// Make sure we have 6 digits for the below calculations.
			if ( 3 === strlen( $hex ) ) {
				$hex = ltrim( $this->hex, '#' );
				$hex = substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) . substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) . substr( $hex, 2, 1 ) . substr( $hex, 2, 1 );
			}

			// Set red, green, blue.
			$this->red   = hexdec( substr( $hex, 0, 2 ) );
			$this->green = hexdec( substr( $hex, 2, 2 ) );
			$this->blue  = hexdec( substr( $hex, 4, 2 ) );
			$this->alpha = 1;
			// Set other color properties.
			$this->set_brightness();
			$this->set_hsl();
			$this->set_luminance();

		}

		/**
		 * Starts with an RGB color and calculates all other properties.
		 *
		 * @access protected
		 * @since 1.0.0
		 * @return void
		 */
		protected function from_rgb() {
			$value = explode( ',', str_replace( array( ' ', 'rgb', '(', ')' ), '', $this->color ) );
			// Set red, green, blue.
			$this->red   = ( isset( $value[0] ) ) ? intval( $value[0] ) : 255;
			$this->green = ( isset( $value[1] ) ) ? intval( $value[1] ) : 255;
			$this->blue  = ( isset( $value[2] ) ) ? intval( $value[2] ) : 255;
			$this->alpha = 1;
			// Set the hex.
			$this->hex = $this->rgb_to_hex( $this->red, $this->green, $this->blue );
			// Set other color properties.
			$this->set_brightness();
			$this->set_hsl();
			$this->set_luminance();
		}

		/**
		 * Starts with an RGBA color and calculates all other properties.
		 *
		 * @access protected
		 * @since 1.0.0
		 * @return void
		 */
		protected function from_rgba() {
			// Set r, g, b, a properties.
			$value = explode( ',', str_replace( array( ' ', 'rgba', '(', ')' ), '', $this->color ) );
			$this->red   = ( isset( $value[0] ) ) ? intval( $value[0] ) : 255;
			$this->green = ( isset( $value[1] ) ) ? intval( $value[1] ) : 255;
			$this->blue  = ( isset( $value[2] ) ) ? intval( $value[2] ) : 255;
			$this->alpha = ( isset( $value[3] ) ) ? filter_var( $value[3], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION ) : 1;
			// Limit values in the range of 0 - 255.
			$this->red   = max( 0, min( 255, $this->red ) );
			$this->green = max( 0, min( 255, $this->green ) );
			$this->blue  = max( 0, min( 255, $this->blue ) );
			// Limit values 0 - 1.
			$this->alpha = max( 0, min( 1, $this->alpha ) );
			// Set hex.
			$this->hex = $this->rgb_to_hex( $this->red, $this->green, $this->blue );
			// Set other color properties.
			$this->set_brightness();
			$this->set_hsl();
			$this->set_luminance();
		}

		/**
		 * Starts with an HSL color and calculates all other properties.
		 *
		 * @access protected
		 * @since 1.0.0
		 * @return void
		 */
		protected function from_hsl() {
			$value = explode( ',', str_replace( array( ' ', 'hsl', '(', ')', '%' ), '', $this->color ) );
			$this->hue        = $value[0];
			$this->saturation = $value[1];
			$this->lightness  = $value[2];
			$this->from_hsl_array();
		}

		/**
		 * Starts with an HSLA color and calculates all other properties.
		 *
		 * @access protected
		 * @since 1.0.0
		 * @return void
		 */
		protected function from_hsla() {
			$value = explode( ',', str_replace( array( ' ', 'hsla', '(', ')', '%' ), '', $this->color ) );
			$this->hue        = $value[0];
			$this->saturation = $value[1];
			$this->lightness  = $value[2];
			$this->alpha      = $value[3];
			$this->from_hsl_array();
		}

		/**
		 * Generates the HEX value of a color given values for $red, $green, $blue.
		 *
		 * @access protected
		 * @since 1.0.0
		 * @param int|string $red   The red value of this color.
		 * @param int|string $green The green value of this color.
		 * @param int|string $blue  The blue value of this color.
		 * @return string
		 */
		protected function rgb_to_hex( $red, $green, $blue ) {
			// Get hex values properly formatted.
			$hex_red   = $this->dexhex_double_digit( $red );
			$hex_green = $this->dexhex_double_digit( $green );
			$hex_blue  = $this->dexhex_double_digit( $blue );
			return '#' . $hex_red . $hex_green . $hex_blue;
		}

		/**
		 * Convert a decimal value to hex and make sure it's 2 characters.
		 *
		 * @access protected
		 * @since 1.0.0
		 * @param int|string $value The value to convert.
		 * @return string
		 */
		protected function dexhex_double_digit( $value ) {
			$value = dechex( $value );
			if ( 1 === strlen( $value ) ) {
				$value = '0' . $value;
			}
			return $value;
		}

		/**
		 * Calculates the red, green, blue values of an HSL color.
		 *
		 * @access protected
		 * @since 1.0.0
		 * @see https://gist.github.com/brandonheyer/5254516
		 */
		protected function from_hsl_array() {
			$h = $this->hue / 360;
			$s = $this->saturation / 100;
			$l = $this->lightness / 100;

			$r = $l;
			$g = $l;
			$b = $l;
			$v = ( $l <= 0.5 ) ? ( $l * ( 1.0 + $s ) ) : ( $l + $s - $l * $s );
			if ( $v > 0 ) {
				$m = $l + $l - $v;
				$sv = ( $v - $m ) / $v;
				$h *= 6.0;
				$sextant = floor( $h );
				$fract = $h - $sextant;
				$vsf = $v * $sv * $fract;
				$mid1 = $m + $vsf;
				$mid2 = $v - $vsf;
				switch ( $sextant ) {
					case 0:
						$r = $v;
						$g = $mid1;
						$b = $m;
						break;
					case 1:
						$r = $mid2;
						$g = $v;
						$b = $m;
						break;
					case 2:
						$r = $m;
						$g = $v;
						$b = $mid1;
						break;
					case 3:
						$r = $m;
						$g = $mid2;
						$b = $v;
						break;
					case 4:
						$r = $mid1;
						$g = $m;
						$b = $v;
						break;
					case 5:
						$r = $v;
						$g = $m;
						$b = $mid2;
						break;
				}
			}
			$this->red   = round( $r * 255, 0 );
			$this->green = round( $g * 255, 0 );
			$this->blue  = round( $b * 255, 0 );

			$this->hex = $this->rgb_to_hex( $this->red, $this->green, $this->blue );
			$this->set_luminance();
		}

		/**
		 * Returns a CSS-formatted value for colors.
		 *
		 * @access public
		 * @since 1.0.0
		 * @param string $mode The mode we're using.
		 * @return string
		 */
		public function toCSS( $mode = 'hex' ) {

			$value = '';

			switch ( $mode ) {
				case 'hex':
					$value = strtolower( $this->hex );
					break;
				case 'rgba':
					$value = 'rgba(' . $this->red . ',' . $this->green . ',' . $this->blue . ',' . $this->alpha . ')';
					break;
				case 'rgb':
					$value = 'rgb(' . $this->red . ',' . $this->green . ',' . $this->blue . ')';
					break;
				case 'hsl':
					$value = 'hsl(' . $this->hue . ',' . round( $this->saturation ) . '%,' . round( $this->lightness ) . '%)';
					break;
				case 'hsla':
					$value = 'hsla(' . $this->hue . ',' . round( $this->saturation ) . '%,' . round( $this->lightness ) . '%,' . $this->alpha . ')';
					break;
			}
			return $value;
		}

		/**
		 * Alias for the toCSS method.
		 *
		 * @access public
		 * @since 1.1
		 * @param string $mode The mode we're using.
		 * @return string
		 */
		public function to_css( $mode = 'hex' ) {
			return $this->toCSS( $mode );
		}

		/**
		 * Sets the HSL values of a color based on the values of red, green, blue.
		 *
		 * @access public
		 * @since 1.0.0
		 */
		protected function set_hsl() {
			$red   = $this->red / 255;
			$green = $this->green / 255;
			$blue  = $this->blue / 255;

			$max = max( $red, $green, $blue );
			$min = min( $red, $green, $blue );

			$lightness  = ( $max + $min ) / 2;
			$difference = $max - $min;

			if ( ! $difference ) {
				$hue = $saturation = 0; // Achromatic.
			} else {
				$saturation = $difference / ( 1 - abs( 2 * $lightness - 1 ) );
				switch ( $max ) {
					case $red:
						$hue = 60 * fmod( ( ( $green - $blue ) / $difference ), 6 );
						if ( $blue > $green ) {
							$hue += 360;
						}
						break;
					case $green:
						$hue = 60 * ( ( $blue - $red ) / $difference + 2 );
						break;
					case $blue:
						$hue = 60 * ( ( $red - $green ) / $difference + 4 );
						break;
				}
			}

			$this->hue        = round( $hue );
			$this->saturation = round( $saturation * 100 );
			$this->lightness  = round( $lightness * 100 );
		}

		/**
		 * Sets the brightness of a color based on the values of red, green, blue.
		 *
		 * @access protected
		 * @since 1.0.0
		 */
		protected function set_brightness() {
			$this->brightness = array(
				'red'   => round( $this->red * .299 ),
				'green' => round( $this->green * .587 ),
				'blue'  => round( $this->blue * .114 ),
				'total' => intval( ( $this->red * .299 ) + ( $this->green * .587 ) + ( $this->blue * .114 ) ),
			);
		}

		/**
		 * Sets the luminance of a color (range:0-255) based on the values of red, green, blue.
		 *
		 * @access protected
		 * @since 1.0.0
		 */
		protected function set_luminance() {
			$lum = ( 0.2126 * $this->red ) + ( 0.7152 * $this->green ) + ( 0.0722 * $this->blue );
			$this->luminance = round( $lum );
		}

		/**
		 * Gets an array of all the wordcolors.
		 *
		 * @access protected
		 * @since 1.0.0
		 * @return array
		 */
		protected function get_word_colors() {
			return array(
				'aliceblue'            => 'F0F8FF',
				'antiquewhite'         => 'FAEBD7',
				'aqua'                 => '00FFFF',
				'aquamarine'           => '7FFFD4',
				'azure'                => 'F0FFFF',
				'beige'                => 'F5F5DC',
				'bisque'               => 'FFE4C4',
				'black'                => '000000',
				'blanchedalmond'       => 'FFEBCD',
				'blue'                 => '0000FF',
				'blueviolet'           => '8A2BE2',
				'brown'                => 'A52A2A',
				'burlywood'            => 'DEB887',
				'cadetblue'            => '5F9EA0',
				'chartreuse'           => '7FFF00',
				'chocolate'            => 'D2691E',
				'coral'                => 'FF7F50',
				'cornflowerblue'       => '6495ED',
				'cornsilk'             => 'FFF8DC',
				'crimson'              => 'DC143C',
				'cyan'                 => '00FFFF',
				'darkblue'             => '00008B',
				'darkcyan'             => '008B8B',
				'darkgoldenrod'        => 'B8860B',
				'darkgray'             => 'A9A9A9',
				'darkgreen'            => '006400',
				'darkgrey'             => 'A9A9A9',
				'darkkhaki'            => 'BDB76B',
				'darkmagenta'          => '8B008B',
				'darkolivegreen'       => '556B2F',
				'darkorange'           => 'FF8C00',
				'darkorchid'           => '9932CC',
				'darkred'              => '8B0000',
				'darksalmon'           => 'E9967A',
				'darkseagreen'         => '8FBC8F',
				'darkslateblue'        => '483D8B',
				'darkslategray'        => '2F4F4F',
				'darkslategrey'        => '2F4F4F',
				'darkturquoise'        => '00CED1',
				'darkviolet'           => '9400D3',
				'deeppink'             => 'FF1493',
				'deepskyblue'          => '00BFFF',
				'dimgray'              => '696969',
				'dimgrey'              => '696969',
				'dodgerblue'           => '1E90FF',
				'firebrick'            => 'B22222',
				'floralwhite'          => 'FFFAF0',
				'forestgreen'          => '228B22',
				'fuchsia'              => 'FF00FF',
				'gainsboro'            => 'DCDCDC',
				'ghostwhite'           => 'F8F8FF',
				'gold'                 => 'FFD700',
				'goldenrod'            => 'DAA520',
				'gray'                 => '808080',
				'green'                => '008000',
				'greenyellow'          => 'ADFF2F',
				'grey'                 => '808080',
				'honeydew'             => 'F0FFF0',
				'hotpink'              => 'FF69B4',
				'indianred'            => 'CD5C5C',
				'indigo'               => '4B0082',
				'ivory'                => 'FFFFF0',
				'khaki'                => 'F0E68C',
				'lavender'             => 'E6E6FA',
				'lavenderblush'        => 'FFF0F5',
				'lawngreen'            => '7CFC00',
				'lemonchiffon'         => 'FFFACD',
				'lightblue'            => 'ADD8E6',
				'lightcoral'           => 'F08080',
				'lightcyan'            => 'E0FFFF',
				'lightgoldenrodyellow' => 'FAFAD2',
				'lightgray'            => 'D3D3D3',
				'lightgreen'           => '90EE90',
				'lightgrey'            => 'D3D3D3',
				'lightpink'            => 'FFB6C1',
				'lightsalmon'          => 'FFA07A',
				'lightseagreen'        => '20B2AA',
				'lightskyblue'         => '87CEFA',
				'lightslategray'       => '778899',
				'lightslategrey'       => '778899',
				'lightsteelblue'       => 'B0C4DE',
				'lightyellow'          => 'FFFFE0',
				'lime'                 => '00FF00',
				'limegreen'            => '32CD32',
				'linen'                => 'FAF0E6',
				'magenta'              => 'FF00FF',
				'maroon'               => '800000',
				'mediumaquamarine'     => '66CDAA',
				'mediumblue'           => '0000CD',
				'mediumorchid'         => 'BA55D3',
				'mediumpurple'         => '9370D0',
				'mediumseagreen'       => '3CB371',
				'mediumslateblue'      => '7B68EE',
				'mediumspringgreen'    => '00FA9A',
				'mediumturquoise'      => '48D1CC',
				'mediumvioletred'      => 'C71585',
				'midnightblue'         => '191970',
				'mintcream'            => 'F5FFFA',
				'mistyrose'            => 'FFE4E1',
				'moccasin'             => 'FFE4B5',
				'navajowhite'          => 'FFDEAD',
				'navy'                 => '000080',
				'oldlace'              => 'FDF5E6',
				'olive'                => '808000',
				'olivedrab'            => '6B8E23',
				'orange'               => 'FFA500',
				'orangered'            => 'FF4500',
				'orchid'               => 'DA70D6',
				'palegoldenrod'        => 'EEE8AA',
				'palegreen'            => '98FB98',
				'paleturquoise'        => 'AFEEEE',
				'palevioletred'        => 'DB7093',
				'papayawhip'           => 'FFEFD5',
				'peachpuff'            => 'FFDAB9',
				'peru'                 => 'CD853F',
				'pink'                 => 'FFC0CB',
				'plum'                 => 'DDA0DD',
				'powderblue'           => 'B0E0E6',
				'purple'               => '800080',
				'red'                  => 'FF0000',
				'rosybrown'            => 'BC8F8F',
				'royalblue'            => '4169E1',
				'saddlebrown'          => '8B4513',
				'salmon'               => 'FA8072',
				'sandybrown'           => 'F4A460',
				'seagreen'             => '2E8B57',
				'seashell'             => 'FFF5EE',
				'sienna'               => 'A0522D',
				'silver'               => 'C0C0C0',
				'skyblue'              => '87CEEB',
				'slateblue'            => '6A5ACD',
				'slategray'            => '708090',
				'slategrey'            => '708090',
				'snow'                 => 'FFFAFA',
				'springgreen'          => '00FF7F',
				'steelblue'            => '4682B4',
				'tan'                  => 'D2B48C',
				'teal'                 => '008080',
				'thistle'              => 'D8BFD8',
				'tomato'               => 'FF6347',
				'turquoise'            => '40E0D0',
				'violet'               => 'EE82EE',
				'wheat'                => 'F5DEB3',
				'white'                => 'FFFFFF',
				'whitesmoke'           => 'F5F5F5',
				'yellow'               => 'FFFF00',
				'yellowgreen'          => '9ACD32',
			);

		}

		/**
		 * Use fallback object.
		 *
		 * @access protected
		 * @since 1.2.0
		 */
		protected function from_fallback() {
			$this->color = $this->fallback;

			if ( ! $this->fallback_obj ) {
				$this->fallback_obj = self::newColor( $this->fallback );
			}
			$this->color      = $this->fallback_obj->color;
			$this->mode       = $this->fallback_obj->mode;
			$this->red        = $this->fallback_obj->red;
			$this->green      = $this->fallback_obj->green;
			$this->blue       = $this->fallback_obj->blue;
			$this->alpha      = $this->fallback_obj->alpha;
			$this->hue        = $this->fallback_obj->hue;
			$this->saturation = $this->fallback_obj->saturation;
			$this->lightness  = $this->fallback_obj->lightness;
			$this->luminance  = $this->fallback_obj->luminance;
			$this->hex        = $this->fallback_obj->hex;
		}

		/**
		 * Handle non-existing public methods.
		 *
		 * @access public
		 * @since 1.1.0
		 * @param string $name      The method name.
		 * @param mixed  $arguments The method arguments.
		 * @return mixed
		 */
		public function __call( $name, $arguments ) {
			if ( method_exists( $this, $name ) ) {
				call_user_func( array( $this, $name ), $arguments );
			} else {
				return $arguments;
			}
		}

		/**
		 * Handle non-existing public static methods.
		 *
		 * @static
		 * @access public
		 * @since 1.1.0
		 * @param string $name      The method name.
		 * @param mixed  $arguments The method arguments.
		 * @return mixed
		 */
		public static function __callStatic( $name, $arguments ) {
			if ( method_exists( __CLASS__, $name ) ) {
				call_user_func( array( __CLASS__, $name ), $arguments );
			} else {
				return $arguments;
			}
		}
	}
}
