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
	// {{{ protected function processUploadFile()

	protected function processUploadFile()
	{
		$id   = $this->app->config->blorg->header_image;
		$file = $this->ui->getWidget('header_image');

		if ($this->app->getInstance() === null) {
			$path = '../../files';
		} else {
			$path = '../../files/'.$this->app->getInstance()->shortname;
		}

		if ($file->isUploaded()) {
			$new_file_id = $this->createFile($file, $path);
			$this->removeOldFile($id, $path);
		} elseif ($this->ui->getWidget('remove_image')->value) {
			$this->removeOldFile($id, $path);
			$new_file_id = null;
		} else {
			$new_file_id = $id;
		}

		return $new_file_id;
	}

	// }}}
	// {{{ protected function createFile()

	protected function createFile(SwatFileEntry $file, $path)
	{
		$now = new SwatDate();
		$now->toUTC();

		$class_name = SwatDBClassMap::get('BlorgFile');
		$blorg_file = new $class_name();
		$blorg_file->setDatabase($this->app->db);
		$blorg_file->setFileBase($path);
		$blorg_file->createFileBase($path);

		$blorg_file->description = Blorg::_('This Blorgs Header Image');
		$blorg_file->visible    = true;
		$blorg_file->filename   = $file->getUniqueFileName($path);
		$blorg_file->mime_type  = $file->getMimeType();
		$blorg_file->filesize   = $file->getSize();
		$blorg_file->createdate = $now;
		$blorg_file->instance   = $this->app->getInstanceId();
		$blorg_file->save();

		$file->saveFile($path, $blorg_file->filename);

		return $blorg_file->id;
	}

	// }}}
	// {{{ protected function removeOldFile()

	protected function removeOldFile($id, $path)
	{
		if ($id != '') {
			$class_name = SwatDBClassMap::get('BlorgFile');
			$old_file = new $class_name();
			$old_file->setDatabase($this->app->db);
			$old_file->load(intval($id));

			$old_file->setFileBase($path);
			$old_file->delete();
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'site_title',
			'site_meta_description',
			'blorg_default_comment_status',
			'date_time_zone',
			'analytics_google_account',
			'blorg_akismet_key',
			'blorg_ad_top',
			'blorg_ad_bottom',
			'blorg_ad_post_content',
			'blorg_ad_post_comments',
			'blorg_ad_referers_only',
		));

		$values['blorg_header_image'] = $this->processUploadFile();

		foreach ($values as $key => $value) {
			$name = substr_replace($key, '.', strpos($key, '_'), 1);
			list($section, $title) = explode('.', $name, 2);
			$this->app->config->$section->$title = (string)$value;
		}

		$this->app->config->save();
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
		$this->buildConfigValues();
		$this->buildPreviewImage();
	}

	// }}}
	// {{{ protected function buildConfigValues()

	protected function buildConfigValues()
	{
		$this->ui->getWidget('blorg_default_comment_status')->addOptionsByArray(
			BlorgPost::getCommentStatuses());

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
	// {{{ protected function buildPreviewImage()

	protected function buildPreviewImage()
	{
		$header_id = $this->app->config->blorg->header_image;

		if ($header_id != '') {
			$class = SwatDBClassMap::get('BlorgFile');
			$file = new $class();
			$file->setDatabase($this->app->db);
			$file->load(intval($header_id));

			$path = $file->getRelativeUri('../');
			$this->ui->getWidget('image_preview')->image = $path;
		} else {
			$this->ui->getWidget('current_image')->visible = false;
			$this->ui->getWidget('remove_image')->parent->visible = false;
			$this->ui->getWidget('change_image')->title = Blorg::_('Add Image');
			$this->ui->getWidget('change_image')->open = true;
		}
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
	}

	// }}}
}

?>
