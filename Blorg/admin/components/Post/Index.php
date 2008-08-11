<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Site/SiteNateGoFulltextSearchEngine.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';
require_once 'Blorg/admin/BlorgTagEntry.php';

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

		// setup tag entry control
		$this->ui->getWidget('tags')->setApplication($this->app);
		$this->ui->getWidget('tags')->setAllTags();
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
		$instance_id = $this->app->getInstanceId();
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
			$num = SwatDB::exec($this->app->db, sprintf(
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
			$num = SwatDB::exec($this->app->db, sprintf(
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

		case 'tags_action':
			$tag_array = $this->ui->getWidget('tags')->getSelectedTagArray();
			if (count($tag_array) > 0) {
				$posts = $this->getPostsFromSelection($view->getSelection());
				foreach ($posts as $post)
					$post->addTagsByShortname($tag_array,
						$this->app->getInstance());

				$num = count($view->getSelection());
				if (count($tag_array) > 1) {
					$message = new SwatMessage(sprintf(Blorg::ngettext(
						'%s tags have been added to one post.',
						'%s tags have been added to %s posts.', $num),
						SwatString::numberFormat(count($tag_array)),
						SwatString::numberFormat($num)));
				} else {
					$message = new SwatMessage(sprintf(Blorg::ngettext(
						'A tag has been added to one post.',
						'A tag has been added to %s posts.', $num),
						SwatString::numberFormat($num)));
				}
			}

			break;
		}

		if ($message !== null)
			$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function getPostsFromSelection()

	protected function getPostsFromSelection(SwatViewSelection $selection)
	{
		$ids = array();
		foreach ($selection as $id)
			$ids[] = $id;

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select BlorgPost.* from BlorgPost
			where id in (%s) and instance %s %s',
			$this->app->db->datatype->implodeArray($ids, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		return SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgPostWrapper'));
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

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
			'select id, title, shortname, publish_date, enabled,
				bodytext
			from BlorgPost
			%s
			where %s
			order by %s',
			$this->getJoinClause(),
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'publish_date desc, title'));

		$this->app->db->setLimit($pager->page_size, $pager->current_record);
		$posts = SwatDB::query($this->app->db, $sql, 'BlorgPostWrapper');

		$current_date = null;
		$store = new SwatTableStore();
		foreach ($posts as $post) {

			if ($current_date === null ||
				$post->publish_date->getMonth() != $current_date->getMonth() ||
				$post->publish_date->getYear() != $current_date->getYear()) {

				$current_date = clone $post->publish_date;
				$current_date->setDay(1);
				$current_date->setHour(0);
				$current_date->setMinute(0);
				$current_date->setSecond(0);
			}

			$ds = new SwatDetailsStore($post);
			$ds->title = $post->getTitle();
			$ds->publish_date_month = $current_date;

			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function searchPosts()

	protected function searchPosts()
	{
		$keywords = $this->ui->getWidget('search_keywords')->value;
		if (trim($keywords) == '' || $this->getSearchType() === null) {
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
			$instance_id = $this->app->getInstanceId();

			// default where clause
			$where = sprintf('instance %s %s ',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

			// keywords are included in the where clause if fulltext searching
			// is turned off
			$keywords = $this->ui->getWidget('search_keywords')->value;
			if (trim($keywords) != '' && $this->getSearchType() === null) {
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

			// author
			$author_clause = new AdminSearchClause('integer:author',
				$this->ui->getWidget('search_author')->value);

			$where.= $author_clause->getClause($this->app->db);

			// enabled
			$enabled_clause = new AdminSearchClause('boolean:enabled',
				$this->ui->getWidget('search_enabled')->value);

			$where.= $enabled_clause->getClause($this->app->db);

			// date range gt
			$search_date_gt = $this->ui->getWidget('search_publish_date_gt');
			if ($search_date_gt->value !== null) {
				// clone so the date displayed will stay the same
				$date_gt = clone $search_date_gt->value;
				$date_gt->setTZ($this->app->default_time_zone);
				$date_gt->toUTC();

				$clause = new AdminSearchClause('date:publish_date');
				$clause->table = 'BlorgPost';
				$clause->value = $date_gt->getDate();
				$clause->operator = AdminSearchClause::OP_GT;
				$where.= $clause->getClause($this->app->db);
			}

			// date range lt
			$search_date_lt = $this->ui->getWidget('search_publish_date_lt');
			if ($search_date_lt->value !== null) {
				// clone so the date displayed will stay the same
				$date_lt = clone $search_date_lt->value;
				$date_lt->setTZ($this->app->default_time_zone);
				$date_lt->toUTC();

				$clause = new AdminSearchClause('date:publish_date');
				$clause->table = 'BlorgPost';
				$clause->value = $date_lt->getDate();
				$clause->operator = AdminSearchClause::OP_LT;
				$where.= $clause->getClause($this->app->db);
			}

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

		return ($keywords != '' || $author != null || $enabled != null);
	}

	// }}}
}

?>
