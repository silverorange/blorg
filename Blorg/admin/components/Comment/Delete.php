<?php

/**
 * Delete confirmation page for comments
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentDelete extends AdminDBDelete
{
	// {{{ private properties

	private $post;

	// }}}
	// {{{ public function setPost()

	public function setPost(BlorgPost $post)
	{
		$this->post = $post;
	}

	// }}}

	// process phase
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('integer');
		$instance_id = $this->app->getInstanceId();

		$this->addToSearchQueue($item_list);

		$sql = sprintf('delete from BlorgComment
			where id in
				(select BlorgComment.id from BlorgComment
					inner join BlorgPost on BlorgPost.id = BlorgComment.post
				where instance %s %s and BlorgComment.id in (%s))',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNS('posts');
		}

		$message = new SwatMessage(sprintf(Blorg::ngettext(
			'One comment has been deleted.',
			'%s comments have been deleted.', $num),
			SwatString::numberFormat($num)));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue($ids)
	{
		// this is automatically wrapped in a transaction because it is
		// called in saveDBData()
		$type = NateGoSearch::getDocumentType($this->app->db, 'post');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where
				document_id in
					(select distinct BlorgComment.post from BlorgComment
						where BlorgComment.id in (%s))
				and document_type = %s',
			$ids,
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type)
			select distinct BlorgComment.post, %s from
				BlorgComment where BlorgComment.id in (%s)',
			$this->app->db->quote($type, 'integer'),
			$ids);

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function relocate()

	protected function relocate()
	{
		$form = $this->ui->getWidget('confirmation_form');
		$url = $form->getHiddenField(self::RELOCATE_URL_FIELD);
		$this->app->relocate($url);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');
		$instance_id = $this->app->getInstanceId();

		$dep = new AdminListDependency();
		$dep->setTitle(Blorg::_('comment'), Blorg::_('comments'));

		$sql = sprintf(
			'select BlorgComment.id, BlorgComment.bodytext from BlorgComment
				inner join BlorgPost on BlorgPost.id = BlorgComment.post
			where instance %s %s and BlorgComment.id in (%s)
			order by BlorgComment.createdate desc, BlorgComment.id',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$item_list);

		$comments = SwatDB::query($this->app->db, $sql);
		$entries = array();

		foreach ($comments as $comment) {
			$entry = new AdminDependencyEntry();

			$entry->id           = $comment->id;
			$entry->title        = SwatString::ellipsizeRight(
				SwatString::condense(SiteCommentFilter::toXhtml(
					$comment->bodytext)), 100);

			$entry->status_level = AdminDependency::DELETE;
			$entry->parent       = null;

			$entries[] = $entry;
		}

		$dep->entries = $entries;

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		// build the navbar like we're in the Post component because it's the
		// only way this delete page gets loaded. In the Comment component,
		// comments get deleted with the AJAX server.

		$this->navbar->popEntry();
		$this->navbar->popEntry();

		$this->navbar->addEntry(new SwatNavBarEntry(
			Blorg::_('Posts'), 'Post'));

		$this->navbar->addEntry(new SwatNavBarEntry($this->post->getTitle(),
			sprintf('Post/Details?id=%s', $this->post->id)));

		$this->navbar->addEntry(new SwatNavBarEntry(
			Blorg::_('Delete Comments')));
	}

	// }}}
}

?>
