<?php

require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Site/pages/SitePage.php';
require_once 'Blorg/BlorgPostLoader.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';
require_once 'XML/Atom/Link.php';

/**
 * Displays an Atom feed of all recent posts in reverse chronological order
 *
 * The number of posts is always at least {@link BlorgAtomPage::$min_entries},
 * but if a recently published set of posts (within the time of
 * {@link BlorgAtomPage::$recent_period}) exceeds <code>$min_entries</code>,
 * up to, {@link BlorgAtomPage::$max_entries} posts will be displayed. This
 * makes it easier to ensure that a subscriber won't miss any posts, while
 * limiting server load for the feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAtomPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var BlorgPostWrapper
	 */
	protected $posts;

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

	/**
	 * @var BlorgPostLoader
	 */
	protected $post_loader;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		$page_number = 1)
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');

		parent::__construct($app, $layout);

		$this->page = $page_number;
		$this->initPosts();
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts()
	{
		$this->post_loader = new BlorgPostLoader($this->app->db,
			$this->app->getInstance());

		$this->post_loader->addSelectField('title');
		$this->post_loader->addSelectField('bodytext');
		$this->post_loader->addSelectField('extended_bodytext');
		$this->post_loader->addSelectField('shortname');
		$this->post_loader->addSelectField('publish_date');
		$this->post_loader->addSelectField('author');
		$this->post_loader->addSelectField('comment_status');
		$this->post_loader->addSelectField('visible_comment_count');

		$this->post_loader->setLoadFiles(true);
		$this->post_loader->setLoadTags(true);

		$this->post_loader->setWhereClause(sprintf('enabled = %s',
			$this->app->db->quote(true, 'boolean')));

		$this->post_loader->setOrderByClause('publish_date desc');
		$this->post_loader->setRange(new SwatDBRange($this->max_entries));
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

		$this->feed = new XML_Atom_Feed($blorg_base_href,
			$this->app->config->site->title);

		$this->feed->setSubTitle(Blorg::_('Recent Posts'));

		$this->feed->addLink($site_base_href.$this->source, 'self',
			'application/atom+xml');

		$this->feed->addLink($blorg_base_href, 'alternate', 'text/html');
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
	// {{{ protected function buildAtomPagination()

	protected function buildAtomPagination($first_page_size, $base_href)
	{
		// Feed paging. See IETF RFC 5005.
		$total_posts = $this->post_loader->getPostCount();
		$this->feed->addLink($base_href, 'first', 'application/atom+xml');

		$last = (ceil(($total_posts - $first_page_size) / $this->min_entries)
			+ 1);

		if ($this->page > $last)
			throw new SiteNotFoundException(Blorg::_('Page Not Found'));

		$this->feed->addLink($base_href.'/page'.$last,
			'last', 'application/atom+xml');

		if ($this->page > 1) {
			$previous = '/page'.($this->page - 1);
			$this->feed->addLink($base_href.$previous,
				'previous', 'application/atom+xml');
		}

		if ($this->page != $last) {
			$next = '/page'.($this->page + 1);
			$this->feed->addLink($base_href.$next,
				'next', 'application/atom+xml');
		}
	}

	// }}}
	// {{{ protected function buildEntries()

	protected function buildEntries(BlorgPostWrapper $posts, $limit)
	{
		$count = 0;
		foreach ($posts as $post) {
			if ($count < $limit)
				$this->addPost($post);

			$count++;
		}
	}

	// }}}
	// {{{ protected function buildFeedIconLogo()

	protected function buildFeedIconLogo()
	{
		$favicon_file = $this->app->theme->getFaviconFile();

		if ($favicon_file !== null)
			$this->feed->setIcon($this->app->getBaseHref().$favicon_file);

		if ($this->app->config->blorg->feed_logo != '') {
			$class = SwatDBClassMap::get('BlorgFile');
			$blorg_file = new $class();
			$blorg_file->setDatabase($this->app->db);
			$blorg_file->load(intval($this->app->config->blorg->feed_logo));
			$this->feed->setLogo($this->app->getBaseHref().
				$blorg_file->getRelativeUri());
		}
	}

	// }}}
	// {{{ protected function displayAtomFeed()

	protected function displayAtomFeed()
	{
		echo $this->feed;
	}

	// }}}
	// {{{ protected function addPost()

	protected function addPost(BlorgPost $post)
	{
		$site_base_href  = $this->app->getBaseHref();
		$blorg_base_href = $site_base_href.$this->app->config->blorg->path;
		$path = $blorg_base_href.'archive';

		$date = clone $post->publish_date;
		$date->convertTZ($this->app->default_time_zone);
		$year = $date->getYear();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];

		$post_uri = sprintf('%s/%s/%s/%s',
			$path,
			$year,
			$month_name,
			$post->shortname);

		$entry = new XML_Atom_Entry($post_uri, $post->getTitle(),
			$post->publish_date);

		if ($post->extended_bodytext != '') {
			$full_bodytext = $post->bodytext.$post->extended_bodytext;
			$entry->setSummary($post->bodytext, 'html');
			$entry->setContent($full_bodytext, 'html');
		} else {
			$entry->setContent($post->bodytext, 'html');
		}

		foreach ($post->getTags() as $tag) {
			$entry->addCategory($tag->shortname, $blorg_base_href,
				$tag->title);
		}

		$entry->addLink($post_uri, 'alternate', 'text/html');

		foreach ($post->getVisibleFiles() as $file) {
			$link = new XML_Atom_Link(
				$site_base_href.$file->getRelativeUri(
					$this->app->config->blorg->path),
				'enclosure',
				$file->mime_type);

			$link->setTitle($file->getDescription());
			$link->setLength($file->filesize);
			$entry->addLink($link);
		}

		if ($post->author->visible) {
			$author_uri = $blorg_base_href.'author/'.
				$post->author->shortname;
		} else {
			$author_uri = '';
		}

		$entry->addAuthor($post->author->name, $author_uri,
			$post->author->email);

		$visible_comment_count = $post->getVisibleCommentCount();
		if ($post->comment_status == BlorgPost::COMMENT_STATUS_OPEN ||
			$post->comment_status == BlorgPost::COMMENT_STATUS_MODERATED ||
			($post->comment_status == BlorgPost::COMMENT_STATUS_LOCKED &&
			$visible_comment_count > 0)) {
			$entry->addLink($post_uri.'#comments', 'comments', 'text/html');
		}

		$this->feed->addEntry($entry);
	}

	// }}}
}

?>
