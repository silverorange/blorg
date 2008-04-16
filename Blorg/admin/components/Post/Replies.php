<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Blorg/dataobjects/BlorgReplyWrapper.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

/**
 * Page to manage pending replies on Posts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostReplies extends AdminSearch
{
	// {{{ class constants

	const SHOW_UNAPPROVED = 0;
	const SHOW_ALL        = 1;
	const SHOW_ALL_SPAM   = 2;
	const SHOW_SPAM       = 3;

	// }}}
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Post/replies.xml';

	protected $where_clause;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);

		$visibility_options = array(
			self::SHOW_UNAPPROVED => 'Replies Awaiting Approval',
			self::SHOW_ALL        => 'All Replies',
			self::SHOW_ALL_SPAM   => 'All Replies, including Spam',
			self::SHOW_SPAM       => 'Spam Only',
		);

		$visibility = $this->ui->getWidget('search_visibility');
		$visibility->addOptionsByArray($visibility_options);
		$visibility->value = self::SHOW_UNAPPROVED;
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

	public function processActions(SwatTableView $view, SwatActions $actions)
	{
		//TODO: make these actions instance aware
		$num = count($view->getSelection());
		$message = null;
		foreach ($view->getSelection() as $item)
			$item_list[] = $this->app->db->quote($item, 'integer');

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Post/ReplyDelete');
			$this->app->getPage()->setItems($view->getSelection());
			break;

		case 'approve':
			SwatDB::query($this->app->db, sprintf(
				'update BlorgReply set status = %s, spam = %s
				where id in (%s)',
				$this->app->db->quote(BlorgReply::STATUS_PUBLISHED, 'integer'),
				$this->app->db->quote(false, 'boolean'),
				implode(',', $item_list)));

			$message = new SwatMessage(sprintf(Blorg::ngettext(
				'One reply has been published.',
				'%s replies have been published.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'deny':
			SwatDB::query($this->app->db, sprintf(
				'update BlorgReply set status = %s, spam = %s
				where id in (%s)',
				$this->app->db->quote(BlorgReply::STATUS_UNPUBLISHED,
					'integer'),
				$this->app->db->quote(false, 'boolean'),
				implode(',', $item_list)));
;
			$message = new SwatMessage(sprintf(Blorg::ngettext(
				'One reply has been unpublished.',
				'%s replies have been unpublished.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'spam':
			SwatDB::query($this->app->db, sprintf(
				'update BlorgReply set status = %s, spam = %s
				where id in (%s)',
				$this->app->db->quote(BlorgReply::STATUS_UNPUBLISHED,
					'integer'),
				$this->app->db->quote(true, 'boolean'),
				implode(',', $item_list)));

			$message = new SwatMessage(sprintf(Blorg::ngettext(
				'One reply has been marked as spam.',
				'%s replies have been marked as spam.', $num),
				SwatString::numberFormat($num)));

			break;
		}

		if ($message !== null)
			$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$author_flydown = $this->ui->getWidget('search_author');
		$author_flydown->show_blank = true;
		// TODO: update this once show is moved into the AdminUserInstanceBindingTable
		$author_flydown->addOptionsByArray(SwatDB::getOptionArray(
			$this->app->db, 'AdminUser', 'name', 'id', 'name',
			sprintf('show = %s', $this->app->db->quote(true, 'boolean'))));

	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select count(id) from BlorgReply where %s',
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = sprintf(
			'select id, fullname, author, bodytext, createdate, status, post,
				spam
			from BlorgReply
			where %s
			order by %s',
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'createdate desc'));
//echo $sql;
		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$replies = SwatDB::query($this->app->db, $sql, 'BlorgReplyWrapper');

		if (count($replies) > 0) {
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(Blorg::_('result'),
					Blorg::_('results'));
		}

		$current_date = null;
		$store = new SwatTableStore();
		foreach ($replies as $reply) {

			if ($current_date === null ||
				$reply->createdate->getDay() != $current_date->getDay() ||
				$reply->createdate->getMonth() != $current_date->getMonth() ||
				$reply->createdate->getYear() != $current_date->getYear()) {

				$current_date = clone $reply->createdate;
			} 

			$ds = new SwatDetailsStore($reply);
			$ds->reply_date_day = $current_date;
			$ds->title = $reply->post->getTitle();

			//TODO: distinguish authors somehow
			if ($reply->author !== null)
				$ds->fullname = $reply->author->name;

			$ds->bodytext = SwatString::condense(
				SwatString::ellipsizeRight($reply->bodytext, 500));

			$store->add($ds);
		}

		return $store;
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
			if (strlen(trim($keywords)) > 0) {
				$clause = new AdminSearchClause('bodytext', $keywords);
				$clause->table = 'BlorgReply';
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$where.= $clause->getClause($this->app->db, 'and');
			}

			$author_clause = new AdminSearchClause('integer:author',
				$this->ui->getWidget('search_author')->value);

			$fullname = $this->ui->getWidget('search_fullname')->value;
			if (strlen(trim($fullname)) > 0) {
				$fullname_clause = new AdminSearchClause('fullname', $fullname);
				$fullname_clause->table = 'BlorgReply';
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
				case self::SHOW_UNAPPROVED :
					$where.= sprintf(
						' and status = %s and spam = %s',
						$this->app->db->quote(BlorgReply::STATUS_PENDING,
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
}

?>
