<?php

require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Admin/AdminListDependency.php';
require_once 'NateGoSearch/NateGoSearch.php';

/**
 * Delete confirmation page for comments
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostCommentDelete extends AdminDBDelete
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

		$this->addToSearchQueue($item_list);

		$sql = sprintf('delete from BlorgComment where id in (%s)', $item_list);
		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Blorg::ngettext(
			'One comment has been deleted.',
			'%d comments have been deleted.', $num),
			SwatString::numberFormat($num)),
			SwatMessage::NOTIFICATION);

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

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(Blorg::_('comment'), Blorg::_('comments'));
		//TODO: ellipsize bodytext
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'BlorgComment', 'integer:id', null, 'text:bodytext', 'id',
			'id in ('.$item_list.')', AdminDependency::DELETE);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();

		$this->buildNavBar();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry($this->post->getTitle(),
			sprintf('Post/Details?id=%s', $this->post->id)));

		$this->navbar->addEntry(new SwatNavBarEntry(
			Blorg::_('Delete Comments')));
	}

	// }}}
}

?>
