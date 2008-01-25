<?php

require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Admin/AdminListDependency.php';

/**
 * Delete confirmation page for Replies
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostReplyDelete extends AdminDBDelete
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

		$sql = sprintf('delete from BlorgReply where id in (%s)', $item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Blorg::ngettext(
			'One reply has been deleted.',
			'%d replies have been deleted.', $num),
			SwatString::numberFormat($num)),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');

		$dep = new AdminListDependency();
		$dep->setTitle(Blorg::_('reply'), Blorg::_('replies'));
		//TODO: ellipsize bodytext
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'BlorgReply', 'integer:id', null, 'text:bodytext', 'id',
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

		$this->navbar->addEntry(new SwatNavBarEntry(Blorg::_('Delete Replies')));
	}

	// }}}
}

?>
