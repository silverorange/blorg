<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminEdit.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/admin/BlorgCommentStatusSlider.php';
require_once dirname(__FILE__).'/include/BlorgHeaderImageDisplay.php';

/**
 * Page for editing preferences for a Blörg site
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgConfigEdit extends AdminEdit
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Blorg/admin/components/Config/edit.xml';

	/**
	 * @var array
	 */
	protected $comment_status_map = array(
		'open'      => BlorgPost::COMMENT_STATUS_OPEN,
		'moderated' => BlorgPost::COMMENT_STATUS_MODERATED,
		'locked'    => BlorgPost::COMMENT_STATUS_LOCKED,
		'closed'    => BlorgPost::COMMENT_STATUS_CLOSED,
	);

	/**
	 * @var array
	 */
	protected $setting_keys = array(
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

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initCommentStatuses();
	}

	// }}}
	// {{{ protected function initCommentStatuses()

	protected function initCommentStatuses()
	{
		$status = $this->ui->getWidget('blorg_default_comment_status');

		// open
		$option = new SwatOption(BlorgPost::COMMENT_STATUS_OPEN,
			BlorgPost::getCommentStatusTitle(BlorgPost::COMMENT_STATUS_OPEN));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can be added by anyone and are immediately visible on '.
			'this post.'));

		// moderated
		$option = new SwatOption(BlorgPost::COMMENT_STATUS_MODERATED,
			BlorgPost::getCommentStatusTitle(
				BlorgPost::COMMENT_STATUS_MODERATED));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can be added by anyone but must be approved by a site '.
			'author before being visible on this post.'));

		// locked
		$option = new SwatOption(BlorgPost::COMMENT_STATUS_LOCKED,
			BlorgPost::getCommentStatusTitle(BlorgPost::COMMENT_STATUS_LOCKED));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can only be added by an author. Existing comments are '.
			'still visible on this post.'));

		// closed
		$option = new SwatOption(BlorgPost::COMMENT_STATUS_CLOSED,
			BlorgPost::getCommentStatusTitle(BlorgPost::COMMENT_STATUS_CLOSED));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can only be added by an author. No comments are visible '.
			'on this post.'));
	}

	// }}}

	// process phase
	// {{{ protected function saveData()

	protected function saveData()
	{
		foreach ($this->setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;
				$saver_method = 'save'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $saver_method)) {
					$this->$saver_method();
				} else {
					$widget = $this->ui->getWidget($field_name);
					$this->app->config->$section->$name = $widget->value;
				}
			}
		}

		$this->app->config->save();

		$message = new SwatMessage(
			Blorg::_('Preferences have been saved.'));

		$this->app->messages->add($message);

		return true;
	}

	// }}}
	// {{{ protected function saveBlorgDefaultCommentStatus()

	protected function saveBlorgDefaultCommentStatus()
	{
		$widget = $this->ui->getWidget('blorg_default_comment_status');
		$value = array_search($widget->value, $this->comment_status_map, true);
		$this->app->config->blorg->default_comment_status = $value;
	}

	// }}}
	// {{{ protected function saveBlorgHeaderImage()

	protected function saveBlorgHeaderImage()
	{
		$transaction = new SwatDBTransaction($this->app->db);
		try {
			$file_id = $this->processHeaderImage();
			$this->app->config->blorg->header_image = $file_id;

			$transaction->commit();
		} catch (SwatDBException $e) {
			$transaction->rollback();

			$message = new SwatMessage(Blorg::_(
				'A database error has occured. The header image was not '.
					'saved.'),
				SwatMessage::SYSTEM_ERROR);

			$this->app->messages->add($message);

			$e->process();
			return false;

		} catch (SwatException $e) {
			$message = new SwatMessage(Blorg::_(
				'An error has occured. The header image was not saved.'),
				SwatMessage::SYSTEM_ERROR);

			$this->app->messages->add($message);

			$e->process();
			return false;
		}

		return true;
	}

	// }}}
	// {{{ protected function processHeaderImage()

	protected function processHeaderImage()
	{
		$id   = $this->app->config->blorg->header_image;
		$file = $this->ui->getWidget('header_image');

		if ($file->isUploaded()) {
			if ($this->app->getInstance() === null) {
				$path = '../../files';
			} else {
				$path = '../../files/'.$this->app->getInstance()->shortname;
			}

			$new_file_id = $this->createHeaderImage($file, $path);
			$this->removeOldHeaderImage($id, $path);
		} else {
			$new_file_id = $id;
		}

		return $new_file_id;
	}

	// }}}
	// {{{ protected function createHeaderImage()

	protected function createHeaderImage(SwatFileEntry $file, $path)
	{
		$now = new SwatDate();
		$now->toUTC();

		$class_name = SwatDBClassMap::get('BlorgFile');
		$blorg_file = new $class_name();
		$blorg_file->setDatabase($this->app->db);
		$blorg_file->setFileBase($path);
		$blorg_file->createFileBase($path);

		$blorg_file->description = Blorg::_('Header Image');
		$blorg_file->visible     = false;
		$blorg_file->filename    = $file->getUniqueFileName($path);
		$blorg_file->mime_type   = $file->getMimeType();
		$blorg_file->filesize    = $file->getSize();
		$blorg_file->createdate  = $now;
		$blorg_file->instance    = $this->app->getInstanceId();
		$blorg_file->save();

		$file->saveFile($path, $blorg_file->filename);

		return $blorg_file->id;
	}

	// }}}
	// {{{ protected function removeOldHeaderImage()

	protected function removeOldHeaderImage($id, $path)
	{
		if ($id != '') {
			$class_name = SwatDBClassMap::get('BlorgFile');
			$old_file = new $class_name();
			$old_file->setDatabase($this->app->db);
			$old_file->load(intval($id), $this->app->getInstance());
			$old_file->setFileBase($path);
			$old_file->delete();
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
	}

	// }}}
	// {{{ protected function buildButton()

	protected function buildButton()
	{
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$form = $this->ui->getWidget('edit_form');

		if (!$form->isProcessed())
			$this->loadData();

		$form->action = $this->source;
		$form->autofocus = true;

		if ($form->getHiddenField(self::RELOCATE_URL_FIELD) === null) {
			$url = $this->getRefererURL();
			$form->addHiddenField(self::RELOCATE_URL_FIELD, $url);
		}
	}
	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$this->navbar->popEntry();
		$this->navbar->createEntry(Blorg::_('Edit Preferences'));
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		foreach ($this->setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;
				$loader_method = 'load'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $loader_method)) {
					$this->$loader_method();
				} else {
					$widget = $this->ui->getWidget($field_name);
					$widget->value = $this->app->config->$section->$name;
				}
			}
		}

		return true;
	}

	// }}}
	// {{{ protected function loadBlorgDefaultCommentStatus()

	protected function loadBlorgDefaultCommentStatus()
	{
		$value  = $this->app->config->blorg->default_comment_status;
		$widget = $this->ui->getWidget('blorg_default_comment_status');

		switch ($value) {
		case 'open':
			$widget->value = BlorgPost::COMMENT_STATUS_OPEN;
			break;

		case 'moderated':
			$widget->value = BlorgPost::COMMENT_STATUS_MODERATED;
			break;

		case 'locked':
			$widget->value = BlorgPost::COMMENT_STATUS_LOCKED;
			break;

		case 'closed':
		default:
			$widget->value = BlorgPost::COMMENT_STATUS_CLOSED;
			break;
		}
	}

	// }}}
	// {{{ protected function loadBlorgHeaderImage()

	protected function loadBlorgHeaderImage()
	{
		$value = $this->app->config->blorg->header_image;
		if ($value == '') {
			$this->ui->getWidget('image_container')->visible = false;

			$change_image = $this->ui->getWidget('change_image');
			$change_image->title = Blorg::_('Add Header Image');
			$change_image->open = true;
		} else {
			$class = SwatDBClassMap::get('BlorgFile');
			$file = new $class();
			$file->setDatabase($this->app->db);
			$file->load(intval($value));
			$this->ui->getWidget('image_preview')->setFile($file);
		}
	}

	// }}}
}

?>
