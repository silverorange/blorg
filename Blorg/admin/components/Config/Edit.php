<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

/**
 * Page for editing site instance settings
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgConfigEdit extends AdminDBEdit
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Config/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);

		$this->id = $this->app->getInstanceId();
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'site_title',
			//'site_banner_image',
			'site_meta_description',
			'blorg_default_comment_status',
			'date_time_zone',
			'analytics_google_account',
			'blorg_akismet_key',
		));

		foreach ($values as $key => $value) {
			$name = substr_replace($key, '.', strpos($key, '_'), 1);

			$sql = sprintf('delete from InstanceConfigSetting where name = %s',
				$this->app->db->quote($name, 'text'));

			SwatDB::exec($this->app->db, $sql);

			if ($value === null)
				continue;

			$sql = sprintf('insert into InstanceConfigSetting
				(name, value, instance) values (%s, %s, %s)',
				$this->app->db->quote($name, 'text'),
				$this->app->db->quote($value, 'text'),
				$this->app->db->quote($this->app->getInstanceId(), 'integer'));

			SwatDB::exec($this->app->db, $sql);

		}

		$message = new SwatMessage(
			Blorg::_('Your config settings have been saved.'));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget(
			'blorg_default_comment_status')->addOptionsByArray(
				BlorgPost::getCommentStatuses());

	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$sql = sprintf('select * from InstanceConfigSetting
			where instance = %s',
			$this->app->db->quote($this->app->getInstanceId(), 'integer'));

		$rs = SwatDB::query($this->app->db, $sql);

		$values = array();
		foreach ($rs as $row)
			$values[str_replace('.', '_', $row->name)] =
				is_numeric($row->value) ? intval($row->value) : $row->value;

		$this->ui->setValues($values);
	}

	// }}}
}

?>
