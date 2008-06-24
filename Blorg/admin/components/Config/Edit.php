<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminEdit.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once dirname(__FILE__).'/include/BlorgHeaderImageDisplay.php';

/**
 * Page for editing site instance settings
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgConfigEdit extends AdminEdit
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
	// {{{ protected function saveData()

	protected function saveData()
	{
		$values = $this->ui->getValues(array(
			'site_title',
			'site_tagline',
			'site_meta_description',
			'blorg_default_comment_status',
			'date_time_zone',
			'analytics_google_account',
			'blorg_akismet_key',
		));

		try {
			$transaction = new SwatDBTransaction($this->app->db);
			$values['blorg_header_image'] = $this->processUploadFile();
			$transaction->commit();

		} catch (SwatDBException $e) {
			$transaction->rollback();

			$message = new SwatMessage(Admin::_(
				'A database error has occured. The item was not saved.'),
				SwatMessage::SYSTEM_ERROR);

			$this->app->messages->add($message);

			$e->process();
			return false;

		} catch (SwatException $e) {
			$message = new SwatMessage(Admin::_(
				'An error has occured. The item was not saved.'),
				SwatMessage::SYSTEM_ERROR);

			$this->app->messages->add($message);

			$e->process();
			return false;
		}

		foreach ($values as $key => $value) {
			$name = substr_replace($key, '.', strpos($key, '_'), 1);
			list($section, $title) = explode('.', $name, 2);
			$this->app->config->$section->$title = (string)$value;
		}

		$this->app->config->save();
		$message = new SwatMessage(
			Blorg::_('Your site settings have been saved.'));

		$this->app->messages->add($message);

		return true;
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->ui->getWidget('blorg_default_comment_status')->addOptionsByArray(
			BlorgPost::getCommentStatuses());

		$this->buildConfigValues();
		$this->buildPreviewImage();
	}

	// }}}
	// {{{ protected function buildConfigValues()

	protected function buildConfigValues()
	{
		$values = array();
		$setting_keys = array(
			'site' => array(
				'title',
				'tagline',
				'meta_description',
			),
			'blorg' => array(
				'header_image',
				'default_comment_status',
				'akismet_key',
			),
			'date' => array(
				'time_zone',
			),
			'analytics' => array(
				'google_account',
			),
		);

		foreach ($setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;
				$values[$field_name] = $this->app->config->$section->$name;
			}
		}

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
			$this->ui->getWidget('image_preview')->file = $file;
		} else {
			$this->ui->getWidget('image_container')->visible = false;
			$this->ui->getWidget('change_image')->title =
				Blorg::_('Add Header Image');

			$this->ui->getWidget('change_image')->open = true;
		}
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
		$frame = $this->ui->getWidget('edit_frame');
		$frame->title = Blorg::_('Edit Site Settings');
	}

	// }}}
	// {{{ protected function buildButton()

	protected function buildButton()
	{
		$button = $this->ui->getWidget('submit_button');
		$button->setFromStock('apply');
	}

	// }}}
	// {{{ protected function buildFrame()

	protected function buildNavBar()
	{
		$this->navbar->createEntry(Blorg::_('Edit Site Settings'));
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		return true;
	}

	// }}}
}

?>
