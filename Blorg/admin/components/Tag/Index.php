<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Blorg/dataobjects/BlorgTagWrapper.php';

/**
 * Index page for Tags
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagIndex extends AdminSearch
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Tag/index.xml';

	protected $where_clause;

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
	// {{{ protected function processActions()

	public function processActions(SwatTableView $view, SwatActions $actions)
	{
		$num = count($view->getSelection());
		$message = null;

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage($this->getComponentName().'/Delete');
			$this->app->getPage()->setItems($view->getSelection());
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

		$this->buildComponentTitlesAndLinks();
	}

	// }}}
	// {{{ protected function buildComponentTitlesAndLinks()

	protected function buildComponentTitlesAndLinks()
	{
		$this->ui->getWidget('search_disclosure')->title =
			'Search '.$this->getComponentTitle();

		$this->ui->getWidget('results_frame')->title =
			$this->getComponentTitle();

		$this->ui->getWidget('tag_tool_link')->link =
			$this->getComponentName().'/Edit';

		$title_column = $this->ui->getWidget('index_view')->getColumn('title');
		$title_column->getFirstRenderer()->link =
			$this->getComponentName().'/Details?id=%s';

		$this->ui->getWidget('pager')->link = $this->getComponentName();
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select count(id) from BlorgTag where %s',
			$this->getWhereClause());

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = sprintf(
			'select id, title, shortname
			from BlorgTag
			where %s
			order by %s',
			$this->getWhereClause(),
			$this->getOrderByClause($view, 'title'));

		$tags = SwatDB::query($this->app->db, $sql, 'BlorgTagWrapper');

		if (count($tags) > 0) {
			$this->ui->getWidget('results_frame')->visible = true;
			$this->ui->getWidget('results_message')->content =
				$pager->getResultsMessage(Blorg::_('result'),
					Blorg::_('results'));
		}

		return $tags;
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

			$title = $this->ui->getWidget('search_title')->value;
			if (strlen(trim($title)) > 0) {
				$clause = new AdminSearchClause('title', $title);
				$clause->table = 'BlorgTag';
				$clause->operator =
					$this->ui->getWidget('search_title_operator')->value;

				$where.= $clause->getClause($this->app->db, 'and');
			}

			$this->where_clause = $where;
		}

		return $this->where_clause;
	}

	// }}}
}

?>
