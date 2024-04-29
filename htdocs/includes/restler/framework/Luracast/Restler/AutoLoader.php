<?php
namespace Luracast\Restler {

	/**
	 * Class that implements spl_autoload facilities and multiple
	 * conventions support.
	 * Supports composer libraries and 100% PSR-0 compliant.
	 * In addition we enable namespace prefixing and class aliases.
	 *
	 * @category   Framework
	 * @package    Restler
	 * @subpackage Helper
	 * @author     Nick Lombard <github@jigsoft.co.za>
	 * @copyright  2012 Luracast
	 *
	 */
	class AutoLoader
	{
		protected static $instance, // the singleton instance reference
					 $perfectLoaders, // used to keep the ideal list of loaders
					 $rogueLoaders = array(), // other auto loaders now unregistered
					 $classMap = array(), // the class to include file mapping
					 $aliases = array( // aliases and prefixes instead of null list aliases
						 'Luracast\\Restler' => null,
						 'Luracast\\Restler\\Format' => null,
						 'Luracast\\Restler\\Data' => null,
						 'Luracast\\Restler\\Filter' => null,
					 );

		/**
		 * Singleton instance facility.
		 *
		 * @static
		 * @return AutoLoader the current instance or new instance if none exists.
		 */
		public static function instance()
		{
			static::$instance = static::$instance ?: new static();
			return static::thereCanBeOnlyOne();
		}

		/**
		 * Helper function to add a path to the include path.
		 * AutoLoader uses the include path to discover classes.
		 *
		 * @static
		 *
		 * @param $path string absolute or relative path.
		 *
		 * @return bool false if the path cannot be resolved
		 *              or the resolved absolute path.
		 */
		public static function addPath($path)
		{
			if (false === $path = stream_resolve_include_path($path))
			return false;
			else set_include_path($path.PATH_SEPARATOR.get_include_path());
			return $path;
		}

		/**
		 * Other autoLoaders interfere and cause duplicate class loading.
		 * AutoLoader is capable enough to handle all standards so no need
		 * for others stumbling about.
		 *
		 * @return callable the one true auto loader.
		 */
		public static function thereCanBeOnlyOne()
		{
			if (static::$perfectLoaders === spl_autoload_functions())
			return static::$instance;

			if (false !== $loaders = spl_autoload_functions())
			if (0 < $count = count($loaders))
				for ($i = 0, static::$rogueLoaders += $loaders;
					 $i < $count && false != ($loader = $loaders[$i]);
					 $i++)
					if ($loader !== static::$perfectLoaders[0])
						spl_autoload_unregister($loader);

			return static::$instance;
		}

		/**
		 * Seen this before cache handler.
		 * Facilitates both lookup and persist operations as well as convenience,
		 * load complete map functionality. The key can only be given a non falsy
		 * value once, this will be truthy for life.
		 *
		 * @param $key   mixed class name considered or a collection of
		 *               classMap entries
		 * @param $value mixed optional not required when doing a query on
		 *               key. Default is false we haven't seen this
		 *               class. Most of the time it will be the filename
		 *               for include and is set to true if we are unable
		 *               to load this class iow true == it does not exist.
		 *               value may also be a callable auto loader function.
		 *
		 * @return mixed The known value for the key or false if key has no value
		 */
		public static function seen($key, $value = false)
		{
			if (is_array($key)) {
				static::$classMap = $key + static::$classMap;
				return false;
			}

			if (empty(static::$classMap[$key]))
			static::$classMap[$key] = $value;

			if (is_string($alias = static::$classMap[$key]))
			if (isset(static::$classMap[$alias]))
				return static::$classMap[$alias];

			return static::$classMap[$key];
		}

		/**
		 * Protected constructor to enforce singleton pattern.
		 * Populate a default include path.
		 * All possible includes cant possibly be catered for and if you
		 * require another path then simply add it calling set_include_path.
		 */
		protected function __construct()
		{
			static::$perfectLoaders = array($this);

			if (false === static::seen('__include_path')) {
				$paths = explode(PATH_SEPARATOR, get_include_path());
				$slash = DIRECTORY_SEPARATOR;
				$dir = dirname(__DIR__);
				$source_dir = dirname($dir);
				$dir = dirname($source_dir);

				foreach (array(
					array($source_dir),
					array($dir, '..', '..', 'composer'),
					array($dir, 'vendor', 'composer'),
					array($dir, '..', '..', '..', 'php'),
					array($dir, 'vendor', 'php'))
				as $includePath)
				if (false !== $path = stream_resolve_include_path(
						implode($slash, $includePath)
					))
				if ('composer' == end($includePath) &&
						false !== $classmapPath = stream_resolve_include_path(
							"$path{$slash}autoload_classmap.php"
						)
					) {
					static::seen(static::loadFile(
						$classmapPath
					));
					$paths = array_merge(
						$paths,
						array_values(static::loadFile(
							"$path{$slash}autoload_namespaces.php"
						))
					);
				} else $paths[] = $path;

				$paths = array_filter(array_map(
				function ($path) {
					if (false == $realPath = @realpath($path))
						return null;
					return $realPath . DIRECTORY_SEPARATOR;
				},
				$paths
				));
				natsort($paths);
				static::seen(
					'__include_path',
					implode(PATH_SEPARATOR, array_unique($paths))
				);
			}

			set_include_path(static::seen('__include_path'));
		}

