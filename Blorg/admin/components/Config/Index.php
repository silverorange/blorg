<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Swat/SwatNavBar.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/pages/AdminIndex.php';
require_once 'Blorg/dataobjects/BlorgTag.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

/**
 * Details page for tags
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgConfigIndex extends AdminIndex
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Config/index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$sql = sprintf('select * from InstanceConfigSetting
			where instance = %s',
			$this->app->db->quote($this->app->getInstanceId(), 'integer'));

		$rs = SwatDB::query($this->app->db, $sql);

		$values = array();

		foreach ($rs as $row)
			$values[$row->name] = $row->value;

		$view = $this->ui->getWidget('site_view');

		if (array_key_exists('site.title', $values)) {
			$renderer = $view->getField(
				'site_title')->getFirstRenderer();

			$renderer->text = $values['site.title'];
		}

		if (array_key_exists('blorg.header_image', $values)) {
			$renderer = $view->getField(
				'blorg_header_image')->getFirstRenderer();

			$class = SwatDBClassMap::get('BlorgFile');
			$file = new $class();
			$file->setDatabase($this->app->db);
			$file->load(intval($values['blorg.header_image']));

			$path = $file->image->getUri('header', '../');
			$renderer->image = $path;
		}

		if (array_key_exists('site.meta_description', $values)) {
			$renderer = $view->getField(
				'site_meta_description')->getFirstRenderer();

			$renderer->text = $values['site.meta_description'];
		}

		if (array_key_exists('blorg.akismet_key', $values)) {
			$renderer = $view->getField(
				'blorg_akismet_key')->getFirstRenderer();

			$renderer->text = $values['blorg.akismet_key'];
		}

		if (array_key_exists('blorg.default_comment_status', $values)) {
			$renderer = $view->getField(
				'blorg_default_comment_status')->getFirstRenderer();

			$renderer->text = BlorgPost::getCommentStatusTitle(
				$values['blorg.default_comment_status']);
		}

		if (array_key_exists('date.time_zone', $values)) {
			$renderer = $view->getField(
				'date_time_zone')->getFirstRenderer();

			$renderer->text = $values['date.time_zone'];
		}

		if (array_key_exists('analytics.google_account', $values)) {
			$renderer = $view->getField(
				'analytics_google_account')->getFirstRenderer();

			$renderer->text = $values['analytics.google_account'];
		}
	}

	// }}}
	// {{{ protected function getTableModel()

	protected function getTableModel(SwatView $view)
	{
		return null;
	}

	// }}}
}

?>
