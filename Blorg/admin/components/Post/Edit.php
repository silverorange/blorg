<?php

/**
 * Page for adding and editing posts
 *
 * Shortnames are autogenerated for new posts if they are not set to hidden
 * when created. Otherwise, no shortname is generated so hidden posts don't
 * pollute the available shortname namespace. See
 * {@link BlorgPostEdit::validate()} and
 * {@link BlorgPostEdit::validateShortname()}.
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var BlorgPost
	 */
	protected $post;

	/**
	 * @var string
	 */
	protected $ui_xml = __DIR__.'/edit.xml';

	/**
	 * @integer
	 */
	protected $original_publish_date;

	/**
	 * @integer
	 */
	protected $original_enabled;

	/**
	 * @var SwatControl
	 */
	protected $bodytext_control;

	/**
	 * @var SwatControl
	 */
	protected $extended_bodytext_control;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		$this->initPost();
		$this->initCommentStatuses();

		// shortname is not settable until this post has been enabled
		// (not hidden)
		if (!$this->post->enabled) {
			$this->ui->getWidget('shortname_field')->visible = false;
		}

		// setup tag entry control
		$this->ui->getWidget('tags')->setApplication($this->app);
		$this->ui->getWidget('tags')->setAllTags();

		// generate form uniqueid for file attachments
		$form = $this->ui->getWidget('edit_form');
		if ($this->id === null && $form->getHiddenField('unique_id') === null)
			$form->addHiddenField('unique_id', uniqid());

		$this->ui->getWidget('file_replicator')->replicators =
			$this->getFileReplicators();

		$this->initBodytextControls();
	}

	// }}}
	// {{{ protected function initPost()

	protected function initPost()
	{
		$class_name = SwatDBClassMap::get('BlorgPost');
		$this->post = new $class_name();
		$this->post->setDatabase($this->app->db);

		if ($this->id === null) {
			$this->post->enabled = false;
		} else {
			if (!$this->post->load($this->id, $this->app->getInstance())) {
				throw new AdminNotFoundException(sprintf(
					Blorg::_('Post with id ‘%s’ not found.'), $this->id));
			}
		}

		// remember original publish_date and enabled so we can clear the
		// tags cache if they changed
		$this->original_publish_date = $this->post->publish_date;
		$this->original_enabled      = $this->post->enabled;
	}

	// }}}
	// {{{ protected function initCommentStatuses()

	protected function initCommentStatuses()
	{
		$status = $this->ui->getWidget('comment_status');

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

		if ($this->id === null) {
			switch ($this->app->config->blorg->default_comment_status) {
			case 'open':
				$status->value = SiteCommentStatus::OPEN;
				break;

			case 'moderated':
				$status->value = SiteCommentStatus::MODERATED;
				break;

			case 'locked':
				$status->value = SiteCommentStatus::LOCKED;
				break;

			case 'closed':
			default:
				$status->value = SiteCommentStatus::CLOSED;
				break;
			}
		}
	}

	// }}}
	// {{{ protected function initBodytextControls()

	protected function initBodytextControls()
	{
		if ($this->app->config->blorg->visual_editor) {
			// visual editors
			$this->bodytext_control = new SwatTextareaEditor('bodytext');
			$this->bodytext_control->required = true;
			$field = $this->ui->getWidget('bodytext_field');
			$field->add($this->bodytext_control);

			$this->extended_bodytext_control =
				new SwatTextareaEditor('extended_bodytext');

			$field = $this->ui->getWidget('extended_bodytext_field');
			$field->add($this->extended_bodytext_control);
		} else {
			// raw editors
			$this->bodytext_control = new SwatXHTMLTextarea('bodytext');
			$this->bodytext_control->allow_ignore_validation_errors = true;
			$this->bodytext_control->required = true;
			$this->bodytext_control->rows = 15;
			$field = $this->ui->getWidget('bodytext_field');
			$field->add($this->bodytext_control);

			$this->extended_bodytext_control =
				new SwatXHTMLTextarea('extended_bodytext');

			$this->extended_bodytext_control->rows = 15;
			$this->extended_bodytext_control->allow_ignore_validation_errors =
				true;

			$field = $this->ui->getWidget('extended_bodytext_field');
			$field->add($this->extended_bodytext_control);
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

			if ($this->id === null) {
				$blorg_file->form_unique_id = $unique_id;
			} else {
				$blorg_file->post = $this->id;
			}

			if ($this->app->getInstance() === null) {
				$path = '../../files';
			} else {
				$path = '../../files/'.$this->app->getInstance()->shortname;
			}

			$blorg_file->setFileBase($path);
			$blorg_file->createFileBase($path);

			$blorg_file->description = $description->value;
			$blorg_file->visible     = $attachment->value;
			$blorg_file->filename    = $file->getUniqueFileName($path);
			$blorg_file->mime_type   = $file->getMimeType();
			$blorg_file->filesize    = $file->getSize();
			$blorg_file->createdate  = $now;
			$blorg_file->instance    = $this->app->getInstanceId();

			// automatically create an image object for image files
			if (strncmp('image', $blorg_file->mime_type, 5) == 0) {
				$blorg_file->image = $this->createImage($file);
			}

			$blorg_file->save();

			$file->saveFile($path, $blorg_file->filename);

			// add message
			if ($blorg_file->visible) {
				$message = new SwatMessage(Blorg::_('The following file '.
					'has been attached to this post:'));

				if ($this->id !== null && isset($this->app->memcache)) {
					$this->app->memcache->flushNs('posts');
				}
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
		// validate or generate a shortname when the post is enabled
		$publish = $this->ui->getWidget('publish');
		if ($publish->value !== BlorgPublishRadioTable::HIDDEN) {
			$shortname = $this->ui->getWidget('shortname')->value;
			if ($shortname === null) {
				$title_value = ($this->ui->getWidget('title')->value == '') ?
					$this->bodytext_control->value :
					$this->ui->getWidget('title')->value;

				$shortname = $this->generateShortname($title_value);

				$this->ui->getWidget('shortname')->value = $shortname;
			} elseif (!$this->validateShortname($shortname)) {
				$message = new SwatMessage(
					Blorg::_('Short name already exists and must be unique.'),
					'error');

				$this->ui->getWidget('shortname')->addMessage($message);
			}
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		// don't validate or generate a shortname when the post is not enabled
		$publish = $this->ui->getWidget('publish');
		if ($publish->value === BlorgPublishRadioTable::HIDDEN) {
			return;
		}

		$post = new BlorgPost();
		$post->id = $this->id;
		$post->shortname = $shortname;
		$post->publish_date = $publish->getPublishDate();

		return BlorgPost::isShortnameValid($this->app, $post);
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->updatePost();

		$modified = $this->post->isModified();

		// save the post
		$this->post->save();

		// save tags
		$tags = $this->getTags();
		$result = $this->post->setTagsByShortName($tags);
		$tags_modified = ($result['added'] > 0 || $result['removed'] > 0);
		$modified = ($modified || $tags_modified);
		$instance_id = $this->app->getInstanceId();

		// update files attached to the form to be attached to the post
		if ($this->id === null) {
			$form = $this->ui->getWidget('edit_form');
			$unique_id = $form->getHiddenField('unique_id');
			$sql = sprintf('update BlorgFile set post = %s,
				form_unique_id = null
				where form_unique_id = %s and post is null
					and instance %s %s',
				$this->app->db->quote($this->post->id, 'integer'),
				$this->app->db->quote($unique_id, 'text'),
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

			$num = SwatDB::exec($this->app->db, $sql);
			$modified = ($modified || $num > 0);
		}

		if ($modified) {

			// clear cache
			if (isset($this->app->memcache)) {
				$this->app->memcache->flushNs('posts');

				$old_date = $this->original_publish_date;
				$new_date = $this->post->publish_date;

				if ($old_date instanceof SwatDate &&
					$new_date instanceof SwatDate) {

					$date_modified = (SwatDate::compare($old_date,
						$new_date) !== 0);
				} elseif ($old_date instanceof SwatDate && $new_date === null) {
					$date_modified = true;
				} elseif ($old_date === null && $new_date instanceof SwatDate) {
					$date_modified = true;
				} else {
					$date_modified = ($old_date !== $new_date);
				}

				if ($this->original_enabled !== $this->post->enabled ||
					$date_modified || $tags_modified) {
					$this->app->memcache->flushNs('tags');
				}
			}

			$this->addToSearchQueue();

			$message = new SwatMessage(sprintf(Blorg::_('“%s” has been saved.'),
				$this->post->getTitle()));

			// ping weblogs
			if ($this->post->enabled && $this->pingWeblogsDotCom()) {
				$message->secondary_content = Blorg::_(
					'Weblogs.com has been notified of updated content.');
			}

			$this->app->messages->add($message);
		}
	}

	// }}}
	// {{{ protected function updatePost()

	protected function updatePost()
	{
		$values = $this->ui->getValues(array(
			'title',
			'shortname',
			'author',
			'comment_status',
		));

		$values['bodytext']          = $this->bodytext_control->value;
		$values['extended_bodytext'] = $this->extended_bodytext_control->value;

		$this->post->author            = $values['author'];
		$this->post->title             = $values['title'];
		$this->post->shortname         = $values['shortname'];
		$this->post->comment_status    = $values['comment_status'];

		$this->post->bodytext          = $values['bodytext'];
		$this->post->extended_bodytext = $values['extended_bodytext'];

		$this->post->bodytext_filter =
			($this->app->config->blorg->visual_editor) ?
			'visual' : 'raw';

		// save default author for the current admin user
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

		// set published date
		$publish = $this->ui->getWidget('publish');
		$this->post->publish_date = $publish->getPublishDate();

		if ($this->post->publish_date === null) {
			$this->post->publish_date = new SwatDate();
			$this->post->publish_date->toUTC();
		} else {
			$this->post->publish_date->setTZ($this->app->default_time_zone);
			$this->post->publish_date->toUTC();
		}

		// set enabled (hidden)
		$this->post->enabled =
			($publish->value !== BlorgPublishRadioTable::HIDDEN);

		// set create/modified date
		$now = new SwatDate();
		$now->toUTC();
		if ($this->id === null) {
			$this->post->createdate = $now;
			$this->post->instance   = $this->app->getInstanceId();
		} else {
			if ($this->post->isModified()) {
				$this->post->modified_date = $now;
			}
		}
	}

	// }}}
	// {{{ protected function getTags()

	protected function getTags()
	{
		return $this->ui->getWidget('tags')->getSelectedTagArray();
	}

	// }}}
	// {{{ protected function pingWeblogsDotCom()

	/**
	 * @return boolean
	 */
	protected function pingWeblogsDotCom()
	{
		$pinged = false;

		try {
			$base_href = $this->app->getFrontendBaseHref().
				$this->app->config->blorg->path;

			$pinger = new BlorgWeblogsDotComPinger($this->app, $this->post,
				$base_href);

			$pinger->ping();

			$pinged = true;

		} catch (Exception $e) {
			// ignore ping errors
		}

		return $pinged;
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
		$this->buildAuthors();

		// init image server for editors here since form_unique_id has been
		// processed as this point.
		if ($this->app->config->blorg->visual_editor) {
			$this->bodytext_control->image_server =
				$this->getBodytextEditorImageServer();

			$this->extended_bodytext_control->image_server =
				$this->getBodytextEditorImageServer();
		}
	}

	// }}}
	// {{{ protected function buildAuthors()

	protected function buildAuthors()
	{
		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('select BlorgAuthor.*,
				AdminUserInstanceBinding.usernum
			from BlorgAuthor
			left outer join AdminUserInstanceBinding on
				AdminUserInstanceBinding.default_author = BlorgAuthor.id
			where BlorgAuthor.instance %s %s
				and (BlorgAuthor.visible = %s or BlorgAuthor.id = %s)
			order by displayorder',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($this->post->getInternalValue('author'),
				'integer'));

		$rs = SwatDB::query($this->app->db, $sql);

		// if there are no visible authors, go back to post index page
		if (count($rs) === 0) {
			$this->app->relocate('Post');
		}

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

		$this->bodytext_control->value = $this->post->bodytext;
		$this->extended_bodytext_control->value =
			$this->post->extended_bodytext;

		if ($this->post->publish_date !== null) {
			$publish_date = new SwatDate($this->post->publish_date);
			$publish_date->convertTZ($this->app->default_time_zone);
			$this->ui->getWidget('publish')->setPublishDate(
				$publish_date, ($this->post->enabled === false));
		}

		$this->ui->getWidget('author')->value = $this->post->author->id;

		$tags = array();
		foreach ($this->post->tags as $tag)
			$tags[$tag->shortname] = $tag->title;

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
			$file_attach->visible = $file->visible;
			$file_attach->file = $file;

			// delete
			$file_delete = $replicator->getWidget('file_delete_control', $key);
			$file_delete->file_title = $this->getFileDescription($file);
			$file_delete->file = $file;
		}
	}

	// }}}
	// {{{ protected function getBodytextEditorImageServer()

	protected function getBodytextEditorImageServer()
	{
		$uri = $this->app->getBaseHref().'Post/FileImageServer';
		if ($this->post->id === null) {
			$form = $this->ui->getWidget('edit_form');
			$form_unique_id = $form->getHiddenField('unique_id');
			$uri.= sprintf('&form_unique_id=%s', urlencode($form_unique_id));
		} else {
			$uri.= sprintf('&post_id=%s', urlencode($this->post->id));
		}
		return $uri;
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
		$extension_position = mb_strrpos($file->filename, '.');
		if ($extension_position !== false) {
			$base = mb_substr($file->filename, 0, $extension_position);
			$extension = mb_substr($file->filename, $extension_position + 1);
		} else {
			$base = $file->filename;
			$extension = '';
		}

		if (mb_strlen($base) > 18) {
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
					$length = mb_strlen($type);
					if (strncmp($type, $file->mime_type, $length) === 0) {
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

			$options[] = new SwatOption($thumb_markup, Blorg::_('Thumbnail'));

			// small
			$img_tag = $file->image->getImgTag('small');
			$img_tag->title = $file->description;

			$small_markup = sprintf('<a class="file" href="%s">%s</a>',
				$uri, $img_tag);

			$options[] = new SwatOption($small_markup, Blorg::_('Medium'));

			// original
			$img_tag = $file->image->getImgTag('original');
			$img_tag->title = $file->description;

			$original_markup = sprintf('<a class="file" href="%s">%s</a>',
				$uri, $img_tag);

			$options[] = new SwatOption($original_markup, Blorg::_('Original'));

			// link to file
			$description = ($file->description === null) ?
				$file->filename : $file->description;

			$link_markup = sprintf('<a class="file" href="%s">%s</a>',
				$uri, $description);

			$options[] = new SwatOption($link_markup, Blorg::_('Link Only'));
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
