<?php

require_once 'Blorg/pages/BlorgAbstractAtomPage.php';
require_once 'Blorg/dataobjects/BlorgCommentWrapper.php';
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
class BlorgCommentsAtomPage extends BlorgAbstractAtomPage
{
	// {{{ protected properties

	/**
	 * @var BlorgCommentWrapper
	 */
	protected $comments;

	/**
	 * The total number of comments for this feed.
	 *
	 * @var integer
	 */
	protected $total_count;

	/**
	 * The total number of comments for the front page of this feed.
	 *
	 * @var integer
	 */
	protected $front_page_count;

	// }}}

	// init phase
	// {{{ protected function initEntries()

	protected function initEntries()
	{
		$this->initComments();
		$this->initTotalCount();
		$this->initFrontPageCount();
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
	// {{{ protected function initTotalCount()

	protected function initTotalCount()
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

		$this->total_count = SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function initFrontPageCount()

	protected function initFrontPageCount()
	{
		$count = 0;
		foreach ($this->comments as $comment) {
			if ($count > $this->max_entries || ($count > $this->min_entries)
				&& !$this->isEntryRecent($comment->createdate))
				break;

			$count++;
		}

		$this->front_page_count = $count;
	}

	// }}}

	// build phase
	// {{{ protected function buildEntries()

	protected function buildEntries(XML_Atom_Feed $feed)
	{
		$limit = $this->getFrontPageCount();
		if ($this->page > 1) {
			$this->initComments($this->getFrontPageCount() +
				($this->page - 2) * $this->min_entries);

			$limit = $this->min_entries;
		}

		$count = 0;
		foreach ($this->comments as $comment) {
			if ($count < $limit)
				$this->buildComment($feed, $comment);

			$count++;
		}
	}

	// }}}
	// {{{ protected function buildHeader()

	protected function buildHeader(XML_Atom_Feed $feed)
	{
		parent::buildHeader($feed);
		$feed->setSubTitle(Blorg::_('Recent Comments'));
	}

	// }}}
	// {{{ protected function buildComment()

	protected function buildComment(XML_Atom_Feed $feed, BlorgComment $comment)
	{
		$post = $comment->post;
		$path = $this->getBlorgBaseHref().'archive';

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
				$author_uri = $this->getBlorgBaseHref().'author/'.
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

		$feed->addEntry($entry);
	}

	// }}}

	// helper methods
	// {{{ protected function getFrontPageCount()

	protected function getFrontPageCount()
	{
		return $this->front_page_count;
	}

	// }}}
	// {{{ protected function getTotalCount()

	protected function getTotalCount()
	{
		return $this->total_count;
	}

	// }}}
	// {{{ protected function getBlorgBaseHref()

	protected function getBlorgBaseHref()
	{
		return $this->app->getBaseHref().$this->app->config->blorg->path;
	}

	// }}}
	// {{{ protected function getFeedBaseHref()

	protected function getFeedBaseHref()
	{
		return $this->getBlorgBaseHref().'feed/comments';
	}

	// }}}
}

?>
