<?php

/**
 * Blorg search indexer application for NateGoSearch
 *
 * This indexer indexes posts, comments, and articles by default.
 * Subclasses may change how and what gets indexed.
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgNateGoSearchIndexer extends SiteNateGoSearchIndexer
{
	// {{{ public function queue()

	/**
	 * Repopulates the entire search queue
	 */
	public function queue()
	{
		parent::queue();

		$this->queuePosts();
		$this->queueComments();
	}

	// }}}
	// {{{ protected function index()

	/**
	 * Indexes posts and articles
	 *
	 * Subclasses should override this method to add or remove additional
	 * indexed tables.
	 */
	protected function index()
	{
		parent::index();

		$this->indexPosts();
		$this->indexComments();
	}

	// }}}
	// {{{ protected function queuePosts()

	/**
	 * Repopulates the posts queue
	 */
	protected function queuePosts()
	{
		$this->debug(Blorg::_('Repopulating post search queue ... '));

		$type = NateGoSearch::getDocumentType($this->db, 'post');

		// clear queue
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from BlorgPost',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->debug(Blorg::_('done')."\n");
	}

	// }}}
	// {{{ protected function indexPosts()

	protected function indexPosts()
	{
		$spell_checker = new NateGoSearchPSpellSpellChecker('en_US', '', '',
			$this->getCustomWordList());

		$post_indexer = new NateGoSearchIndexer('post', $this->db);
		$post_indexer->setSpellChecker($spell_checker);

		$post_indexer->addTerm(new NateGoSearchTerm('title', 30));
		$post_indexer->addTerm(new NateGoSearchTerm('bodytext', 20));
		$post_indexer->addTerm(new NateGoSearchTerm('extended_bodytext', 18));
		$post_indexer->addTerm(new NateGoSearchTerm('comments', 1));
		$post_indexer->setMaximumWordLength(32);
		$post_indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$type = NateGoSearch::getDocumentType($this->db, 'post');

		$sql = sprintf('select BlorgPost.*
			from BlorgPost
				inner join NateGoSearchQueue
					on BlorgPost.id = NateGoSearchQueue.document_id
					and NateGoSearchQueue.document_type = %s
			order by BlorgPost.id',
			$this->db->quote($type, 'integer'));

		$this->debug(Blorg::_('Indexing posts... ').'   ');

		$posts = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('BlorgPostWrapper'));

		$total = count($posts);
		$count = 0;
		$current_post_id = null;
		foreach ($posts as $post) {
			$ds = new SwatDetailsStore($post);
			$ds->title = $post->getTitle();

			$ds->comments = '';
			foreach ($post->getVisibleComments() as $comment)
				$ds->comments.= $comment->fullname.' '.$comment->bodytext.' ';

			if ($count % 10 == 0) {
				$post_indexer->commit();
				$this->debug(str_repeat(chr(8), 3));
				$this->debug(sprintf('%2d%%', ($count / $total) * 100));
			}

			$document = new NateGoSearchDocument($ds, 'id');
			$post_indexer->index($document);
			$current_post_id = $post->id;
			$count++;
		}

		$this->debug(str_repeat(chr(8), 3).Blorg::_('done')."\n");

		$post_indexer->commit();
		unset($post_indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ protected function queueComments()

	/**
	 * Repopulates the comments queue
	 */
	protected function queueComments()
	{
		$this->debug(Blorg::_('Repopulating comment search queue ... '));

		$type = NateGoSearch::getDocumentType($this->db, 'comment');

		// clear queue
		$sql = sprintf('delete from NateGoSearchQueue
			where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		// fill queue
		$sql = sprintf('insert into NateGoSearchQueue
			(document_type, document_id) select %s, id from BlorgComment',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);

		$this->debug(Blorg::_('done')."\n");
	}

	// }}}
	// {{{ protected function indexComments()

	protected function indexComments()
	{
		$type_shortname = 'comment';

		$spell_checker = new NateGoSearchPSpellSpellChecker('en_US', '', '',
			$this->getCustomWordList());

		$comment_indexer = new NateGoSearchIndexer($type_shortname, $this->db);
		$comment_indexer->setSpellChecker($spell_checker);

		$comment_indexer->addTerm(new NateGoSearchTerm('fullname', 30));
		$comment_indexer->addTerm(new NateGoSearchTerm('email', 20));
		$comment_indexer->addTerm(new NateGoSearchTerm('bodytext', 1));
		$comment_indexer->setMaximumWordLength(32);
		$comment_indexer->addUnindexedWords(
			NateGoSearchIndexer::getDefaultUnindexedWords());

		$type = NateGoSearch::getDocumentType($this->db, $type_shortname);

		$sql = sprintf('select BlorgComment.*
			from BlorgComment
				inner join NateGoSearchQueue
					on BlorgComment.id = NateGoSearchQueue.document_id
					and NateGoSearchQueue.document_type = %s
			order by BlorgComment.id',
			$this->db->quote($type, 'integer'));

		$this->debug(Blorg::_('Indexing comments... ').'   ');

		$comments = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('BlorgCommentWrapper'));

		$total = count($comments);
		$count = 0;
		foreach ($comments as $comment) {
			$ds = new SwatDetailsStore($comment);

			if ($count % 10 == 0) {
				$comment_indexer->commit();
				$this->debug(str_repeat(chr(8), 3));
				$this->debug(sprintf('%2d%%', ($count / $total) * 100));
			}

			$document = new NateGoSearchDocument($ds, 'id');
			$comment_indexer->index($document);
			$count++;
		}

		$this->debug(str_repeat(chr(8), 3).Blorg::_('done')."\n");

		$comment_indexer->commit();
		unset($comment_indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
}

?>
