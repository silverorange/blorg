<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatString.php';
require_once 'Blorg/dataobjects/BlorgCommentWrapper.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';
require_once 'Blorg/admin/BlorgCommentDisplay.php';

/**
 * Page to manage pending comments on posts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentIndex extends AdminPage
{
	// {{{ class constants

	const SHOW_UNAPPROVED = 1;
	const SHOW_ALL        = 2;
	const SHOW_ALL_SPAM   = 3;
	const SHOW_SPAM       = 4;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Blorg/admin/components/Comment/index.xml';

	/**
	 * @var string
	 */
	protected $where_clause;

	/**
	 * @var array
	 */
	protected $comments;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$visibility_options = array(
			self::SHOW_UNAPPROVED => Blorg::_('Pending Comments'),
			self::SHOW_ALL        => Blorg::_('All Comments'),
			self::SHOW_ALL_SPAM   => Blorg::_('All Comments, Including Spam'),
			self::SHOW_SPAM       => Blorg::_('Spam Only'),
		);

		$visibility = $this->ui->getWidget('search_visibility');
		$visibility->addOptionsByArray($visibility_options);

		// if default comment status is moderated, only show pending comments
		// by default.
		if ($this->app->config->blorg->default_comment_status === 'moderated') {
			$visibility->value = self::SHOW_UNAPPROVED;
		} else {
			$visibility->value = self::SHOW_ALL;
		}

		$this->processSearchUi();

		$this->initComments();
		$this->initCommentReplicator();
	}

	// }}}
	// {{{ protected function initComments()

	protected function initComments()
	{
		$sql = 'select count(id) from BlorgComment where '.
			$this->getWhereClause();

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		// load comments
		$sql = sprintf(
			'select * from BlorgComment
			where %s
			order by createdate desc',
			$this->getWhereClause());

		$this->app->db->setLimit($pager->page_size, $pager->current_record);

		$wrapper = SwatDBClassMap::get('BlorgCommentWrapper');
		$comments = SwatDB::query($this->app->db, $sql, $wrapper);

		// init result message
		$visibility = $this->ui->getWidget('search_visibility')->value;
		switch ($visibility) {
		default:
		case self::SHOW_UNAPPROVED :
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(
					Blorg::_('pending comment'),
					Blorg::_('pending comments'));

			break;
		case self::SHOW_ALL :
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(
					Blorg::_('comment'),
					Blorg::_('comments'));

			break;

		case self::SHOW_ALL_SPAM :
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(
					Blorg::_('comment (including spam)'),
					Blorg::_('comments (including spam)'));

			break;

		case self::SHOW_SPAM :
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(
					Blorg::_('spam comment'),
					Blorg::_('spam comments'));

			break;
		}

		// efficiently load posts for all comments
		$instance_id = $this->app->getInstanceId();
		$post_sql = sprintf('select id, title, bodytext
			from BlorgPost
			where instance %s %s and id in (%%s)',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$post_wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$comments->loadAllSubDataObjects('post', $this->app->db, $post_sql,
			$post_wrapper);

		$this->comments = array();
		foreach ($comments as $comment) {
			$this->comments[$comment->id] = $comment;
		}
	}

	// }}}
	// {{{ protected function initCommentReplicator()

	protected function initCommentReplicator()
	{
		$comment_display = $this->ui->getWidget('comment');
		$comment_display->setApplication($this->app);

		$replicator = $this->ui->getWidget('comment_replicator');
		$replicator->replication_ids = array_keys($this->comments);
	}

	// }}}
	// {{{ protected function processSearchUi()

	protected function processSearchUi()
	{
		$search_frame = $this->ui->getWidget('search_frame');
		$search_frame->init();
		$search_frame->process();

		$form = $this->ui->getWidget('search_form');
		if ($form->isProcessed()) {
			$this->saveState();
		}

		if ($this->hasState()) {
			$this->loadState();
		}

		$this->ui->getWidget('pager')->init();
		$this->ui->getWidget('pager')->process();
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$instance_id = $this->app->getInstanceId();

			$where = sprintf(
				'post in (select id from BlorgPost where instance %s %s)',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

			$keywords = $this->ui->getWidget('search_keywords')->value;
			if (trim($keywords) != '') {
				$clause = new AdminSearchClause('bodytext', $keywords);
				$clause->table = 'BlorgComment';
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$where.= $clause->getClause($this->app->db, 'and');
			}

			$author_clause = new AdminSearchClause('integer:author',
				$this->ui->getWidget('search_author')->value);

			$fullname = $this->ui->getWidget('search_fullname')->value;
			if (trim($fullname) != '') {
				$fullname_clause = new AdminSearchClause('fullname', $fullname);
				$fullname_clause->table = 'BlorgComment';
				$fullname_clause->operator = AdminSearchClause::OP_CONTAINS;

				$where.= ' and (';
				$where.= $fullname_clause->getClause($this->app->db, '');
				$where.= $author_clause->getClause($this->app->db, 'or');
				$where.= ')';

			} else {
				$where.= $author_clause->getClause($this->app->db);
			}

			$visibility = $this->ui->getWidget('search_visibility')->value;
			switch ($visibility) {
			default:
			case self::SHOW_UNAPPROVED :
				$where.= sprintf(
					' and status = %s and spam = %s',
					$this->app->db->quote(BlorgComment::STATUS_PENDING,
						'integer'),
					$this->app->db->quote(false, 'boolean'));

				break;

			case self::SHOW_ALL :
				$where.= sprintf(' and spam = %s',
					$this->app->db->quote(false, 'boolean'));

				break;

			case self::SHOW_ALL_SPAM :
				// do extra where needed

				break;

			case self::SHOW_SPAM :
				$where.= sprintf(' and spam = %s',
					$this->app->db->quote(true, 'boolean'));

				break;
			}

			$this->where_clause = $where;
		}
		return $this->where_clause;
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();
	}

	// }}}
	// {{{ protected function processActions()

	public function processActions(SwatTableView $view, SwatActions $actions)
	{
		//TODO: make these actions instance aware
		$num = count($view->getSelection());
		$message = null;
		foreach ($view->getSelection() as $item)
			$item_list[] = $this->app->db->quote($item, 'integer');

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Post/CommentDelete');
			$this->app->getPage()->setItems($view->getSelection());
			break;

		case 'approve':
			SwatDB::query($this->app->db, sprintf(
				'update BlorgComment set status = %s, spam = %s
				where id in (%s)',
				$this->app->db->quote(BlorgComment::STATUS_PUBLISHED,
					'integer'),
				$this->app->db->quote(false, 'boolean'),
				implode(',', $item_list)));

			$message = new SwatMessage(sprintf(Blorg::ngettext(
				'One comment has been published.',
				'%s comments have been published.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'deny':
			SwatDB::query($this->app->db, sprintf(
				'update BlorgComment set status = %s, spam = %s
				where id in (%s)',
				$this->app->db->quote(BlorgComment::STATUS_UNPUBLISHED,
					'integer'),
				$this->app->db->quote(false, 'boolean'),
				implode(',', $item_list)));
;
			$message = new SwatMessage(sprintf(Blorg::ngettext(
				'One comment has been unpublished.',
				'%s comments have been unpublished.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'spam':
			SwatDB::query($this->app->db, sprintf(
				'update BlorgComment set status = %s, spam = %s
				where id in (%s)',
				$this->app->db->quote(BlorgComment::STATUS_UNPUBLISHED,
					'integer'),
				$this->app->db->quote(true, 'boolean'),
				implode(',', $item_list)));

			$message = new SwatMessage(sprintf(Blorg::ngettext(
				'One comment has been marked as spam.',
				'%s comments have been marked as spam.', $num),
				SwatString::numberFormat($num)));

			break;
		}

		if ($message !== null)
			$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function clearState()

	/**
	 * Clears a saved search state
	 */
	protected function clearState()
	{
		if ($this->hasState()) {
			unset($this->app->session->{$this->getKey()});
		}
	}

	// }}}
	// {{{ protected function saveState()

	protected function saveState()
	{
		$search_form = $this->ui->getWidget('search_form');
		$search_state = $search_form->getDescendantStates();
		$this->app->session->{$this->getKey()} = $search_state;
	}

	// }}}
	// {{{ protected function loadState()

	/**
	 * Loads a saved search state for this page
	 *
	 * @return boolean true if a saved state exists for this page and false if
	 *                  it does not.
	 *
	 * @see BlorgPostComments::hasState()
	 */
	protected function loadState()
	{
		$return = false;

		$search_form = $this->ui->getWidget('search_form');

		if ($this->hasState()) {
			$search_form->setDescendantStates(
				$this->app->session->{$this->getKey()});

			$return = true;
		}

		return $return;
	}

	// }}}
	// {{{ protected function hasState()

	/**
	 * Checks if this search page has stored search information
	 *
	 * @return boolean true if this page has stored search information and
	 *                  false if it does not.
	 */
	protected function hasState()
	{
		return isset($this->app->session->{$this->getKey()});
	}

	// }}}
	// {{{ protected function getKey()

	protected function getKey()
	{
		return $this->source.'_search_state';
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildSearchForm();
		$this->buildAuthorFlydown();
		$this->buildCommentReplicator();
	}

	// }}}
	// {{{ protected function buildCommentReplicator()

	protected function buildCommentReplicator()
	{
		$comment_replicator = $this->ui->getWidget('comment_replicator');
		foreach ($comment_replicator->replication_ids as $id) {
			$comment_display = $comment_replicator->getWidget('comment', $id);
			$comment_display->setComment($this->comments[$id]);
		}
	}

	// }}}
	// {{{ protected function buildAuthorFlydown()

	protected function buildAuthorFlydown()
	{
		$instance_id = $this->app->getInstanceId();
		$where_clause = sprintf('visible = %s and instance %s %s',
			$this->app->db->quote(true, 'boolean'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$author_flydown = $this->ui->getWidget('search_author');
		$author_flydown->show_blank = true;
		$author_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'BlorgAuthor', 'name', 'id', 'name',
			$where_clause));
	}

	// }}}
	// {{{ protected function buildSearchForm()

	protected function buildSearchForm()
	{
		$form = $this->ui->getWidget('search_form', true);
		$form->action = $this->source;
	}

	// }}}
}

?>
