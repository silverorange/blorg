<?php

require_once 'Site/SiteCommentUi.php';
require_once 'Blorg/BlorgPageFactory.php';

/**
 * Blorg comment UI
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentUi extends SiteCommentUi
{
	// {{{ protected function setCommentPost()

	protected function setCommentPost(SiteComment $comment,
		SiteCommentStatus $post)
	{
		$comment->post = $post;
	}

	// }}}
	// {{{ protected function getPermalink()

	protected function getPermalink(SiteComment $comment)
	{
		return $this->app->getBaseHref().
			Blorg::getPostRelativeUri($this->app, $comment->post);

	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$this->addPostToSearchQueue();
		$this->addCommentToSearchQueue();
	}

	// }}}
	// {{{ protected function addPostToSearchQueue()

	protected function addPostToSearchQueue()
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'post');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->post->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->post->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function addCommentToSearchQueue()

	protected function addCommentToSearchQueue()
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'comment');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->comment->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->comment->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function clearCache()

	protected function clearCache()
	{
		// clear posts cache if comment is visible
		if (isset($this->app->memcache)) {
			if (!$this->comment->spam &&
				$this->comment->status === SiteComment::STATUS_PUBLISHED) {
				$this->app->memcache->flushNs('posts');
			}
		}
	}

	// }}}
}

?>
