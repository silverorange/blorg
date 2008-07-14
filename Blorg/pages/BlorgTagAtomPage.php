<?php

require_once 'Site/pages/SitePage.php';
require_once 'Blorg/dataobjects/BlorgTag.php';
require_once 'Blorg/BlorgPostLoader.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';
require_once 'XML/Atom/Link.php';

/**
 * Displays an Atom feed of all recent posts in reverse chronological order for
 * a specified tag
 *
 * The number of posts is always at least
 * {@link BlorgTagAtomPage::$min_entries}, but if a recently published set of
 * posts (within the time of {@link BlorgTagAtomPage::$recent_period}) exceeds
 * <code>$min_entries</code>, up to, {@link BlorgTagAtomPage::$max_entries}
 * posts will be displayed. This makes it easier to ensure that a subscriber
 * won't miss any posts, while limiting server load for the feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagAtomPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var BlorgPostLoader
	 */
	protected $post_loader;

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
	 * @var BlorgTag
	 */
	protected $tag;

	/**
	 * @var integer
	 */
	protected $page;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		$shortname, $page_number = 1)
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');
		parent::__construct($app, $layout);
		$this->page = $page_number;
		$this->initPosts($shortname);
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts($shortname)
	{
		$class_name = SwatDBClassMap::get('BlorgTag');
		$tag = new $class_name();
		$tag->setDatabase($this->app->db);
		if (!$tag->loadByShortname($shortname, $this->app->getInstance())) {
			throw new SiteNotFoundException('Page not found.');
		}

		$this->tag = $tag;

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

		$this->post_loader->setWhereClause(sprintf('enabled = %s and
			id in (select post from BlorgPostTagBinding where tag = %s)',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($tag->id, 'integer')));

		$this->post_loader->setOrderByClause('publish_date desc');
		$this->post_loader->setRange($this->max_entries);
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
		$favicon_file    = $this->app->theme->getFaviconFile();
		$blorg_base_href = $site_base_href.$this->app->config->blorg->path;
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

		if ($favicon_file !== null)
			$this->feed->setIcon($site_base_href.$favicon_file);

		if ($this->app->config->blorg->feed_logo != '') {
			$class = SwatDBClassMap::get('BlorgFile');
			$blorg_file = new $class();
			$blorg_file->setDatabase($this->app->db);
			$blorg_file->load(intval($this->app->config->blorg->feed_logo));
			$this->feed->setLogo($site_base_href.$blorg_file->getRelativeUri());
		}

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

		$this->buildAtomPagination($count);

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

	protected function buildAtomPagination($first_page_size)
	{
		// Feed paging. See IETF RFC 5005.
		$total_posts = $this->post_loader->getPostCount();
		$site_base_href  = $this->app->getBaseHref();

		$this->feed->addLink($site_base_href.'feed',
			'first', 'application/atom+xml');

		$last = (ceil(($total_posts - $first_page_size) / $this->min_entries)
			+ 1);

		$this->feed->addLink($site_base_href.'feed'.'/page'.$last,
			'last', 'application/atom+xml');

		if ($this->page > 1) {
			$previous = '/page'.($this->page - 1);
			$this->feed->addLink($site_base_href.'feed'.$previous,
				'previous', 'application/atom+xml');
		}

		if ($this->page != $last) {
			$next = '/page'.($this->page + 1);
			$this->feed->addLink($site_base_href.'feed'.$next,
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
