<?php

require_once 'Blorg/pages/BlorgAbstractAtomPage.php';
require_once 'Blorg/dataobjects/BlorgCommentWrapper.php';
require_once 'XML/Atom/Entry.php';

/**
 * Displays an Atom feed of all recent comments in reverse chronological order
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

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->initComments($this->getArgument('page'));
	}

	// }}}
	// {{{ protected function initComments()

	protected function initComments($page)
	{
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select BlorgComment.* from BlorgComment %s where %s
			order by BlorgComment.createdate desc',
			$this->getJoinClause(),
			$this->getWhereClause());

		$offset = ($page - 1) * $this->getPageSize();
		$this->app->db->setLimit($this->getPageSize(), $offset);

		$wrapper = SwatDBClassMap::get('BlorgCommentWrapper');
		$this->comments = SwatDB::query($this->app->db, $sql, $wrapper);

		if (count($this->comments) === 0) {
			throw new SiteNotFoundException('Page not found.');
		}

		$sql = sprintf('select count(1) from BlorgComment %s where %s',
			$this->getJoinClause(),
			$this->getWhereClause());

		$this->total_count = SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getJoinClause()

	protected function getJoinClause()
	{
		$instance_id = $this->app->getInstanceId();
		return sprintf('inner join BlorgPost on
				BlorgComment.post = BlorgPost.id and
				BlorgPost.enabled = %s and BlorgPost.instance %s %s and
				BlorgPost.comment_status != %s',
			$this->app->db->quote(true, 'boolean'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(BlorgPost::COMMENT_STATUS_CLOSED, 'integer'));
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		return sprintf('BlorgComment.status = %s and BlorgComment.spam = %s',
			$this->app->db->quote(BlorgComment::STATUS_PUBLISHED, 'integer'),
			$this->app->db->quote(false, 'boolean'));
	}

	// }}}

	// build phase
	// {{{ protected function buildEntries()

	protected function buildEntries(XML_Atom_Feed $feed)
	{
		foreach ($this->comments as $comment) {
			$this->buildComment($feed, $comment);
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
			sprintf(Blorg::_('%s on “%s”'), $author_name, $post->getTitle()),
			$comment->createdate);

		$entry->setContent(BlorgComment::getBodytextXhtml(
			$comment->bodytext), 'html');

		$entry->addAuthor($author_name, $author_uri, $author_email);
		$entry->addLink($comment_uri, 'alternate', 'text/html');

		$feed->addEntry($entry);
	}

	// }}}

	// helper methods
	// {{{ protected function getTotalCount()

	protected function getTotalCount()
	{
		return $this->total_count;
	}

	// }}}
	// {{{ protected function getFeedBaseHref()

	protected function getFeedBaseHref()
	{
		return $this->getBlorgBaseHref().'feed/comments';
	}

	// }}}
	// {{{ protected function getPageSize()

	protected function getPageSize()
	{
		return 50;
	}

	// }}}
}

?>
