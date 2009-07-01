<?php

require_once 'Site/SiteCommentStatus.php';
require_once 'Blorg/pages/BlorgAbstractAtomPage.php';
require_once 'Blorg/dataobjects/BlorgCommentWrapper.php';
require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';
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
		// get comments for this page
		$this->comments = false;

		if (isset($this->app->memcache)) {
			$key = $this->getCommentsCacheKey();
			$this->comments = $this->app->memcache->getNs('posts', $key);

			/*
			 * Note: The limit of comments per page is somewhat important here.
			 * In extreme cases, we could run over the 1M size limit for cached
			 * values. This would occur when every comment is close to the
			 * maximum size in bodytext (8K) and the associated posts also have
			 * a very large bodytext (about 8K each). In these rare cases,
			 * caching will fail.
			 */
		}

		if ($this->comments === false) {
			$sql = sprintf('select BlorgComment.* from BlorgComment %s where %s
				order by BlorgComment.createdate desc',
				$this->getJoinClause(),
				$this->getWhereClause());

			$offset = ($page - 1) * $this->getPageSize();
			$this->app->db->setLimit($this->getPageSize(), $offset);

			$wrapper = SwatDBClassMap::get('BlorgCommentWrapper');
			$this->comments = SwatDB::query($this->app->db, $sql, $wrapper);

			// efficiently load posts
			$post_wrapper = SwatDBClassMap::get('BlorgPostWrapper');
			$post_sql = 'select id, title, shortname, bodytext, publish_date
				from BlorgPost
				where id in (%s)';

			$this->comments->loadAllSubDataObjects('post', $this->app->db,
				$post_sql, $post_wrapper);

			// efficiently load authors
			$author_wrapper = SwatDBClassMap::get('BlorgAuthorWrapper');
			$author_sql = 'select id, name, shortname, email, visible
				from BlorgAuthor
				where id in (%s)';

			$this->comments->loadAllSubDataObjects('author', $this->app->db,
				$author_sql, $author_wrapper);

			if (isset($this->app->memcache)) {
				$this->app->memcache->setNs('posts', $key, $this->comments);
			}
		} else {
			$this->comments->setDatabase($this->app->db);
		}

		// if we're not on the first page and there are no comments, 404
		if ($page > 1 && count($this->comments) === 0) {
			throw new SiteNotFoundException('Page not found.');
		}

		// get total number of comments
		$this->total_count = false;

		if (isset($this->app->memcache)) {
			$total_key = $this->getTotalCountCacheKey();
			$this->total_count = $this->app->memcache->getNs('posts',
				$total_key);
		}

		if ($this->total_count === false) {
			$sql = sprintf('select count(1) from BlorgComment %s where %s',
				$this->getJoinClause(),
				$this->getWhereClause());

			$this->total_count = SwatDB::queryOne($this->app->db, $sql);

			if (isset($this->app->memcache)) {
				$this->app->memcache->setNs('posts', $total_key,
					$this->total_count);
			}
		}
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
			$this->app->db->quote(SiteCommentStatus::CLOSED, 'integer'));
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		return sprintf('BlorgComment.status = %s and BlorgComment.spam = %s',
			$this->app->db->quote(SiteComment::STATUS_PUBLISHED, 'integer'),
			$this->app->db->quote(false, 'boolean'));
	}

	// }}}
	// {{{ protected function getCommentsCacheKey()

	protected function getCommentsCacheKey()
	{
		return 'comments_feed_page'.$this->getArgument('page');
	}

	// }}}
	// {{{ protected function getTotalCountCacheKey()

	protected function getTotalCountCacheKey()
	{
		return 'comments_feed_total_count';
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
					$comment->author->shortname;

				$author_email = $comment->author->email;
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

		$entry->setContent(SiteCommentFilter::toXhtml($comment->bodytext),
			'html');

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
