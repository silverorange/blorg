<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Site/SiteNateGoFulltextSearchEngine.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Index page for Posts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostIndex extends AdminSearch
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Post/index.xml';

	/**
	 * @var NateGoFulltextSearchResult
	 */
	protected $fulltext_result;

	protected $join_clause;
	protected $where_clause;
	protected $order_by_clause;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$this->ui->getWidget('pager')->process();

		if ($this->hasSearch())
			$this->ui->getWidget('search_frame')->open = true;
	}

	// }}}
	// {{{ protected function processActions()

	public function processActions(SwatTableView $view, SwatActions $actions)
	{
		$instance_id = $this->app->instance->getId();
		$num = count($view->getSelection());
		$message = null;
		foreach ($view->getSelection() as $item)
			$item_list[] = $this->app->db->quote($item, 'integer');

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage($this->getComponentName().'/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;

		case 'enable':
			SwatDB::query($this->app->db, sprintf(
				'update BlorgPost set enabled = %s
				where instance %s %s and id in (%s)',
				$this->app->db->quote(true, 'boolean'),
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'),
				implode(',', $item_list)));

			$message = new SwatMessage(sprintf(Blorg::ngettext(
				'One post has been enabled.',
				'%s posts have been enabled.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'disable':
			SwatDB::query($this->app->db, sprintf(
				'update BlorgPost set enabled = %s
				where instance %s %s and id in (%s)',
				$this->app->db->quote(false, 'boolean'),
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'),
				implode(',', $item_list)));

			$message = new SwatMessage(sprintf(Blorg::ngettext(
				'One post has been disabled.',
				'%s posts have been disabled.', $num),
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
		$this->searchPosts();

		$sql = sprintf('select count(id) from BlorgPost %s where %s',
			$this->getJoinClause(),
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = sprintf(
			'select id, title, shortname, post_date, enabled,
				bodytext
			from BlorgPost
			%s
			where %s
			order by %s',
			$this->getJoinClause(),
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'post_date desc, title'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$posts = SwatDB::query($this->app->db, $sql, 'BlorgPostWrapper');

		$current_date = null;
		$store = new SwatTableStore();
		foreach ($posts as $post) {

			if ($current_date === null ||
				$post->post_date->getMonth() != $current_date->getMonth() ||
				$post->post_date->getYear() != $current_date->getYear()) {

				$current_date = clone $post->post_date;
				$current_date->setDay(1);
				$current_date->setHour(0);
				$current_date->setMinute(0);
				$current_date->setSecond(0);
			}

			$ds = new SwatDetailsStore($post);
			$ds->title = $post->getTitle();
			$ds->post_date_month = $current_date;

			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function searchPosts()

	protected function searchPosts()
	{
		$keywords = $this->ui->getWidget('search_keywords')->value;
		if (strlen(trim($keywords)) == 0 || $this->getSearchType() === null) {
			$this->fulltext_result = null;
		} else {
			$fulltext_engine = new SiteNateGoFulltextSearchEngine(
				$this->app->db);

			$fulltext_engine->setTypes(array(
				$this->getSearchType(),
			));

			$this->fulltext_result = $fulltext_engine->search($keywords);
		}
	}

	// }}}
	// {{{ protected function getSearchType()

	/**
	 * Gets the search type for BlorgPosts for this web-application
	 *
	 * @return integer the search type for BlorgPosts for this web-application.
	 */
	protected function getSearchType()
	{
		//TODO: set up NateGoSearch
		return null;
	}

	// }}}
	// {{{ protected function getJoinClause()

	protected function getJoinClause()
	{
		if ($this->join_clause === null) {
			if ($this->fulltext_result === null) {
				$this->join_clause = '';
			} else {
				$this->join_clause = $this->fulltext_result->getJoinClause(
					'id', $this->getSearchType());
			}
		}

		return $this->join_clause;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$instance_id = $this->app->instance->getId();

			$where = sprintf('instance %s %s ',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));
		
			// keywords are included in the where clause if fulltext searching
			// is turned off
			$keywords = $this->ui->getWidget('search_keywords')->value;
			if (strlen(trim($keywords)) > 0 &&
				$this->getSearchType() === null) {

				$where.= ' and ( ';

				$clause = new AdminSearchClause('title', $keywords);
				$clause->table = 'BlorgPost';
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$where.= $clause->getClause($this->app->db, '');

				$clause = new AdminSearchClause('bodytext', $keywords);
				$clause->table = 'BlorgPost';
				$clause->operator = AdminSearchClause::OP_CONTAINS;
				$where.= $clause->getClause($this->app->db, 'or');

				$where.= ') ';
			}

			$author_clause = new AdminSearchClause('integer:author',
				$this->ui->getWidget('search_author')->value);

			$enabled_clause = new AdminSearchClause('boolean:enabled',
				$this->ui->getWidget('search_enabled')->value);

			$where.= $author_clause->getClause($this->app->db);
			$where.= $enabled_clause->getClause($this->app->db);

			$this->where_clause = $where;
		}

		return $this->where_clause;
	}

	// }}}
	// {{{ protected function getOrderByClause()

	protected function getOrderByClause($view, $default_orderby)
	{
		if ($this->order_by_clause === null) {
			if ($this->fulltext_result === null) {
				$order_by_clause = $default_orderby;
			} else {
				// AdminSearch expects no 'order by' in returned value.
				$order_by_clause = str_replace('order by ', '',
					$this->fulltext_result->getOrderByClause($default_orderby));
			}

			$this->order_by_clause =
				parent::getOrderByClause($view, $order_by_clause);
		}

		return $this->order_by_clause;
	}

	// }}}
	// {{{ private function hasSearch()

	private function hasSearch()
	{
		$keywords = trim($this->ui->getWidget('search_keywords')->value);
		$author   = $this->ui->getWidget('search_author')->value;
		$enabled  = $this->ui->getWidget('search_enabled')->value;

		return (strlen($keywords) > 0 || $author != null || $enabled != null) ?
			true : false;
	}

	// }}}
}

?>
