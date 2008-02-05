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
require_once 'Blorg/BlorgViewFactory.php';

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
				Blorg::_('A post with an id of ‘%d’ does not exist.'), $id));
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$this->ui->getWidget('pager')->process();
	}

	// }}}
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
		$this->buildNavBar();
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
				return $this->getRepliesTableModel($view);
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->layout->navbar->createEntry($this->post->getTitle());
	}

	// }}}

	// build phase - post details
	// {{{ protected function buildPost()

	protected function buildPost()
	{
		$content_block = $this->ui->getWidget('post_preview');
		ob_start();
		$view = BlorgViewFactory::buildPostView('admin', $this->app,
			$this->post);

		$view->display();
		$content_block->content = ob_get_clean();
		$content_block->content_type = 'text/xml';

		$ds = new SwatDetailsStore($this->post);
		$ds->has_modified_date = ($this->post->modified_date !== null);

		$details_view = $this->ui->getWidget('details_view');
		$details_view->data = $ds;

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Blorg::_('Post');
		$details_frame->subtitle = $this->post->getTitle();
		$this->title = $this->post->getTitle();
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

			$approved_column->visible                        = true;
			$this->ui->getWidget('approve_divider')->visible = true;
			$this->ui->getWidget('approve')->visible         = true;
			$this->ui->getWidget('deny')->visible            = true;
		}

		// set default time zone
		$date_column =
			$this->ui->getWidget('replies_view')->getColumn('createdate');

		$date_renderer = $date_column->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;
	}

	// }}}
	// {{{ protected function getReviewsTableModel()

	protected function getRepliesTableModel(SwatTableView $view)
	{
		$sql = sprintf('select count(id) from BlorgReply where post = %s',
			$this->app->db->quote($this->post->id, 'integer'));

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = sprintf('select id, fullname, author, bodytext, createdate, show,
			approved from BlorgReply where post = %s order by %s',
			$this->app->db->quote($this->post->id, 'integer'),
			$this->getOrderByClause($view, 'createdate desc'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$replies = SwatDB::query($this->app->db, $sql, 'BlorgReplyWrapper');

		$store = new SwatTableStore();

		foreach ($replies as $reply) {
			$ds = new SwatDetailsStore($reply);
			if ($reply->author !== null)
				$ds->fullname = $reply->author->name;

			$ds->bodytext = SwatString::condense(
				SwatString::ellipsizeRight($reply->bodytext, 500));

			$store->add($ds);
		}

		return $store;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/blorg/admin/styles/blorg-post-details-page.css',
			Store::PACKAGE_ID));
	}

	// }}}
}

?>
