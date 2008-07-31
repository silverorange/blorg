<?php

require_once 'Site/pages/SitePage.php';
require_once 'XML/Atom/Feed.php';

/**
 * Abstract class used to help build atom feeds.
 *
 * The number of posts is always at least
 * {@link BlorgAbstractAtomPage::$min_entries}, but if a recently published set
 * of posts (within the time of {@link BlorgAbstractAtomPage::$recent_period})
 * exceeds <code>$min_entries</code>, up to
 * {@link BlorgAbstractAtomPage::$max_entries} posts will be displayed. This
 * makes it easier to ensure that a subscriber won't miss any posts, while
 * limiting server load for the feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class BlorgAbstractAtomPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var XML_Atom_Feed
	 */
	protected $feed;

	/**
	 * The minimum number of entries to display
	 *
	 * @var integer
	 */
	protected $min_entries = 20;

	/**
	 * The maximum number of entries to display
	 *
	 * @var integer
	 */
	protected $max_entries = 100;

	/**
	 * Period for recently added posts (in seconds)
	 *
	 * Default value is two days.
	 *
	 * @var interger
	 */
	protected $recent_period = 172800;

	/**
	 * @var integer
	 */
	protected $page;

	// }}}

	// init phase
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		array $arguments = array())
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');
		parent::__construct($app, $layout, $arguments);
		$this->page = $this->getArgument('page') == 0 ? 1 :
			$this->getArgument('page');

		$this->initEntries();
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'page' => array(0, 1),
		);
	}

	// }}}
	// {{{ abstract protected function initEntries()

	abstract protected function initEntries();

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildFeed();

		$this->layout->startCapture('content');
		echo $this->feed;
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildFeed()

	protected function buildFeed()
	{
		$feed = new XML_Atom_Feed($this->getBlorgBaseHref(),
			$this->app->config->site->title);

		$this->buildContent($feed);
		$this->feed = $feed;
	}

	// }}}
	// {{{ protected function buildContent()

	protected function buildContent(XML_Atom_Feed $feed)
	{
		$this->buildHeader($feed);
		$this->buildEntries($feed);
		$this->buildLogo($feed);
		$this->buildIcon($feed);
		$this->buildPagination($feed);
	}

	// }}}
	// {{{ protected function buildPagination()

	protected function buildPagination(XML_Atom_Feed $feed)
	{
		$type = 'application/atom+xml';
		$last = ceil(($this->getTotalCount() - $this->getFrontPageCount()) /
			$this->min_entries) + 1;

		$feed->addLink($this->getFeedBaseHref(), 'first', $type);
		$feed->addLink($this->getFeedBaseHref().'/page'.$last, 'last', $type);

		if ($this->page > 1)
			$feed->addLink($this->getFeedBaseHref().'/page'.($this->page - 1),
				'previous', $type);

		if ($this->page != $last)
			$feed->addLink($this->getFeedBaseHref().'/page'.($this->page + 1),
				'next', $type);
	}

	// }}}
	// {{{ protected function buildIcon()

	protected function buildIcon(XML_Atom_Feed $feed)
	{
		if ($this->app->hasModule('SiteThemeModule')) {
			$favicon_file = $this->app->theme->getFaviconFile();

			if ($favicon_file !== null)
				$feed->setIcon($this->app->getBaseHref().$favicon_file);
		}
	}

	// }}}
	// {{{ protected function buildLogo()

	protected function buildLogo(XML_Atom_Feed $feed)
	{
		if ($this->app->config->blorg->feed_logo != '') {
			$class = SwatDBClassMap::get('BlorgFile');
			$blorg_file = new $class();
			$blorg_file->setDatabase($this->app->db);
			$blorg_file->load(intval($this->app->config->blorg->feed_logo));
			$feed->setLogo($this->app->getBaseHref().
				$blorg_file->getRelativeUri());
		}
	}

	// }}}
	// {{{ protected function buildHeader()

	protected function buildHeader(XML_Atom_Feed $feed)
	{
		$feed->setGenerator('Blörg');
		$feed->setBase($this->app->getBaseHref());
		$feed->addLink($this->app->getBaseHref().$this->source, 'self',
			'application/atom+xml');
	}

	// }}}
	// {{{ abstract protected function buildEntries()

	abstract protected function buildEntries(XML_Atom_Feed $feed);

	// }}}

	// helper methods
	// {{{ protected function isEntryRecent()

	protected function isEntryRecent(SwatDate $entry_date)
	{
		$threshold = new SwatDate();
		$threshold->toUTC();
		$threshold->subtractSeconds($this->recent_period);

		return ($entry_date->after($threshold));
	}

	// }}}
	// {{{ abstract protected function getBlorgBaseHref()

	abstract protected function getBlorgBaseHref();

	// }}}
	// {{{ abstract protected function getFeedBaseHref()

	abstract protected function getFeedBaseHref();

	// }}}
	// {{{ abstract protected function getTotalCount()

	abstract protected function getTotalCount();

	// }}}
	// {{{ abstract protected function getFrontPageCount()

	abstract protected function getFrontPageCount();

	// }}}
}
