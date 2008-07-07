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
	 * @var interger
	 */
	protected $recent_period = 172800;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null)
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');

		parent::__construct($app, $layout);

		$this->initComments();
	}

	// }}}
	// {{{ protected function initComments()

	protected function initComments()
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

		$this->app->db->setLimit($this->max_entries);

		$wrapper = SwatDBClassMap::get('BlorgCommentWrapper');
		$this->comments = SwatDB::query($this->app->db, $sql, $wrapper);
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

		$this->feed = new XML_Atom_Feed($blorg_base_href,
			sprintf(Blorg::_('%s - Recent Comments'),
				$this->app->config->site->title));

		$this->feed->setSubTitle(Blorg::_('Recent Comments'));

		$this->feed->addLink($site_base_href.$this->source, 'self',
			'application/atom+xml');

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

		$count = 0;

		foreach ($this->comments as $comment) {
			$count++;

			if ($count > $this->max_entries ||
				($count > $this->min_entries) &&
					$comment->createdate->before($threshold))
				break;

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
