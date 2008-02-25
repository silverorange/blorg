<?php

require_once 'Site/pages/SitePage.php';
require_once 'Blorg/dataobjects/BlorgReplyWrapper.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';

/**
 * Displays an Atom feed of all recent replies in reverse chronological order
 *
 * The constant MAX_REPLIES determines how many replies are displayed in the
 * feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAtomRepliesPage extends SitePage
{
	// {{{ class constants

	const MAX_REPLIES = 20;

	// }}}
	// {{{ protected properties

	/**
	 * @var BlorgReplyWrapper
	 */
	protected $replies;

	/**
	 * @var XML_Atom_Feed
	 */
	protected $feed;

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
		$instance_id = $this->app->instance->getId();

		$sql = sprintf('select BlorgReply.* from BlorgReply
			inner join BlorgPost on BlorgReply.post = BlorgPost.id
			where BlorgPost.instance %s %s and BlorgPost.enabled = true and
				BlorgPost.reply_status != %s and BlorgReply.status = %s
			order by BlorgReply.createdate desc limit %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(BlorgPost::REPLY_STATUS_CLOSED, 'integer'),
			$this->app->db->quote(BlorgReply::STATUS_PUBLISHED, 'integer'),
			$this->app->db->quote(self::MAX_REPLIES, 'integer'));

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

		foreach ($this->replies as $reply) {
			$post = $reply->post;

			$path = $blorg_base_href.'archive';

			$date = clone $post->post_date;
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
