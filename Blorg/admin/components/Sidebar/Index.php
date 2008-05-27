<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Blorg/BlorgGadgetFactory.php';
require_once 'Blorg/dataobjects/BlorgGadgetInstanceWrapper.php';

/**
 * Index page for sidebar gadgets
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgSidebarIndex extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Sidebar/index.xml';

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
		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage('Sidebar/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;
		}
	}

	// }}}

	// build phase
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$sql = sprintf('select * from BlorgGadgetInstance
			where instance %s %s
			order by %s',
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'),
			$this->getOrderByClause($view, 'displayorder'));

		$gadget_instances = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgGadgetInstanceWrapper'));

		$available = BlorgGadgetFactory::getAvailable($this->app);

		$store = new SwatTableStore();
		foreach ($gadget_instances as $gadget_instance) {
			$gadget = BlorgGadgetFactory::get($this->app, $gadget_instance);
			$ds = new SwatDetailsStore($gadget_instance);
			$ds->title = $gadget->getTitle();
			$store->add($ds);
		}

		return $store;
	}

	// }}}
}

?>
