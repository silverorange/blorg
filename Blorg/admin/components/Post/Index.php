<?php

require_once 'Admin/pages/AdminIndex.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatTableStore.php';
require_once 'Swat/SwatDetailsStore.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Index page for Posts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostIndex extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Post/index.xml';

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
			$this->app->replacePage('Post/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;

		case 'enable':
			SwatDB::query($this->app->db, sprintf(
				'update BlorgPost set enabled = %s
				where instance %s %s and id in (%s)',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'),
				$this->app->db->quote(true, 'boolean'),
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
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'),
				$this->app->db->quote(false, 'boolean'),
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
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		$instance_id = $this->app->instance->getId();

		$sql = sprintf('select count(id) from BlorgPost where instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$pager = $this->ui->getWidget('pager');
		$pager->total_records = SwatDB::queryOne($this->app->db, $sql);

		$sql = sprintf(
			'select id, title, shortname, post_date, enabled,
				bodytext
			from BlorgPost
				where instance %s %s
				order by %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
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
}

?>
