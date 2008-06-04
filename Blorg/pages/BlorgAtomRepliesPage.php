<?php

require_once 'Site/pages/SitePage.php';
require_once 'Blorg/dataobjects/BlorgReplyWrapper.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';

/**
 * Displays an Atom feed of all recent replies in reverse chronological order
 *
 * The number of replies is always at least $min_entries, but if a recently
 * published set of replies (within the time of $recent_period) exceeds
 * $min_entries, up to $max_entries replies will be displayed. This makes it
 * easier to ensure that a subscriber won't miss any replies while
 * limiting server load for the feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAtomRepliesPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var BlorgReplyWrapper
	 */
	protected $replies;

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
	 * Period for recently added replies (in seconds)
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
		$layout = new SiteLayout($app, 'Blorg/layouts/xhtml/atom.php');

		parent::__construct($app, $layout);

		$this->initReplies();
	}

	// }}}
	// {{{ protected function initReplies()

	protected function initReplies()
	{
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select BlorgReply.* from BlorgReply
			inner join BlorgPost on BlorgReply.post = BlorgPost.id and
				BlorgPost.enabled = %s and BlorgPost.instance %s %s and
				BlorgPost.reply_status != %s
			where BlorgReply.status = %s and BlorgReply.spam = %s
			order by BlorgReply.createdate desc',
			$this->app->db->quote(true, 'boolean'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(BlorgPost::REPLY_STATUS_CLOSED, 'integer'),
			$this->app->db->quote(BlorgReply::STATUS_PUBLISHED, 'integer'),
			$this->app->db->quote(false, 'boolean'));

		$this->app->db->setLimit($this->max_entries);

		$wrapper = SwatDBClassMap::get('BlorgReplyWrapper');
		$this->replies = SwatDB::query($this->app->db, $sql, $wrapper);
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

		$this->feed = new XML_Atom_Feed($blorg_base_href,
			sprintf(Blorg::_('%s - Recent Replies'),
				$this->app->config->site->title));

		$this->feed->addLink($site_base_href.$this->source, 'self',
			'application/atom+xml');

		$this->feed->setGenerator('Blörg');
		$this->feed->setBase($site_base_href);

		$threshold = new SwatDate();
		$threshold->toUTC();
		$threshold->subtractSeconds($this->recent_period);

		$count = 0;

		foreach ($this->replies as $reply) {
			$count++;

			if ($count > $this->max_entries ||
				($count > $this->min_entries) &&
					$reply->createdate->before($threshold))
				break;

			$post = $reply->post;

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

			$reply_uri = $post_uri.'#'.$reply->id;

			if ($reply->author !== null) {
				$author_name = $reply->author->name;
				if ($reply->author->show) {
					$author_uri = $blorg_base_href.'author/'.
						$post->author->shortname;

					$author_email = $post->author->email;
				} else {
					$author_uri   = '';
					$author_email = '';
				}
			} else {
				$author_name  = $reply->fullname;
				$author_uri   = $reply->link;
				// don't show anonymous author email
				$author_email = '';
			}

			$entry = new XML_Atom_Entry($reply_uri,
				sprintf(Blorg::_('%s on “%s”'), $author_name, $post->title),
				$reply->createdate);

			$entry->setContent(BlorgReply::getBodytextXhtml($reply->bodytext),
				'html');

			$entry->addAuthor($author_name, $author_uri, $author_email);
			$entry->addLink($reply_uri, 'alternate', 'text/html');

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
