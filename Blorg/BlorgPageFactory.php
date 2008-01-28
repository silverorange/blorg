<?php

require_once 'Site/SitePageFactory.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * Page factory for Blorg pages
 *
 * This factory is responsible for loading URLs of the forms:
 *
 * - /archive
 * - /archive/<year>
 * - /archive/<year>/<month>
 * - /archive/<year>/<month>/<post-shortname>
 * - /author
 * - /author/<author-shortname>
 *
 * @package   Blorg
 * @copyright 2008 silverorange
 */
class BlorgPageFactory extends SitePageFactory
{
	// {{{ public static properties

	public static $month_names = array(
		1  => 'january',
		2  => 'february',
		3  => 'march',
		4  => 'april',
		5  => 'may',
		6  => 'june',
		7  => 'july',
		8  => 'august',
		9  => 'september',
		10 => 'october',
		11 => 'november',
		12 => 'december',
	);

	public static $months_by_name = array(
		'january'   => 1,
		'february'  => 2,
		'march'     => 3,
		'april'     => 4,
		'may'       => 5,
		'june'      => 6,
		'july'      => 7,
		'august'    => 8,
		'september' => 9,
		'october'   => 10,
		'november'  => 11,
		'december'  => 12,
	);

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Blorg page factory
	 */
	public function __construct()
	{
		$this->class_map['Blorg'] = 'Blorg/pages';
	}

	// }}}
	// {{{ public function resolvePage()

	/**
	 * Resolves a page object from a source string
	 *
	 * The following URLs can be resolved in Blorg:
	 *
	 * - /archive
	 * - /archive/<year>
	 * - /archive/<year>/<month>
	 * - /archive/<year>/<month>/<post-shortname>
	 * - /author
	 * - /author/<author-shortname>
	 * - <nothing>
	 *
	 * @param SiteWebApplication $app the web application for which the page is
	 *                                 being resolved.
	 * @param string $source the source string for which to get the page.
	 * @param SiteLayout $layout optional, the layout to use.
	 *
	 * @return SitePage the page for the given source string.
	 */
	public function resolvePage(SiteWebApplication $app, $source,
		$layout = null)
	{
		if ($layout === null)
			$layout = $this->resolveLayout($app, $source);

		$page = null;
		if ($source == '') {
			// front page
			$page = $this->instantiatePage($app, 'BlorgFrontPage',
				array($app, $layout));
		} else {
			foreach ($this->getPageMap() as $pattern => $class) {
				$params = array();
				// escape delimiters
				$pattern = str_replace('@', '\@', $pattern);
				$regexp = '@'.$pattern.'@u';
				if (preg_match($regexp, $source, $params) === 1) {

					// discard full match string
					array_shift($params);

					// add layout as second param
					array_unshift($params, $layout);

					// add app as first param
					array_unshift($params, $app);

					$page = $this->instantiatePage($app, $class, $params);

					break;
				}
			}
		}

		if ($page === null) {
			throw new SiteNotFoundException(
				sprintf('Page not found for path ‘%s’.', $source));
		}

		return $page;
	}

	// }}}
	// {{{ protected function instantiatePage()

	/**
	 * Instantiates a Blorg page
	 *
	 * @param SiteWebApplication $app the web application for which the page is
	 *                                 being resolved.
	 * @param string $class the name of the page class to resolve. This must be
	 *                       either {@link SitePage} or a subclass of
	 *                       SitePage.
	 * @param array $params an array of parameters to pass the the constructor
	 *                       of the page object.
	 *
	 * @return SitePage the instantiated page object.
	 *
	 * @throws SiteClassNotFoundException if the given class could not be
	 *                                     resolved or if the given class is
	 *                                     neither SitePage nor a subclass of
	 *                                     SitePage.
	 * @throws SiteNotFoundException if no file could be resolved for the given
	 *                                class and the given class is undefined.
	 */
	public function instantiatePage(SiteWebApplication $app, $class, $params)
	{
		if (!class_exists($class)) {
			$class_file = "{$this->page_class_path}/{$class}.php";

			if (!file_exists($class_file)) {
				$class_file = null;

				// look for class file in class map
				foreach ($this->class_map as $package_prefix => $path) {
					if (strncmp($class, $package_prefix, strlen($package_prefix)) == 0) {
						$class_file = "{$path}/{$class}.php";
						break;
					}
				}
			}

			if ($class_file === null) {
				throw new SiteNotFoundException(sprintf(
					'No file found for page class ‘%s’.', $class));
			}

			require_once $class_file;
		}

		if (!class_exists($class)) {
			throw new SiteClassNotFoundException(sprintf(
				'No page class definition found for ‘%s’.', $class), 0, $class);
		}

		if ($class != 'SitePage' && !is_subclass_of($class, 'SitePage')) {
			throw new SiteClassNotFoundException(sprintf(
				'The provided page class ‘%s’ is not a SitePage.', $class),
				0, $class);
		}

		$reflector = new ReflectionClass($class);
		$page = $reflector->newInstanceArgs($params);

		return $page;
	}

	// }}}
	// {{{ protected function getPageMap()

	/**
	 * Gets an array of page mappings used to resolve Blorg pages
	 *
	 * The page mappings are an array of the form:
	 *
	 *   source expression => page class
	 *
	 * The <i>source expression</i> is an regular expression using PREG syntax
	 * sans-delimiters. The <i>page class</i> is the class name of the page to
	 * be resolved.
	 *
	 * For example, the following mapping array will match the source
	 * 'about/content' to the class 'ContactPage':
	 *
	 * <code>
	 * array('^(about/contact)$' => 'ContactPage');
	 * </code>
	 *
	 * Mappings for the following URL types are defined in Blorg:
	 *
	 * - /archive
	 * - /archive/<year>
	 * - /archive/<year>/<month>
	 * - /archive/<year>/<month>/<post-shortname>
	 * - /author
	 * - /author/<author-shortname>
	 *
	 * @return array the page mappings of this factory.
	 */
	protected function getPageMap()
	{
		$months = implode('|', self::$month_names);

		return array(
			'^author$'                                 => 'BlorgAuthorIndexPage',
			'^author/([\w-]+)$'                        => 'BlorgAuthorPage',
			'^archive$'                                => 'BlorgArchivePage',
			'^archive/(\d{4})$'                        => 'BlorgYearArchivePage',
			'^archive/(\d{4})/('.$months.')$'          => 'BlorgMonthArchivePage',
			'^archive/(\d{4})/('.$months.')/([\w-]+)$' => 'BlorgPostPage',
		);
	}

	// }}}
}

?>