		/**
		 * Attempt to include the path location.
		 * Called from a static context which will not expose the AutoLoader
		 * instance itself.
		 *
		 * @param $path string location of php file on the include path
		 *
		 * @return bool|mixed returns reference obtained from the include or false
		 */
		private static function loadFile($path)
		{
			return \Luracast_Restler_autoloaderInclude($path);
		}

		/**
		 * Attempt to load class with namespace prefixes.
		 *
		 * @param $className string class name
		 *
		 * @return bool|mixed reference to discovered include or false
		 */
		private function loadPrefixes($className)
		{
			$currentClass = $className;
			if (false !== $pos = strrpos($className, '\\'))
			$className = substr($className, $pos);
			else $className = "\\$className";

			for (
			$i = 0,
				$file = false,
				$count = count(static::$aliases),
				$prefixes = array_keys(static::$aliases);
			$i < $count
				&& false === $file
				&& false === $file = $this->discover(
					$variant = $prefixes[$i++].$className,
					$currentClass
				);
			$file = $this->loadAliases($variant)
			);

			return $file;
		}

		/**
		 * Attempt to load configured aliases based on namespace part of class name.
		 *
		 * @param $className string fully qualified class name.
		 *
		 * @return bool|mixed reference to discovered include or false
		 */
		private function loadAliases($className)
		{
			$file = false;
			if (preg_match('/(.+)(\\\\\w+$)/U', $className, $parts))
			for (
				$i = 0,
				$aliases = isset(static::$aliases[$parts[1]])
					? static::$aliases[$parts[1]] : array(),
				$count = count($aliases);
				$i < $count && false === $file;
				$file = $this->discover(
					"{$aliases[$i++]}$parts[2]",
					$className
				)
			) ;

			return $file;
		}

		/**
		 * Load from rogueLoaders as last resort.
		 * It may happen that a custom auto loader may load classes in a unique way,
		 * these classes cannot be seen otherwise nor should we attempt to cover every
		 * possible deviation. If we still can't find a class, as a last resort, we will
		 * run through the list of rogue loaders and verify if we succeeded.
		 *
		 * @param      $className string className that can't be found
		 * @param null $loader callable loader optional when the loader is known
		 *
		 * @return bool false unless className now exists
		 */
		private function loadLastResort($className, $loader = null)
		{
			$loaders = array_unique(static::$rogueLoaders, SORT_REGULAR);
			if (isset($loader)) {
				if (false === array_search($loader, $loaders))
				static::$rogueLoaders[] = $loader;
				return $this->loadThisLoader($className, $loader);
			}
			foreach ($loaders as $loader)
				if (false !== $file = $this->loadThisLoader($className, $loader))
					return $file;

			return false;
		}

		/**
		 * Helper for loadLastResort.
		 * Use loader with $className and see if className exists.
		 *
		 * @param $className string   name of a class to load
		 * @param $loader    callable autoLoader method
		 *
		 * @return bool false unless className exists
		 */
		private function loadThisLoader($className, $loader)
		{
			if (is_array($loader)
				&& is_callable($loader)) {
					$b = new $loader[0];
					//avoid PHP Fatal error:  Uncaught Error: Access to undeclared static property: Composer\\Autoload\\ClassLoader::$loader
					//in case of multiple autoloader systems
					if (property_exists($b, $loader[1])) {
						if (false !== $file = $b::$loader[1]($className)
							&& $this->exists($className, $b::$loader[1])) {
								return $file;
							}
					}
				} elseif (is_callable($loader)
					&& false !== $file = $loader($className)
					&& $this->exists($className, $loader)) {
						return $file;
				}
				return false;

			/* other code tested to reduce autoload conflict
			$s = '';
			if (is_array($loader)
			&& is_callable($loader)) {
				// @CHANGE DOL avoid autoload conflict
				if (!preg_match('/LuraCast/', get_class($loader[0]))) {
					return false;
				}
				$b = new $loader[0];
				// @CHANGE DOL avoid PHP Fatal error:  Uncaught Error: Access to undeclared static property: Composer\\Autoload\\ClassLoader::$loader
				//in case of multiple autoloader systems
				if (property_exists($b, $loader[1])) {
					if (false !== $file = $b::$loader[1]($className)
						&& $this->exists($className, $b::$loader[1])) {
							return $file;
						}
				}
			} elseif (is_callable($loader, false, $s)) {
				// @CHANGE DOL avoid PHP infinite loop (detected when xdebug is on)
				if ($s == 'Luracast\Restler\AutoLoader::__invoke') {
					return false;
				}
				if (false !== ($file = $loader($className)) && $this->exists($className, $loader)) {
					return $file;
				}
			}
			return false;
			*/
		}

