<?php

require_once 'Site/pages/SitePage.php';
require_once 'XML/Atom/Feed.php';

/**
 * Abstract class used to help build atom feeds.
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

	// }}}

	// init phase
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		array $arguments = array())
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');
		parent::__construct($app, $layout, $arguments);
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
		$page = $this->getArgument('page');
		$type = 'application/atom+xml';
		$last = intval(ceil($this->getTotalCount() / $this->getPageSize()));

		$feed->addLink($this->getFeedBaseHref(), 'first', $type);
		$feed->addLink($this->getFeedBaseHref().'/page'.$last, 'last', $type);

		if ($page > 1) {
			$feed->addLink($this->getFeedBaseHref().'/page'.($page - 1),
				'previous', $type);
		}

		if ($page < $last) {
			$feed->addLink($this->getFeedBaseHref().'/page'.($page + 1),
				'next', $type);
		}
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
	// {{{ abstract protected function getBlorgBaseHref()

	abstract protected function getBlorgBaseHref();

	// }}}
	// {{{ abstract protected function getFeedBaseHref()

	abstract protected function getFeedBaseHref();

	// }}}
	// {{{ abstract protected function getTotalCount()

	abstract protected function getTotalCount();

	// }}}
	// {{{ protected function getPageSize()

	protected function getPageSize()
	{
		return 20;
	}

	// }}}
}
