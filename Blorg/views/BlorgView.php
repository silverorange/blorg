<?php

/**
 * Base class for Blörg object views
 *
 * Usage pattern is as follows:
 * 1. instantiate a view object,
 * 2. set what you want to be shown and how you want it to be shown using the
 *    show*() methods on the new view
 * 3. display one or more objects using the view by calling the display()
 *    method and passing in the object to be displayed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class BlorgView
{
	// {{{ class constants

	/**
	 * Do not show the element. All elements should support this mode.
	 */
	const SHOW_NONE    = 1;

	/**
	 * Show a shortened or summarized version of the element. Not all elements
	 * support this mode. If it is unsupported, it is treated like
	 * {@link BlorgView::SHOW_ALL}.
	 */
	const SHOW_SUMMARY = 2;

	/**
	 * Show all of the element. This is the default mode for most elements.
	 */
	const SHOW_ALL     = 3;

	// }}}
	// {{{ protected properties

	/**
	 * The application to which this view belongs
	 *
	 * @var SiteApplication
	 *
	 * @see BlorgPostView::__construct()
	 */
	protected $app;

	/**
	 * Path prefix for relatively referenced paths
	 *
	 * @var string
	 */
	protected $path_prefix = '';

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Blorg object view
	 *
	 * @param SiteApplication $app the application to which this view belongs.
	 */
	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function setPathPrefix()

	/**
	 * Sets the prefix to use for relatively referenced paths such as file
	 * download links.
	 *
	 * @param string A relative path prefix to access the web root such as
	 *               "../"
	 */
	public function setPathPrefix($path_prefix)
	{
		$this->path_prefix = $path_prefix;
	}

	// }}}
	// {{{ protected function getMode()

	/**
	 * Ensures the specified mode is a valid mode and makes it valid if
	 * invalid
	 *
	 * If an invalid mode is specified, {@link BlorgView::SHOW_NONE} is
	 * returned. Otherwise, the specified mode is returned.
	 *
	 * @param integer $mode the mode.
	 *
	 * @return integer a valid display mode.
	 */
	protected function getMode($mode)
	{
		$valid_modes = array(
			self::SHOW_ALL,
			self::SHOW_SUMMARY,
			self::SHOW_NONE,
		);

		if (!in_array($mode, $valid_modes)) {
			$mode = self::SHOW_NONE;
		}

		return $mode;
	}

	// }}}
	// {{{ protected function getLink()

	/**
	 * Ensures the specified link is valid for the link parameter of a show
	 * method, and makes the link valid if invalid
	 *
	 * If an invalid link is specified, false is returned. Otherwise the
	 * specified link is returned.
	 *
	 * @param boolean|string $link the link.
	 *
	 * @return boolean|string a valid link.
	 */
	protected function getLink($link)
	{
		if (!is_bool($link) && !is_string($link)) {
			$link = false;
		}
		return $link;
	}

	// }}}
	// {{{ protected function getPostRelativeUri()

	protected function getPostRelativeUri(BlorgPost $post)
	{
		$path = $this->app->config->blorg->path.'archive';

		$date = clone $post->publish_date;
		$date->convertTZ($this->app->default_time_zone);
		$year = $date->getYear();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];

		return sprintf('%s/%s/%s/%s',
			$path,
			$year,
			$month_name,
			$post->shortname);
	}

	// }}}
	// {{{ protected function getAuthorRelativeUri()

	protected function getAuthorRelativeUri(BlorgAuthor $author)
	{
		$path = $this->app->config->blorg->path.'author';
		return sprintf('%s/%s',
			$path,
			$author->shortname);
	}

	// }}}
}

?>