		/**
		 * Create an alias for class.
		 *
		 * @param $className    string the name of the alias class
		 * @param $currentClass string the current class this alias references
		 * @return void
		 */
		private function alias($className, $currentClass)
		{
			if ($className == 'Luracast\Restler\string') return;
			if ($className == 'Luracast\Restler\mixed') return;
			if ($className != $currentClass
			&& false !== strpos($className, $currentClass))
				if (!class_exists($currentClass, false)
					&& class_alias($className, $currentClass))
						static::seen($currentClass, $className);
		}

		/**
		 * Discovery process.
		 *
		 * @param $className    string class name to discover
		 * @param $currentClass string optional name of current class when
		 *                      looking up an alias
		 *
		 * @return bool|mixed resolved include reference or false
		 */
		private function discover($className, $currentClass = null)
		{
			$currentClass = $currentClass ?: $className;

			/** The short version we've done this before and found it in cache */
			if (false !== $file = static::seen($className)) {
				if (!$this->exists($className))
				if (is_callable($file))
					$file = $this->loadLastResort($className, $file);
				elseif ($file = stream_resolve_include_path($file))
					$file = static::loadFile($file);

				$this->alias($className, $currentClass);
				return $file;
			}

			/** We did not find it in cache, lets look for it shall we */

			/** replace \ with / and _ in CLASS NAME with / = PSR-0 in 3 lines */
			$file = preg_replace("/\\\|_(?=\w+$)/", DIRECTORY_SEPARATOR, $className);
			if (false === $file = stream_resolve_include_path("$file.php"))
			return false;

			/** have we loaded this file before could this be an alias */
			if (in_array($file, get_included_files())) {
				if (false !== $sameFile = array_search($file, static::$classMap))
				if (!$this->exists($className, $file))
					if (false !== strpos($sameFile, $className))
						$this->alias($sameFile, $className);

				return $file;
			}

			$state = array_merge(get_declared_classes(), get_declared_interfaces());

			if (false !== $result = static::loadFile($file)) {
				if ($this->exists($className, $file))
				$this->alias($className, $currentClass);
				elseif (false != $diff = array_diff(
				array_merge(get_declared_classes(), get_declared_interfaces()), $state))
				foreach ($diff as $autoLoaded)
					if ($this->exists($autoLoaded, $file))
						if (false !== strpos($autoLoaded, $className))
							$this->alias($autoLoaded, $className);

				if (!$this->exists($currentClass))
				$result = false;
			}

			return $result;
		}

		/**
		 * Checks whether supplied string exists in a loaded class or interface.
		 * As a convenience the supplied $mapping can be the value for seen.
		 *
		 * @param $className string The class or interface to verify
		 * @param $mapping   string (optional) value for map/seen if found to exist
		 *
		 * @return bool whether the class/interface exists without calling auto loader
		 */
		private function exists($className, $mapping = null)
		{
			if (class_exists($className, false)
			|| interface_exists($className, false))
			if (isset($mapping))
				return static::seen($className, $mapping);
			else return true;
			return false;
		}

		/**
		 * Auto loader callback through __invoke object as function.
		 *
		 * @param $className string class/interface name to auto load
		 *
		 * @return mixed|null the reference from the include or null
		 */
		public function __invoke($className)
		{
			if (empty($className))
			return false;

			if (false !== $includeReference = $this->discover($className))
			return $includeReference;

			//static::thereCanBeOnlyOne();

			if (false !== $includeReference = $this->loadAliases($className))
			return $includeReference;

			if (false !== $includeReference = $this->loadPrefixes($className))
			return $includeReference;

			if (false !== $includeReference = $this->loadLastResort($className))
				return $includeReference;

			static::seen($className, true);
			return null;
		}
	}
}

namespace {
	/**
	 * Include function in the root namespace to include files optimized
	 * for the global context.
	 *
	 * @param $path string path of php file to include into the global context.
	 *
	 * @return mixed|bool false if the file could not be included.
	 */
	function Luracast_Restler_autoloaderInclude($path)
	{
		return include $path;
	}
}
