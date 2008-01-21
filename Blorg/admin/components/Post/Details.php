<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatNavBar.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

/**
 * Details page for Posts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostDetails extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Post/details.xml';
	protected $post;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->loadPost();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}
	// {{{ protected function loadPost()

	protected function loadPost()
	{
		$id = SiteApplication::initVar('id');

		$post_class = SwatDBClassMap::get('BlorgPost');
		$this->post = new $post_class();
		$this->post->setDatabase($this->app->db);

		if (!$this->post->load($id))
			throw new AdminNotFoundException(sprintf(
				Blorg::_('A post with an id of ‘%d’ does not exist.'),
				$id));
	}

	// }}}

	// process phase
	// {{{ protected function processActions()

	protected function processActions(SwatTableView $view, SwatActions $actions)
	{
		switch ($view->id) {
		case 'replies_view':
			$this->processRepliesActions($view, $actions);
			break;
		}
	}

	// }}}
	// {{{ protected function processRepliesActions()

	protected function processRepliesActions(SwatTableView $view,
		SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Post/ReplyDelete');
			$this->app->getPage()->setItems($view->getSelection());
			$this->app->getPage()->setPost($this->post);
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildPost();
		$this->buildReplies();

		$toolbar = $this->ui->getWidget('details_toolbar');
		$toolbar->setToolLinkValues($this->post->id);

		$toolbar = $this->ui->getWidget('replies_toolbar');
		$toolbar->setToolLinkValues($this->post->id);
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		switch ($view->id) {
			case 'replies_view':
				return $this->post->replies;
		}
	}

	// }}}

	// build phase - post details
	// {{{ protected function buildPost()

	protected function buildPost()
	{
		$ds = new SwatDetailsStore($this->post);
		$details_view = $this->ui->getWidget('details_view');
		$details_view->data = $ds;

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Blorg::_('Post');
		// todo: make next two lines work nicer with posts with no title
		$details_frame->subtitle = $this->post->title;
		$this->title = $this->post->title;
	}

	// }}}

	// build phase - reply details
	// {{{ protected function buildReplies()

	protected function buildReplies()
	{
		$toolbar = $this->ui->getWidget('replies_toolbar');
		$toolbar->setToolLinkValues($this->post->id);
	}

	// }}}
}

?>
