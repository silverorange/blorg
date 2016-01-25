<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatNavBar.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/SiteViewFactory.php';
require_once 'Site/admin/SiteCommentVisibilityCellRenderer.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/dataobjects/BlorgCommentWrapper.php';

/**
 * Details page for Posts
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
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

		if (!$this->post->load($id)) {
			throw new AdminNotFoundException(sprintf(
				Blorg::_('A post with an id of ‘%d’ does not exist.'), $id));
		}

		$instance_id = $this->post->getInternalValue('instance');
		if ($instance_id !== $this->app->getInstanceId()) {
			throw new AdminNotFoundException(sprintf(
				Blorg::_('A post with an id of ‘%d’ does not exist.'), $id));
		}
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
		case 'comments_view':
			$this->processCommentsActions($view, $actions);
			break;
		}
	}

	// }}}
	// {{{ protected function processCommentsActions()

	protected function processCommentsActions(SwatTableView $view,
		SwatActions $actions)
	{
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Comment/Delete');
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
		$this->buildComments();

		$toolbar = $this->ui->getWidget('details_toolbar');
		$toolbar->setToolLinkValues($this->post->id);
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		switch ($view->id) {
		case 'comments_view':
			return $this->getCommentsTableModel($view);
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$this->layout->navbar->createEntry($this->post->getTitle());
	}

	// }}}

	// build phase - post details
	// {{{ protected function buildPost()

	protected function buildPost()
	{
		$content_block = $this->ui->getWidget('post_preview');
		ob_start();
		$view = $this->getPostView();
		$view->display($this->post);
		$content_block->content = ob_get_clean();
		$content_block->content_type = 'text/xml';

		$details_view = $this->ui->getWidget('details_view');
		$details_view->data = $this->getPostDetailsStore();

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Blorg::_('Post');
		$details_frame->subtitle = $this->post->getTitle();
		$this->title = $this->post->getTitle();
	}

	// }}}
	// {{{ protected function getPostView()

	protected function getPostView()
	{
		$view = SiteViewFactory::get($this->app, 'post');

		$view->setPathPrefix('../');
		$view->setPartLink('author',            false);
		$view->setPartLink('title',             false);
		$view->setPartLink('permalink',         false);
		$view->setPartLink('comment_count',     false);
		$view->setPartLink('extended_bodytext', false);
		$view->setPartLink('author',            false);

		return $view;
	}

	// }}}
	// {{{ protected function getPostDetailsStore()

	protected function getPostDetailsStore()
	{
		$store = new SwatDetailsStore($this->post);

		$store->has_modified_date = ($this->post->modified_date !== null);

		$store->comment_status_title =
			BlorgPost::getCommentStatusTitle($this->post->comment_status);

		ob_start();
		$this->displayTags();
		$store->tags_summary = ob_get_clean();

		return $store;
	}

	// }}}

	// build phase - comment details
	// {{{ protected function buildComments()

	protected function buildComments()
	{
		$toolbar = $this->ui->getWidget('comments_toolbar');
		$toolbar->setToolLinkValues($this->post->id);

		// set default time zone
		$date_column =
			$this->ui->getWidget('comments_view')->getColumn('createdate');

		$date_renderer = $date_column->getRendererByPosition();
		$date_renderer->display_time_zone = $this->app->default_time_zone;
	}

	// }}}
	// {{{ protected function getCommentsTableModel()

	protected function getCommentsTableModel(SwatTableView $view)
	{
		$sql = sprintf('select count(id) from BlorgComment
			where post = %s and spam = %s',
			$this->app->db->quote($this->post->id, 'integer'),
			$this->app->db->quote(false, 'boolean'));

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = sprintf(
			'select id, fullname, author, bodytext, createdate, status
			from BlorgComment
			where post = %s and spam = %s
			order by %s',
			$this->app->db->quote($this->post->id, 'integer'),
			$this->app->db->quote(false, 'boolean'),
			$this->getOrderByClause($view, 'createdate'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$comments = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgCommentWrapper'));

		$store = new SwatTableStore();

		foreach ($comments as $comment) {
			$ds = new SwatDetailsStore($comment);
			//TODO: distinguish authors somehow
			if ($comment->author !== null)
				$ds->fullname = $comment->author->name;

			$ds->bodytext = SwatString::condense(
				SwatString::ellipsizeRight($comment->bodytext, 500));

			$ds->post_id = $this->post->id;

			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function displayTags()

	protected function displayTags()
	{
		if (count($this->post->tags)) {
			$base_anchor_tag = $this->getTagsBaseAnchorTag();
			echo '<ul>';

			foreach ($this->post->tags as $tag) {
				echo '<li>';
				$anchor_tag = clone $base_anchor_tag;
				$anchor_tag->href.= $tag->id;
				$anchor_tag->setContent($tag->title);
				$anchor_tag->display();
				echo '</li>';
			}

			echo '<ul>';
		}
	}

	// }}}
	// {{{ protected function getTagsBaseAnchorTag()

	protected function getTagsBaseAnchorTag()
	{
		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = 'Tag/Details?id=';

		return $anchor_tag;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/blorg/admin/styles/blorg-post-details-page.css',
			Blorg::PACKAGE_ID));
	}

	// }}}
}

?>
