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
class BlorgBlorgPostDetails extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/BlorgPost/details.xml';
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
				Blorg::_('A post with an id of ‘%d’ does not exist.'), $id));
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
			$this->app->replacePage('BlorgPost/ReplyDelete');
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
		$details_frame->subtitle = $this->post->getTitle();
		$this->title = $this->post->getTitle();

		if ($this->post->bodytext !== null)
			$this->post->bodytext = SwatString::condense(SwatString::toXHTML(
				$this->post->bodytext));

		if ($this->post->extended_bodytext !== null)
			$this->post->extended_bodytext = SwatString::condense(
				SwatString::toXHTML($this->post->extended_bodytext));

	}

	// }}}

	// build phase - reply details
	// {{{ protected function buildReplies()

	protected function buildReplies()
	{
		$toolbar = $this->ui->getWidget('replies_toolbar');
		$toolbar->setToolLinkValues($this->post->id);

		// hide all approved stuff unless the BlorgPost needs it
		if ($this->post->reply_status === BlorgPost::REPLY_STATUS_MODERATED) {
			$approved_column =
				$this->ui->getWidget('replies_view')->getColumn('approved');

			$approved_column->visible = true;

			$this->ui->getWidget('approve_divider')->visible = false;
			$this->ui->getWidget('approve')->visible         = false;
			$this->ui->getWidget('deny')->visible            = false;
		}

		// set default time zone
		$date_column =
			$this->ui->getWidget('replies_view')->getColumn('createdate');

		$date_renderer = $date_column->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;
	}

	// }}}
}

?>
