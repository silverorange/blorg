<?php

require_once 'Site/pages/SitePage.php';
require_once 'Blorg/dataobjects/BlorgCommentWrapper.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';

/**
 * Displays an Atom feed of all recent comments in reverse chronological order
 *
 * The number of comments is always at least $min_entries, but if a recently
 * published set of comments (within the time of $recent_period) exceeds
 * $min_entries, up to $max_entries comments will be displayed. This makes it
 * easier to ensure that a subscriber won't miss any comments while
 * limiting server load for the feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentsAtomPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var BlorgCommentWrapper
	 */
	protected $comments;

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
	 * Period for recently added comments (in seconds)
	 *
	 * Default value is two days.
	 *
	 * @var integer
	 */
	protected $recent_period = 172800;

	/**
	 * The current page number of this feed.
	 *
	 * @var integer
	 */
	protected $page;

	/**
	 * The total number of comments for this feed.
	 *
	 * @var integer
	 */
	protected $comment_count;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		$page_number = 1)
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');

		parent::__construct($app, $layout);

		$this->page = $page_number;
		$this->initComments();
		$this->initCommentCount();
	}

	// }}}
	// {{{ protected function initComments()

	protected function initComments($offset = null)
	{
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select BlorgComment.* from BlorgComment
			inner join BlorgPost on BlorgComment.post = BlorgPost.id and
				BlorgPost.enabled = %s and BlorgPost.instance %s %s and
				BlorgPost.comment_status != %s
			where BlorgComment.status = %s and BlorgComment.spam = %s
			order by BlorgComment.createdate desc',
			$this->app->db->quote(true, 'boolean'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(BlorgPost::COMMENT_STATUS_CLOSED, 'integer'),
			$this->app->db->quote(BlorgComment::STATUS_PUBLISHED, 'integer'),
			$this->app->db->quote(false, 'boolean'));

		if ($offset != null)
			$this->app->db->setLimit($this->min_entries, $offset);
		else
			$this->app->db->setLimit($this->max_entries);


		$wrapper = SwatDBClassMap::get('BlorgCommentWrapper');
		$this->comments = SwatDB::query($this->app->db, $sql, $wrapper);
	}

	// }}}
	// {{{ protected function initCommentCount()

	protected function initCommentCount()
	{
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select count(BlorgComment.id) from BlorgComment
			inner join BlorgPost on BlorgComment.post = BlorgPost.id and
				BlorgPost.enabled = %s and BlorgPost.instance %s %s and
				BlorgPost.comment_status != %s
			where BlorgComment.status = %s and BlorgComment.spam = %s',
			$this->app->db->quote(true, 'boolean'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(BlorgPost::COMMENT_STATUS_CLOSED, 'integer'),
			$this->app->db->quote(BlorgComment::STATUS_PUBLISHED, 'integer'),
			$this->app->db->quote(false, 'boolean'));

		$this->comment_count = SwatDB::queryOne($this->app->db, $sql);
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
		$feed_base_href  = $blorg_base_href.'comments';

		$this->feed = new XML_Atom_Feed($blorg_base_href,
			sprintf(Blorg::_('%s - Recent Comments'),
				$this->app->config->site->title));

		$this->feed->setSubTitle(Blorg::_('Recent Comments'));

		$this->feed->addLink($site_base_href.$this->source, 'self',
			'application/atom+xml');

		$this->feed->setGenerator('Blörg');
		$this->feed->setBase($site_base_href);

		$this->buildIcon();
		$this->buildLogo();

		$threshold = new SwatDate();
		$threshold->toUTC();
		$threshold->subtractSeconds($this->recent_period);

		$count = 0;
		foreach ($this->comments as $comment) {
			$count++;

			if ($count > $this->max_entries ||
				($count > $this->min_entries) &&
					$comment->createdate->before($threshold))
				break;
		}

		$this->buildAtomPagination($count, $feed_base_href);

		if ($this->page > 1) {
			$this->initComments($count +
				($this->page - 2) * $this->min_entries);

			$count = $this->min_entries;
		}

		$total = 0;
		foreach ($this->comments as $comment) {
			if ($total < $count)
				$this->addComment($comment);

			$total++;
		}
	}

	// }}}
	// {{{ protected function buildAtomPagination()

	protected function buildAtomPagination($first_page_size, $base_href)
	{
		// Feed paging. See IETF RFC 5005.
		$last = ceil(
			($this->comment_count - $first_page_size)/ $this->min_entries) + 1;

		if ($this->page > $last)
			throw new SiteNotFoundException(Blorg::_('Page not found.'));

		$this->feed->addLink($base_href, 'first',
			'application/atom+xml');

		$this->feed->addLink($base_href.'/page'.$last, 'last',
			'application/atom+xml');

		if ($this->page > 1) {
			$previous = '/page'.($this->page - 1);
			$this->feed->addLink($base_href.$previous, 'previous',
				'application/atom+xml');
		}

		if ($this->page != $last) {
			$next = '/page'.($this->page + 1);
			$this->feed->addLink($base_href.$next, 'next',
				'application/atom+xml');
		}
	}

	// }}}
	// {{{ protected function buildIcon()

	protected function buildIcon()
	{
		if ($this->app->hasModule('SiteThemeModule')) {
			$favicon_file = $this->app->theme->getFaviconFile();

			if ($favicon_file !== null)
				$this->feed->setIcon($this->app->getBaseHref().$favicon_file);
		}
	}

	// }}}
	// {{{ protected function buildLogo()

	protected function buildLogo()
	{
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
	// {{{ protected function addComment()

	protected function addComment($comment)
	{
		$site_base_href  = $this->app->getBaseHref();
		$blorg_base_href = $site_base_href.$this->app->config->blorg->path;

		$post = $comment->post;
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

		$comment_uri = $post_uri.'#comment'.$comment->id;

		if ($comment->author !== null) {
			$author_name = $comment->author->name;
			if ($comment->author->visible) {
				$author_uri = $blorg_base_href.'author/'.
					$post->author->shortname;

				$author_email = $post->author->email;
			} else {
				$author_uri   = '';
				$author_email = '';
			}
		} else {
			$author_name  = $comment->fullname;
			$author_uri   = $comment->link;
			// don't show anonymous author email
			$author_email = '';
		}

		$entry = new XML_Atom_Entry($comment_uri,
			sprintf(Blorg::_('%s on “%s”'),
				$author_name, $post->getTitle()),
			$comment->createdate);

		$entry->setContent(BlorgComment::getBodytextXhtml(
			$comment->bodytext), 'html');

		$entry->addAuthor($author_name, $author_uri, $author_email);
		$entry->addLink($comment_uri, 'alternate', 'text/html');

		$this->feed->addEntry($entry);
	}

	// }}}
	// {{{ protected function displayAtomFeed()

	protected function displayAtomFeed()
	{
		echo $this->feed;
	}

	// }}}
}

?>
