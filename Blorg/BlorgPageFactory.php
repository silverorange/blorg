<?php

require_once 'Site/SitePageFactory.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * Page factory for Blörg pages
 *
 * This factory is responsible for loading URIs of the following forms:
 *
 * <code>
 * - /
 *   - page<i>number</i>
 *   - archive/
 *     - <i>year</i>/
 *       - <i>month</i>/
 *         - <i>post-shortname</i>/
 *           - feed/
 *             - page<i>number</i>
 *   - author/
 *     - <i>author-shortname</i>/
 *       - page<i>number</i>
 *   - feed/
 *     - page<i>number</i>
 *     - comments/
 *       - page<i>number</i>
 *   - file/<i>filename</i>
 *   - tag/
 *     - <i>tag-shortname</i>/
 *       - page<i>number</i>
 *       - feed/
 *         - page<i>number</i>
 *   - ajax/<i>proxy-service</i>
 * </code>
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
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
	public function __construct(SiteApplication $app)
	{
		parent::__construct($app);
		$this->page_class_map['Blorg'] = 'Blorg/pages';
	}

	// }}}
	// {{{ public function resolvePage()

	/**
	 * Resolves a page object from a source string
	 *
	 * @param string $source the source string for which to get the page.
	 * @param SiteLayout $layout optional. The layout to use. If not specified,
	 *                            the layout is resolved from the
	 *                            <code>$source</code>.
	 *
	 * @return SiteAbstractPage the page for the given source string.
	 */
	public function resolvePage($source, SiteLayout $layout = null)
	{
		$layout = ($layout === null) ? $this->resolveLayout($source) : $layout;

		$page_info = $this->getPageInfo($source);

		if ($page_info === null) {
			throw new SiteNotFoundException(
				sprintf('Page not found for path ‘%s’.', $source));
		}

		// create page object
		$page = $this->instantiatePage($page_info['page'], $layout,
			$page_info['arguments']);

		// decorate page
		$decorators = array_reverse($page_info['decorators']);
		foreach ($decorators as $decorator) {
			$page = $this->decorate($page, $decorator);
		}

		return $page;
	}

	// }}}
	// {{{ protected function getPageInfo()

	/**
	 * Gets page info for the passed source string
	 *
	 * @param string $source the source string for which to get the page info.
	 *
	 * @return array an array of page info. The array has the index values
	 *               'page', 'decorators' and 'arguments'. If no suitable page
	 *               is found, null is returned.
	 */
	protected function getPageInfo($source)
	{
		$info = null;

		foreach ($this->getPageMap() as $pattern => $class) {
			$regs = array();
			$pattern = str_replace('@', '\@', $pattern); // escape delimiters
			$regexp = '@'.$pattern.'@u';
			if (preg_match($regexp, $source, $regs) === 1) {

				$info = array(
					'page'       => $this->default_page_class,
					'decorators' => array(),
					'arguments'  => array(),
				);

				array_shift($regs); // discard full match string

				// get additional arguments as remaining subpatterns
				foreach ($regs as $reg) {
					// set empty regs parsed from page map expressions to null
					$reg = ($reg == '') ? null : $reg;
					$info['arguments'][] = $reg;
				}

				// get page class and/or decorators
				if (is_array($class)) {
					$page = array_pop($class);
					if ($this->isPage($page)) {
						$info['page']       = $page;
						$info['decorators'] = $class;
					} else {
						$class[]            = $page;
						$info['decorators'] = $class;
					}
				} else {
					if ($this->isPage($class)) {
						$info['page'] = $class;
					} else {
						$info['decorators'][] = $class;
					}
				}

				break;
			}
		}

		return $info;
	}

	// }}}
	// {{{ protected function getPageMap()

	/**
	 * Gets an array of page mappings used to resolve Blörg pages
	 *
	 * The page mappings are an array of the form:
	 *
	 * <code>
	 * array(
	 *     $source expression => $page_class
	 * );
	 * </code>
	 *
	 * The <code>$source_expression</code> is an regular expression using PCRE
	 * syntax sans-delimiters. The delimiter character is unspecified and should
	 * not be escaped in these expressions. The <code>$page_class</code> is the
	 * class name of the page to be resolved.
	 *
	 * Capturing sub-patterns are passed as arguments to the page constructor.
	 *
	 * For example, the following mapping array will match the source
	 * 'about/content' to the class 'ContactPage':
	 *
	 * <code>
	 * array(
	 *     '^about/contact$' => 'ContactPage',
	 * );
	 * </code>
	 *
	 * @return array the page mappings of this factory.
	 */
	protected function getPageMap()
	{
		$months = implode('|', self::$month_names);
		$post = 'archive/(\d{4})/('.$months.')/([\w-]+)';
		$page = '(?:/page(\d+))?';

		return array(
			'^(?:page(\d+))?$'                => 'BlorgFrontPage',
			'^search$'                        => 'BlorgSearchResultsPage',
			'^author$'                        => 'BlorgAuthorIndexPage',
			'^author/([\w-]+)'.$page.'$'      => 'BlorgAuthorPage',
			'^archive$'                       => 'BlorgArchivePage',
			'^archive/(\d{4})$'               => 'BlorgYearArchivePage',
			'^archive/(\d{4})/('.$months.')$' => 'BlorgMonthArchivePage',
			'^'.$post.'$'                     => 'BlorgPostPage',
			'^'.$post.'/feed'.$page.'$'       => 'BlorgPostAtomPage',
			'^feed'.$page.'$'                 => 'BlorgAtomPage',
			'^file/(.*)$'                     => 'BlorgFileLoaderPage',
			'^feed/comments'.$page.'$'        => 'BlorgCommentsAtomPage',
			'^tag$'                           => 'BlorgTagIndexPage',
			'^tag/([\w-]+)(?:/page(\d+))?$'   => 'BlorgTagPage',
			'^tag/([\w-]+)/feed'.$page.'$'    => 'BlorgTagAtomPage',
			'^ajax/(.+)$'                     => 'BlorgAjaxProxyPage',
		);
	}

	// }}}
}

?>
