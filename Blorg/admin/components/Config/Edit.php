<?php

/**
 * Page for editing preferences for a Blörg site
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
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
		'open'      => SiteCommentStatus::OPEN,
		'moderated' => SiteCommentStatus::MODERATED,
		'locked'    => SiteCommentStatus::LOCKED,
		'closed'    => SiteCommentStatus::CLOSED,
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
			'feed_logo',
			'default_comment_status',
			'visual_editor',
		),
		'comment' => array(
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
		$this->initBlorgHeaderImage();
		$this->initBlorgFeedLogo();
	}

	// }}}
	// {{{ protected function initCommentStatuses()

	protected function initCommentStatuses()
	{
		$status = $this->ui->getWidget('blorg_default_comment_status');

		// open
		$option = new SwatOption(SiteCommentStatus::OPEN,
			BlorgPost::getCommentStatusTitle(SiteCommentStatus::OPEN));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can be added by anyone and are immediately visible on '.
			'this post.'));

		// moderated
		$option = new SwatOption(SiteCommentStatus::MODERATED,
			BlorgPost::getCommentStatusTitle(
				SiteCommentStatus::MODERATED));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can be added by anyone but must be approved by a site '.
			'author before being visible on this post.'));

		// locked
		$option = new SwatOption(SiteCommentStatus::LOCKED,
			BlorgPost::getCommentStatusTitle(SiteCommentStatus::LOCKED));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can only be added by an author. Existing comments are '.
			'still visible on this post.'));

		// closed
		$option = new SwatOption(SiteCommentStatus::CLOSED,
			BlorgPost::getCommentStatusTitle(SiteCommentStatus::CLOSED));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can only be added by an author. No comments are visible '.
			'on this post.'));
	}

	// }}}
	// {{{ protected function initBlorgHeaderImage()

	protected function initBlorgHeaderImage()
	{
		$value = $this->app->config->blorg->header_image;
		if ($value != '') {
			$class = SwatDBClassMap::get('BlorgFile');
			$file = new $class();
			$file->setDatabase($this->app->db);
			$file->load(intval($value));
			$this->ui->getWidget('image_preview')->setFile($file);
		}
	}

	// }}}
	// {{{ protected function initBlorgFeedLogo()

	protected function initBlorgFeedLogo()
	{
		$value = $this->app->config->blorg->feed_logo;
		if ($value != '') {
			$class = SwatDBClassMap::get('BlorgFile');
			$file = new $class();
			$file->setDatabase($this->app->db);
			$file->load(intval($value));
			$this->ui->getWidget('logo_preview')->setFile($file);
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$file = $this->ui->getWidget('feed_logo');

		if ($file->isUploaded()) {
			$imagick = new Imagick($file->getTempFileName());
			$width   = $imagick->getImageWidth();
			$height  = $imagick->getImageHeight();
			$text    = Blorg::_('The feed logo must have an aspect ratio of '.
				'2:1. Dimesions given were %s x %s. '.
				'Please resize the logo and try again.');

			if ($width % $height !== 0 || $width / $height !== 2) {
				$message = new SwatMessage(sprintf($text, $height, $width));
				$file->addMessage($message);
			}
		}
	}

	// }}}
	// {{{ protected function saveData()

	protected function saveData()
	{
		$settings = array();

		foreach ($this->setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;
				$saver_method = 'save'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $saver_method)) {
					$this->$saver_method();
				} else {
					$widget = $this->ui->getWidget($field_name);
					$this->app->config->{$section}->{$name} = $widget->value;
				}

				$settings[] = $section.'.'.$name;
			}
		}

		$this->app->config->save($settings);

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
			$file_id = $this->processImage('header_image');
			$this->app->config->blorg->header_image = $file_id;

			$transaction->commit();
		} catch (SwatDBException $e) {
			$transaction->rollback();

			$message = new SwatMessage(Blorg::_(
				'A database error has occured. The header image was not '.
					'saved.'),
				'system-error');

			$this->app->messages->add($message);

			$e->process();
			return false;

		} catch (SwatException $e) {
			$message = new SwatMessage(Blorg::_(
				'An error has occured. The header image was not saved.'),
				'system-error');

			$this->app->messages->add($message);

			$e->process();
			return false;
		}

		return true;
	}

	// }}}
	// {{{ protected function saveBlorgFeedLogo()

	protected function saveBlorgFeedLogo()
	{
		$transaction = new SwatDBTransaction($this->app->db);
		try {
			$file_id = $this->processImage('feed_logo');
			$this->app->config->blorg->feed_logo = $file_id;

			$transaction->commit();
		} catch (SwatDBException $e) {
			$transaction->rollback();

			$message = new SwatMessage(Blorg::_(
				'A database error has occured. The header image was not '.
					'saved.'),
				'system-error');

			$this->app->messages->add($message);

			$e->process();
			return false;

		} catch (SwatException $e) {
			$message = new SwatMessage(Blorg::_(
				'An error has occured. The header image was not saved.'),
				'system-error');

			$this->app->messages->add($message);

			$e->process();
			return false;
		}

		return true;
	}

	// }}}
	// {{{ protected function saveBlorgVisualEditor()

	protected function saveBlorgVisualEditor()
	{
		$widget = $this->ui->getWidget('blorg_visual_editor');
		$value = ($widget->value) ? '1' : '0';
		$this->app->config->blorg->visual_editor = $value;
	}

	// }}}
	// {{{ protected function processImage()

	protected function processImage($widget)
	{
		$id   = $this->app->config->blorg->$widget;
		$file = $this->ui->getWidget($widget);

		if ($file->isUploaded()) {
			if ($this->app->getInstance() === null) {
				$path = '../../files';
			} else {
				$path = '../../files/'.$this->app->getInstance()->shortname;
			}

			$new_file_id = $this->createBlorgFile($file, $path);
			$this->removeBlorgFile($id, $path);
		} else {
			$new_file_id = $id;
		}

		return $new_file_id;
	}

	// }}}
	// {{{ protected function createBlorgFile()

	protected function createBlorgFile(SwatFileEntry $file, $path)
	{
		$now = new SwatDate();
		$now->toUTC();

		$class_name = SwatDBClassMap::get('BlorgFile');
		$blorg_file = new $class_name();
		$blorg_file->setDatabase($this->app->db);
		$blorg_file->setFileBase($path);
		$blorg_file->createFileBase($path);

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
	// {{{ protected function removeBlorgFileImage()

	protected function removeBlorgFile($id, $path)
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
			$widget->value = SiteCommentStatus::OPEN;
			break;

		case 'moderated':
			$widget->value = SiteCommentStatus::MODERATED;
			break;

		case 'locked':
			$widget->value = SiteCommentStatus::LOCKED;
			break;

		case 'closed':
		default:
			$widget->value = SiteCommentStatus::CLOSED;
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
		} else {
			$change_image = $this->ui->getWidget('change_image');
			$change_image->title = Blorg::_('Replace Header Image');
			$change_image->open = false;
		}
	}

	// }}}
	// {{{ protected function loadBlorgFeedLogo()

	protected function loadBlorgFeedLogo()
	{
		$value = $this->app->config->blorg->feed_logo;
		if ($value == '') {
			$this->ui->getWidget('logo_container')->visible = false;
		} else {
			$change_image = $this->ui->getWidget('change_logo');
			$change_image->title = Blorg::_('Replace Feed Logo');
			$change_image->open = false;
		}
	}

	// }}}
}

?>
