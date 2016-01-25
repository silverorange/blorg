<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Admin/pages/AdminDBOrder.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';

/**
 * Order page for Authors component
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAuthorOrder extends AdminDBOrder
{
	// process phase
	// {{{ protected function saveIndex()

	protected function saveIndex($id, $index)
	{
		SwatDB::updateColumn($this->app->db, 'BlorgAuthor',
			'integer:displayorder',
			$index, 'integer:id', array($id));

		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('update BlorgAuthor set displayorder = %s
			where id = %s and instance %s %s',
			$this->app->db->quote($index, 'integer'),
			$this->app->db->quote($id, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs('authors');
		}
	}

	// }}}
	// {{{ protected function getUpdatedMessage()

	protected function getUpdatedMessage()
	{
		return new SwatMessage(Blorg::_('Author order updated.'));
	}

	// }}}

	// build phase
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
		parent::buildFrame();

		$frame = $this->ui->getWidget('order_frame');
		$frame->title = Blorg::_('Order Authors');
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		$instance_id = $this->app->getInstanceId();
		if ($instance_id === null)
			$where_clause = '1 = 1';
		else
			$where_clause = sprintf('instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$order_widget = $this->ui->getWidget('order');

		$sql = sprintf('select * from BlorgAuthor where %s
			order by displayorder, name',
			$where_clause);

		$authors = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgAuthorWrapper'));

		foreach ($authors as $author)
			$order_widget->addOption($author->id, $author->name);

		$sql = 'select sum(displayorder) from BlorgAuthor where '.$where_clause;
		$sum = SwatDB::queryOne($this->app->db, $sql, 'integer');
		$options_list = $this->ui->getWidget('options');
		$options_list->value = ($sum == 0) ? 'auto' : 'custom';
	}

	// }}}
}

?>
