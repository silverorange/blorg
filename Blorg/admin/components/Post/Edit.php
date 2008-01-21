<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

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
		$this->initReplyStatuses();

		if ($this->id === null)
			$this->ui->getWidget('shortname_field')->visible = false;

		$instance_id = $this->app->instance->getId();
		$tag_where_clause = sprintf('instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$tag_list = $this->ui->getWidget('tags');
		$tag_list->addOptionsByArray(SwatDB::getOptionArray($this->app->db,
			'BlorgTag', 'title', 'id', 'title', $tag_where_clause));
	}

	// }}}
	// {{{ protected function initPost()

	protected function initPost()
	{
		$this->post = new BlorgPost();
		$this->post->setDatabase($this->app->db);

		if ($this->id !== null && !$this->post->load($this->id))
			throw new AdminNotFoundException(
				sprintf(Blorg::_('Post with id ‘%s’ not found.'), $this->id));
	}

	// }}}
	// {{{ protected function initReplyStatuses()

	protected function initReplyStatuses()
	{
		// TODO: default status to config default
		$status = $this->ui->getWidget('reply_status');
		$status->addOptionsByArray($this->post->getReplyStatuses());
	}

	// }}}
	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$shortname = $this->ui->getWidget('shortname')->value;

		if ($this->id === null && $shortname === null) {
			$title_value = strlen($this->ui->getWidget('title')->value) ?
				$this->ui->getWidget('title')->value :
				$this->ui->getWidget('bodytext')->value;

			$shortname = $this->generateShortname($title_value);

			$this->ui->getWidget('shortname')->value = $shortname;

		} elseif (!$this->validateShortname($shortname)) {
			$message = new SwatMessage(
				Site::_('Shortname already exists and must be unique.'),
				SwatMessage::ERROR);

			$this->ui->getWidget('shortname')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'title',
			'shortname',
			'bodytext',
			'extended_bodytext',
			'reply_status',
			'enabled',
		));

		$this->post->title             = $values['title'];
		$this->post->shortname         = $values['shortname'];
		$this->post->bodytext          = $values['bodytext'];
		$this->post->extended_bodytext = $values['extended_bodytext'];
		$this->post->reply_status      = $values['reply_status'];
		$this->post->enabled           = $values['enabled'];

		$now = new SwatDate();
		$now->toUTC();

		if ($this->id === null) {
			$this->post->createdate = $now;
			$this->post->instance   = $this->app->instance->getId();
			$this->post->author     = $this->app->session->getUserID();
		}
		$this->post->modified_date = $now;

		$this->post->save();

		$tag_list = $this->ui->getWidget('tags');
		SwatDB::updateBinding($this->app->db, 'BlorgPostTagBinding',
			'post', $this->post->id, 'tag', $tag_list->values,
			'BlorgTag', 'id');

		// don't bother displaying the title in the message as it may be null
		$message = new SwatMessage(Blorg::_('Post has been saved.'));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->post));

		$tag_list = $this->ui->getWidget('tags');
		$tag_list->values = SwatDB::queryColumn($this->app->db,
			'BlorgPostTagBinding', 'tag', 'post',
			$this->id);
	}

	// }}}
}

?>
