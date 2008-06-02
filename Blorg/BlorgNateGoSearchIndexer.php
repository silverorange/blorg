<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Site/SiteNateGoSearchIndexer.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Blorg search indexer application for NateGoSearch
 *
 * This indexer indexes posts and articles by default.
 * Subclasses may change how and what gets indexed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
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
	}

	// }}}
	// {{{ protected function queuePosts()

	/**
	 * Repopulates the posts queue
	 */
	protected function queuePhotos()
	{
		$this->output(Blorg::_('Repopulating post search queue ... '),
			self::VERBOSITY_ALL);

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

		$this->output(Blorg::_('done')."\n", self::VERBOSITY_ALL);
	}

	// }}}
	// {{{ protected function indexPosts()

	protected function indexPosts()
	{
		$spell_checker = new NateGoSearchPSpellSpellChecker('en');
		$spell_checker->setCustomWordList($this->getCustomWordList());
		$spell_checker->loadCustomContent();

		$post_indexer = new NateGoSearchIndexer('post', $this->db);
		$post_indexer->setSpellChecker($spell_checker);

		$post_indexer->addTerm(new NateGoSearchTerm('title', 20));
		$post_indexer->addTerm(new NateGoSearchTerm('bodytext', 15));
		$post_indexer->addTerm(new NateGoSearchTerm('extended_bodytext', 13));
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

		$this->output(Blorg::_('Indexing posts... ').'   ',
			self::VERBOSITY_ALL);

		$posts = SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('BlorgPostWrapper'));

		$total = count($posts);
		$count = 0;
		$current_post_id = null;
		foreach ($posts as $post) {
			$ds = new SwatDetailsStore($post);
			$ds->title = $post->getTitle();

			if ($count % 10 == 0) {
				$post_indexer->commit();
				$this->output(str_repeat(chr(8), 3), self::VERBOSITY_ALL);
				$this->output(sprintf('%2d%%', ($count / $total) * 100),
					self::VERBOSITY_ALL);
			}

			$document = new NateGoSearchDocument($ds, 'id');
			$post_indexer->index($document);
			$current_post_id = $post->id;
			$count++;
		}

		$this->output(str_repeat(chr(8), 3).Blorg::_('done')."\n",
			self::VERBOSITY_ALL);

		$post_indexer->commit();
		unset($post_indexer);

		$sql = sprintf('delete from NateGoSearchQueue where document_type = %s',
			$this->db->quote($type, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
}

?>
