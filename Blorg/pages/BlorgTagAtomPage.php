<?php

require_once 'Site/pages/SitePage.php';
require_once 'Blorg/dataobjects/BlorgTag.php';
require_once 'Blorg/BlorgPostLoader.php';
require_once 'Blorg/pages/BlorgAtomPage.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';
require_once 'XML/Atom/Link.php';

/**
 * Displays an Atom feed of all recent posts in reverse chronological order for
 * a specified tag
 *
 * The number of posts is always at least
 * {@link BlorgAtomPage::$min_entries}, but if a recently published set of
 * posts (within the time of {@link BlorgAtomPage::$recent_period}) exceeds
 * <code>$min_entries</code>, up to, {@link BlorgAtomPage::$max_entries}
 * posts will be displayed. This makes it easier to ensure that a subscriber
 * won't miss any posts, while limiting server load for the feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagAtomPage extends BlorgAtomPage
{
	// {{{ protected properties

	/**
	 * @var BlorgTag
	 */
	protected $tag;

	/**
	 * @var string
	 */
	protected $shortname;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		$shortname, $page_number = 1)
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');
		$this->shortname = $shortname;
		parent::__construct($app, $layout, $page_number);
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts()
	{
		$class_name = SwatDBClassMap::get('BlorgTag');
		$tag = new $class_name();
		$tag->setDatabase($this->app->db);
		if (!$tag->loadByShortname($this->shortname,
				$this->app->getInstance())) {
					throw new SiteNotFoundException('Page not found.');
		}

		$this->tag = $tag;

		parent::initPosts();

		$this->post_loader->setWhereClause(sprintf('enabled = %s and
			id in (select post from BlorgPostTagBinding where tag = %s)',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($tag->id, 'integer')));
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildAtomFeed();

		$this->layout->startCapture('content');
		$this->displayAtomFeed();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildAtomFeed()

	protected function buildAtomFeed()
	{
		$site_base_href  = $this->app->getBaseHref();
		$blorg_base_href = $site_base_href.$this->app->config->blorg->path;
		$feed_base_href  = $site_base_href.$this->source;
		$tag_href = $blorg_base_href.'tag/'.$this->tag->shortname;

		$this->feed = new XML_Atom_Feed($blorg_base_href,
			sprintf(Blorg::_('%s - %s'),
				$this->app->config->site->title,
				$this->tag->title));

		$this->feed->setSubTitle(sprintf(
			Blorg::_('Posts Tagged: %s'),
			$this->tag->title));

		$this->feed->addLink($site_base_href.$this->source, 'self',
			'application/atom+xml');

		$this->feed->addLink($tag_href, 'alternate', 'text/html');
		$this->feed->setGenerator('Blörg');
		$this->feed->setBase($site_base_href);

		$this->buildFeedIconLogo();

		$threshold = new SwatDate();
		$threshold->toUTC();
		$threshold->subtractSeconds($this->recent_period);

		$posts = $this->post_loader->getPosts();
		$count = 0;

		foreach ($posts as $post) {
			if ($count > $this->max_entries ||
				($count > $this->min_entries) &&
					$post->publish_date->before($threshold))
				break;

			$count++;
		}

		$this->buildAtomPagination($count, $feed_base_href);

		if ($this->page > 1) {
			$this->post_loader->setRange($this->min_entries,
				$count + (($this->page - 2) * $this->min_entries));

			$posts = $this->post_loader->getPosts();
			$count = $this->min_entries;
		}

		$this->buildEntries($posts, $count);
	}

	// }}}
}

?>
