<?php

require_once 'Swat/exceptions/SwatClassNotFoundException.php';

class BlorgViewFactory extends SwatObject
{
	private static $view_class_names_by_type = array();

	/**
	 * Paths to search for class-definition files
	 *
	 * @var array
	 */
	private static $search_paths = array('.');

	public static function build($type, SiteApplication $app)
	{
		$type = strval($type);
		if (!array_key_exists($type, self::$view_class_names_by_type)) {
			throw new Exception(sprintf(
				'No views are registered with the type "%s".',
				$type));
		}

		$view_class_name = self::$view_class_names_by_type[$type];
		self::loadViewClass($view_class_name);

		$view = new $view_class_name($app);
		return $view;
	}

	public static function registerView($type, $view_class_name)
	{
		$type = strval($type);
		self::$view_class_names_by_type[$type] = $view_class_name;
	}

	// {{{ public static function addPath()

	/**
	 * Adds a search path for class-definition files
	 *
	 * When an undefined class is resolved, the class map attempts to find
	 * and require a class-definition file for the class.
	 *
	 * All search paths are relative to the PHP include path. The empty search
	 * path ('.') is included by default.
	 *
	 * @param string $search_path the path to search for class-definition files.
	 *
	 * @see SwatDBClassMap::removePath()
	 */
	public static function addPath($search_path)
	{
		if (!in_array($search_path, self::$search_paths, true)) {
			// add path to front of array since it is more likely we will find
			// class-definitions in manually added search paths
			array_unshift(self::$search_paths, $search_path);
		}
	}

	// }}}

	private static function loadViewClass($view_class_name)
	{
		// try to load class definition for $view_class_name
		if (!class_exists($view_class_name) &&
			count(self::$search_paths) > 0) {
			$include_paths = explode(':', get_include_path());
			foreach (self::$search_paths as $search_path) {
				// check if search path is relative
				if ($search_path[0] == '/') {
					$filename = sprintf('%s/%s.php',
						$search_path, $view_class_name);

					if (file_exists($filename)) {
						require_once $filename;
						break;
					}
				} else {
					foreach ($include_paths as $include_path) {
						$filename = sprintf('%s/%s/%s.php',
							$include_path, $search_path, $view_class_name);

						if (file_exists($filename)) {
							require_once $filename;
							break 2;
						}
					}
				}
			}
		}

		if (!class_exists($view_class_name)) {
			throw new SwatClassNotFoundException(sprintf(
				'View class "%s" does not exist and could not be found in '.
				'the search path.',
				$view_class_name), 0, $view_class_name);
		}
	}

	private function __construct()
	{
	}
}

?>
