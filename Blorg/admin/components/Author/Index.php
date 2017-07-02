<?php

/**
 * Index page for Authors
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
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

	public function processActions(SwatView $view, SwatActions $actions)
	{
		$num = count($view->getSelection());
		$message = null;
		$items = SwatDB::implodeSelection($this->app->db,
			$view->getSelection(), 'integer');

		switch ($actions->selected->id) {
		case 'delete':
			$this->app->replacePage($this->getComponentName().'/Delete');
			$this->app->getPage()->setItems($view->getSelection());
			break;

		case 'enable':
			$instance_id = $this->app->getInstanceId();
			$num = SwatDB::exec($this->app->db, sprintf(
				'update BlorgAuthor set visible = %s
				where instance %s %s and id in (%s)',
				$this->app->db->quote(true, 'boolean'),
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'),
				$items));

			if ($num > 0 && isset($this->app->memcache)) {
				$this->app->memcache->flushNs('authors');
			}

			$message = new SwatMessage(sprintf(Blorg::ngettext(
				'One author has been shown on site.',
				'%s authors have been shown on site.', $num),
				SwatString::numberFormat($num)));

			break;

		case 'disable':
			$instance_id = $this->app->getInstanceId();
			$num = SwatDB::exec($this->app->db, sprintf(
				'update BlorgAuthor set visible = %s
				where instance %s %s and id in (%s)',
				$this->app->db->quote(false, 'boolean'),
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'),
				$items));

			if ($num > 0 && isset($this->app->memcache)) {
				$this->app->memcache->flushNs('authors');
			}

			$message = new SwatMessage(sprintf(Blorg::ngettext(
				'One author has been hidden on site.',
				'%s authors have been hidden on site.', $num),
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

		$authors = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgAuthorWrapper'));

		if (count($authors) < 2) {
			$this->ui->getWidget('author_order_link')->sensitive = false;
		}

		return $authors;
	}

	// }}}
}

?>
