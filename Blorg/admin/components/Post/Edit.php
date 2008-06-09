<?php

require_once 'Swat/SwatOption.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Site/exceptions/SiteInvalidImageException.php';
require_once 'NateGoSearch/NateGoSearch.php';
require_once 'Blorg/BlorgWeblogsDotComPinger.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/dataobjects/BlorgFile.php';
require_once 'Blorg/dataobjects/BlorgFileWrapper.php';
require_once 'Blorg/dataobjects/BlorgFileImage.php';
require_once 'Blorg/admin/BlorgCommentStatusSlider.php';
require_once 'Blorg/admin/BlorgTagEntry.php';
require_once dirname(__FILE__).'/include/BlorgFileAttachControl.php';
require_once dirname(__FILE__).'/include/BlorgFileDeleteControl.php';
require_once dirname(__FILE__).'/include/BlorgPublishRadioTable.php';
require_once dirname(__FILE__).'/include/BlorgMarkupView.php';

/**
 * Page for editing Posts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var BlorgPost
	 */
	protected $post;

	protected $ui_xml = 'Blorg/admin/components/Post/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		$this->initPost();
		$this->initCommentStatuses();

		if ($this->post->publish_date === null)
			$this->ui->getWidget('shortname_field')->visible = false;

		// setup tag entry control
		$this->ui->getWidget('tags')->setApplication($this->app);
		$this->ui->getWidget('tags')->setAllTags();

		$form = $this->ui->getWidget('edit_form');
		if ($this->id === null && $form->getHiddenField('unique_id') === null)
			$form->addHiddenField('unique_id', uniqid());

		$this->ui->getWidget('file_replicator')->replicators =
			$this->getFileReplicators();
	}

	// }}}
	// {{{ protected function initPost()

	protected function initPost()
	{
		$class_name = SwatDBClassMap::get('BlorgPost');
		$this->post = new $class_name();
		$this->post->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->post->load($this->id, $this->app->getInstance())) {
				throw new AdminNotFoundException(sprintf(
					Blorg::_('Post with id ‘%s’ not found.'), $this->id));
			}
		}
	}

	// }}}
	// {{{ protected function initCommentStatuses()

	protected function initCommentStatuses()
	{
		$status = $this->ui->getWidget('comment_status');

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

		if ($this->id === null) {
			switch ($this->app->config->blorg->default_comment_status) {
			case 'open':
				$status->value = BlorgPost::COMMENT_STATUS_OPEN;
				break;

			case 'moderated':
				$status->value = BlorgPost::COMMENT_STATUS_MODERATED;
				break;

			case 'locked':
				$status->value = BlorgPost::COMMENT_STATUS_LOCKED;
				break;

			case 'closed':
			default:
				$status->value = BlorgPost::COMMENT_STATUS_CLOSED;
				break;
			}
		}
	}

	// }}}
	// {{{ protected function getFileReplicators()

	protected function getFileReplicators()
	{
		$replicators = array();

		foreach ($this->getFiles() as $file) {
			$replicators[$file->id] = null;
		}

		return $replicators;
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		$upload_file_button = $this->ui->getWidget('upload_button');
		if ($upload_file_button->hasBeenClicked()) {
			$this->ui->getWidget('bodytext_field')->display_messages = false;
			$this->ui->getWidget('publish_field')->display_messages = false;
			$this->processUploadFile();
		} else {
			parent::processInternal();
		}
	}

	// }}}
	// {{{ protected function processUploadFile()

	protected function processUploadFile()
	{
		$form = $this->ui->getWidget('edit_form');

		$upload_container = $this->ui->getWidget('upload_container');

		$now = new SwatDate();
		$now->toUTC();

		$file = $this->ui->getWidget('upload_file');

		if (!$upload_container->hasMessage() && $file->isUploaded()) {
			$description = $this->ui->getWidget('upload_description');
			$attachment  = $this->ui->getWidget('upload_attachment');

			$class_name = SwatDBClassMap::get('BlorgFile');
			$blorg_file = new $class_name();
			$blorg_file->setDatabase($this->app->db);
			$unique_id = $form->getHiddenField('unique_id');

			if ($this->id === null)
				$blorg_file->form_unique_id = $unique_id;
			else
				$blorg_file->post = $this->id;

			$blorg_file->description = $description->value;
			$blorg_file->show = $attachment->value;
			$blorg_file->filename = $file->getUniqueFileName(
				'../../files');

			$blorg_file->mime_type = $file->getMimeType();
			$blorg_file->filesize = $file->getSize();
			$blorg_file->createdate = $now;
			$blorg_file->instance   = $this->app->getInstanceId();

			// automatically create an image object for image files
			if (strncmp('image', $blorg_file->mime_type, 5) == 0) {
				$blorg_file->image = $this->createImage($file);
			}

			$blorg_file->save();

			$file->saveFile('../../files', $blorg_file->filename);

			// add message
			if ($blorg_file->show) {
				$message = new SwatMessage(Blorg::_('The following file '.
					'has been attached to this post:'));
			} else {
				$message = new SwatMessage(Blorg::_('The following file '.
					'has been uploaded:'));
			}

			$message->secondary_content =
				$this->getFileDescription($blorg_file);

			$this->ui->getWidget('message_display')->add($message);

			// clear upload form values
			$description->value = null;
			$attachment->value = false;

			// add replication for new file
			$replicator = $this->ui->getWidget('file_replicator');
			$replicator->addReplication($blorg_file->id);
		} else {
			$this->ui->getWidget('file_disclosure')->open = true;
		}
	}

	// }}}
	// {{{ protected function createImage()

	protected function createImage(SwatFileEntry $file)
	{
		$class_name = SwatDBClassMap::get('BlorgFileImage');
		$image = new $class_name();
		$image->setDatabase($this->app->db);
		$image->setFileBase('../images');

		try {
			$image->process($file->getTempFileName());
		} catch (SiteInvalidImageException $e) {
			$image = null;
		}

		return $image;
	}

	// }}}
	// {{{ protected function validate()

	protected function validate()
	{
		$shortname = $this->ui->getWidget('shortname')->value;

		$publish_date = $this->ui->getWidget('publish')->getPublishDate();
		if ($publish_date === null)
			return;

		if ($shortname === null) {
			$title_value = strlen($this->ui->getWidget('title')->value) ?
				$this->ui->getWidget('title')->value :
				$this->ui->getWidget('bodytext')->value;

			$shortname = $this->generateShortname($title_value);

			$this->ui->getWidget('shortname')->value = $shortname;

		} elseif (!$this->validateShortname($shortname)) {
			$message = new SwatMessage(
				Blorg::_('Shortname already exists and must be unique.'),
				SwatMessage::ERROR);

			$this->ui->getWidget('shortname')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$publish_date = $this->ui->getWidget('publish')->getPublishDate();

		if ($publish_date === null)
			return;

		$publish_date->setTZ($this->app->default_time_zone);
		$instance_id = $this->app->getInstanceId();

		$sql = 'select shortname from BlorgPost
			where shortname = %s and instance %s %s and id %s %s
			and date_trunc(\'month\', convertTZ(publish_date, %s)) =
				date_trunc(\'month\', timestamp %s)';

		$sql = sprintf($sql,
			$this->app->db->quote($shortname, 'text'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			SwatDB::equalityOperator($this->id, true),
			$this->app->db->quote($this->id, 'integer'),
			$this->app->db->quote($publish_date->tz->getId(), 'text'),
			$this->app->db->quote($publish_date->getDate(), 'date'));

		$query = SwatDB::query($this->app->db, $sql);

		return (count($query) == 0);
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'title',
			'shortname',
			'author',
			'bodytext',
			'extended_bodytext',
			'comment_status',
		));

		$this->post->author            = $values['author'];
		$this->post->title             = $values['title'];
		$this->post->shortname         = $values['shortname'];
		$this->post->bodytext          = $values['bodytext'];
		$this->post->extended_bodytext = $values['extended_bodytext'];
		$this->post->comment_status    = $values['comment_status'];

		$instance_id = $this->app->getInstanceId();
		if ($this->id === null && $instance_id !== null) {
			$sql = sprintf('update AdminUserInstanceBinding
				set default_author = %s
				where usernum = %s and instance = %s',
				$this->app->db->quote($values['author'], 'integer'),
				$this->app->db->quote($this->app->session->user->id,
					'integer'),
				$this->app->db->quote($instance_id, 'integer'));

			SwatDB::exec($this->app->db, $sql);
		}

		$this->post->publish_date =
			$this->ui->getWidget('publish')->getPublishDate();

		$this->post->enabled = ($this->ui->getWidget('publish')->value !=
			BlorgPublishRadioTable::HIDDEN);

		if ($this->post->publish_date !== null) {
			$this->post->publish_date->setTZ($this->app->default_time_zone);
			$this->post->publish_date->toUTC();
		}

		$now = new SwatDate();
		$now->toUTC();
		$id = $this->id;

		if ($id === null) {
			$this->post->createdate   = $now;
			$this->post->instance     = $this->app->getInstanceId();
		} else {
			$this->post->modified_date = $now;
		}

		$this->post->save();

		$tags = $this->ui->getWidget('tags')->getSelectedTagArray();
		$this->post->addTagsByShortName($tags,
			$this->app->getInstance(), true);

		if ($id === null) {
			$form = $this->ui->getWidget('edit_form');
			$unique_id = $form->getHiddenField('unique_id');
			$sql = sprintf('update BlorgFile set post = %s,
				form_unique_id = null
				where form_unique_id = %s',
				$this->app->db->quote($this->post->id, 'integer'),
				$this->app->db->quote($unique_id, 'text'));

			SwatDB::exec($this->app->db, $sql);
		}

		$this->addToSearchQueue();

		$message = new SwatMessage(sprintf(Blorg::_('“%s” has been saved.'),
			$this->post->getTitle()));

		if ($this->post->enabled) {
			$this->pingWebLogsDotCom();
			$message->secondary_content = Blorg::_(
				'Weblogs.com has been notified of updated content.');
		}

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function pingWeblogsDotCom()

	protected function pingWeblogsDotCom()
	{
		try {
			$base_href = $this->app->getFrontendBaseHref().
				$this->app->config->blorg->path;

			$pinger = new BlorgWeblogsDotComPinger($this->app, $this->post,
				$base_href);

			$pinger->ping();
		} catch (Exception $e) {
			// ignore ping errors
		}
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		// this is automatically wrapped in a transaction because it is
		// called in saveDBData()
		$type = NateGoSearch::getDocumentType($this->app->db, 'post');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->post->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->post->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildFiles();

		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('select BlorgAuthor.*,
				AdminUserInstanceBinding.usernum
			from BlorgAuthor
			left outer join AdminUserInstanceBinding on
				AdminUserInstanceBinding.default_author = BlorgAuthor.id
			where BlorgAuthor.instance %s %s and BlorgAuthor.show = %s
			order by displayorder',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$rs = SwatDB::query($this->app->db, $sql);

		$default_author = null;
		$authors = array();
		foreach ($rs as $row) {
			$authors[$row->id] = $row->name;

			if ($this->id === null &&
				$row->usernum == $this->app->session->user->id)
				$this->ui->getWidget('author')->value = $row->id;
		}

		$this->ui->getWidget('author')->addOptionsByArray($authors);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->post));

		if ($this->post->publish_date !== null) {
			$publish_date = new SwatDate($this->post->publish_date);
			$publish_date->convertTZ($this->app->default_time_zone);
			$this->ui->getWidget('publish')->setPublishDate(
				$publish_date, ($this->post->enabled === false));
		}

		$this->ui->getWidget('author')->value = $this->post->author->id;

		$tags = array();
		foreach ($this->post->tags as $tag)
			$tags[] = $tag->shortname;

		$this->ui->getWidget('tags')->setSelectedTagArray($tags);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		if ($this->post->id !== null) {
			$entry = $this->layout->navbar->popEntry();
			$this->layout->navbar->createEntry($this->post->getTitle(),
				sprintf('Post/Details?id=%s', $this->post->id));

			$this->layout->navbar->addEntry($entry);
		}
	}

	// }}}
	// {{{ protected function buildFiles()

	protected function buildFiles()
	{
		$files = $this->getFiles();
		$replicator = $this->ui->getWidget('file_replicator');
		foreach ($files as $file) {
			$key = $file->id;

			// file title
			$title = $this->getFileTitle($file);
			$file_title = $replicator->getWidget('file_title', $key);
			$file_title->title = $title;
			$file_title->link = $file->getRelativeUri(
				$this->app->config->blorg->path, '../');

			// file details
			$file_details = $this->getFileDetails($file);
			$file_title = $replicator->getWidget('file_details', $key);
			$file_title->content = $file_details;

			// file icon
			$icon = $this->getFileIcon($file);
			$file_icon = $replicator->getWidget('file_icon', $key);
			$file_icon->image  = $icon['image'];
			$file_icon->width  = $icon['width'];
			$file_icon->height = $icon['height'];

			// markup
			$options = $this->getFileMarkupOptions($file);
			$file_markup = $replicator->getWidget('file_markup', $key);
			$file_markup->options = $options;

			// attachment status
			$file_attach = $replicator->getWidget('file_attach_control', $key);
			$file_attach->show = $file->show;
			$file_attach->file = $file;

			// delete
			$file_delete = $replicator->getWidget('file_delete_control', $key);
			$file_delete->file_title = $this->getFileDescription($file);
			$file_delete->file = $file;
		}
	}

	// }}}
	// {{{ protected function getFileTitle()

	protected function getFileTitle(BlorgFile $file)
	{
		if ($file->description === null) {
			$title = $this->getFileFilename($file);
		} else {
			$title = SwatString::ellipsizeRight($file->description, 30);
		}

		return $title;
	}

	// }}}
	// {{{ protected function getFileFilename()

	protected function getFileFilename(BlorgFile $file)
	{
		$extension_position = strrpos($file->filename, '.');
		if ($extension_position !== false) {
			$base = substr($file->filename, 0, $extension_position);
			$extension = substr($file->filename, $extension_position + 1);
		} else {
			$base = $file->filename;
			$extension = '';
		}

		if (strlen($base) > 18) {
			$filename = SwatString::ellipsizeRight($base, 18);
			if ($extension != '') {
				$filename.= '&nbsp;'.$extension;
			}
		} else {
			$filename = $file->filename;
		}

		return $filename;
	}

	// }}}
	// {{{ protected function getFileDetails()

	protected function getFileDetails(BlorgFile $file)
	{
		if ($file->description === null) {
			$details = SwatString::byteFormat($file->filesize);
		} else {
			$details = sprintf('%s - %s',
				$this->getFileFilename($file),
				SwatString::byteFormat($file->filesize));
		}

		return $details;
	}

	// }}}
	// {{{ protected function getFileDescription()

	protected function getFileDescription(BlorgFile $file)
	{
		if ($file->description === null) {
			$description = sprintf('%s - %s',
				$file->filename,
				SwatString::byteFormat($file->filesize));
		} else {
			$description = sprintf('%s (%s) - %s',
				$file->description,
				$file->filename,
				SwatString::byteFormat($file->filesize));
		}

		return $description;
	}

	// }}}
	// {{{ protected function getFileIcon()

	protected function getFileIcon(BlorgFile $file)
	{
		$icon = array();

		if ($file->image === null) {
			$icon['width']  = 48;
			$icon['height'] = 48;

			$file_type = $this->mapMimeType($file->mime_type);
			if ($file_type === null) {
				$file_type = 'archive';
				$types = array(
					'image',
					'audio',
					'video',
					'text',
				);

				foreach ($types as $type) {
					if (strncmp($type, $file->mime_type, strlen($type)) == 0) {
						$file_type = $type;
						break;
					}
				}
			}

			$icon['image'] =
				'packages/blorg/admin/images/file-'.$file_type.'.png';

		} else {
			$icon['width']  = $file->image->getWidth('pinky');
			$icon['height'] = $file->image->getHeight('pinky');
			$icon['image']  = $file->image->getUri('pinky', '../');
		}

		return $icon;
	}

	// }}}
	// {{{ protected function getFileMarkupOptions()

	protected function getFileMarkupOptions(BlorgFile $file)
	{
		$options = array();

		$uri = $file->getRelativeUri(
			$this->app->config->blorg->path);

		if ($file->image === null) {
			$description = ($file->description === null) ?
				$file->filename : $file->description;

			$markup = sprintf('<a class="file" href="%s">%s</a>',
				$uri, $description);

			$options[] = new SwatOption($markup, 'Link');
		} else {
			// thumbnail
			$img_tag = $file->image->getImgTag('thumb');
			$img_tag->title = $file->description;

			$thumb_markup = sprintf('<a class="file" href="%s">%s</a>',
				$uri, $img_tag);

			$options[] = new SwatOption($thumb_markup, 'Thumbnail');

			// small
			$img_tag = $file->image->getImgTag('small');
			$img_tag->title = $file->description;

			$small_markup = sprintf('<a class="file" href="%s">%s</a>',
				$uri, $img_tag);

			$options[] = new SwatOption($small_markup, 'Small');

			// original
			$description = ($file->description === null) ?
				$file->filename : $file->description;

			$original_markup = sprintf('<a class="file" href="%s">%s</a>',
				$uri, $description);

			$options[] = new SwatOption($original_markup, 'Link Only');
		}

		return $options;
	}

	// }}}
	// {{{ protected function getFiles()

	protected function getFiles()
	{
		$form = $this->ui->getWidget('edit_form');
		$form_unique_id = $form->getHiddenField('unique_id');

		$sql = sprintf('select * from BlorgFile
			where post %s %s and form_unique_id %s %s
			order by id',
			SwatDB::equalityOperator($this->post->id),
			$this->app->db->quote($this->post->id, 'integer'),
			SwatDB::equalityOperator($form_unique_id),
			$this->app->db->quote($form_unique_id, 'text'));

		return SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgFileWrapper'));
	}

	// }}}
	// {{{ protected function mapMimeType()

	protected function mapMimeType($mime_type)
	{
		$type = null;
		switch ($mime_type) {
		case 'application/ogg':
			$type = 'audio';
			break;

		case 'application/x-zip':
		case 'application/x-bzip2':
		case 'application/x-bzip-compressed-tar':
		case 'application/x-gzip':
			$type = 'archive';
			break;

		case 'application/msword':
		case 'application/pdf':
		case 'application/vnd.oasis.opendocument.text':
		case 'application/vnd.sun.xml.writer':
		case 'application/x-abiword':
			$type = 'document';
			break;
		}

		return $type;
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/blorg/admin/styles/blorg-post-edit-page.css',
			Blorg::PACKAGE_ID));
	}

	// }}}
}

?>
