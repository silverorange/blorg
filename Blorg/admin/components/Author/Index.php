<?php

require_once 'Admin/pages/AdminSearch.php';
require_once 'Admin/AdminSearchClause.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';

/**
 * Index page for Authors
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAuthorIndex extends AdminSearch
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Author/index.xml';

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
		$this->ui->getWidget('index_frame')->title =
			$this->getComponentTitle();

		$this->ui->getWidget('author_tool_link')->link =
			$this->getComponentName().'/Edit';

		$this->ui->getWidget('author_order_link')->link =
			$this->getComponentName().'/Order';

		$title_column = $this->ui->getWidget('index_view')->getColumn('name');
		$title_column->getFirstRenderer()->link =
			$this->getComponentName().'/Edit?id=%s';
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$instance_id = $this->app->getInstanceId();
		$sql = sprintf(
			'select id, name, visible, email, shortname
			from BlorgAuthor
			where instance %s %s
			order by %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->getOrderByClause($view, 'displayorder, name'));

		return SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgAuthorWrapper'));
	}

	// }}}
}

?>
