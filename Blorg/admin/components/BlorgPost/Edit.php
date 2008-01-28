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
class BlorgBlorgPostEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var BlorgPost
	 */
	protected $post;

	protected $ui_xml = 'Blorg/admin/components/BlorgPost/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initPost();
		$this->initReplyStatuses();

		if ($this->id === null) {
			$this->ui->getWidget('shortname_field')->visible = false;
			$this->ui->getWidget('post_date_field')->visible = false;
		} else {
			$post_date = $this->ui->getWidget('post_date');
			$post_date->display_time_zone = $this->app->default_time_zone;
			$post_date->display_parts  = SwatDateEntry::YEAR |
				SwatDateEntry::MONTH | SwatDateEntry::DAY |
				SwatDateEntry::CALENDAR | SwatDateEntry::TIME;
		}

		$instance_id = $this->app->instance->getId();
		$tag_where_clause = sprintf('instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$tag_options = SwatDB::getOptionArray($this->app->db, 'BlorgTag',
			'title', 'id', 'title', $tag_where_clause);

		if (count($tag_options)) {
			$tag_list = $this->ui->getWidget('tags');
			$tag_list->addOptionsByArray($tag_options);
		} else
			$this->ui->getWidget('tag_field')->visible = false;
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
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		if ($this->post->id === null) {
			$post_date = new SwatDate();
			$post_date->toUTC();
		} else {
			$post_date = $this->ui->getWidget('post_date')->value;
		}

		$post_date->setTZ($this->app->default_time_zone);
		$instance_id = $this->app->instance->getId();

		$sql = 'select shortname from BlorgPost
			where shortname = %s and instance %s %s and id %s %s
			and post_date is null and
				date_trunc(\'month\', convertTZ(createdate, %s)) =
				date_trunc(\'month\', timestamp %s)';

		$sql = sprintf($sql,
			$this->app->db->quote($shortname, 'text'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			SwatDB::equalityOperator($this->id, true),
			$this->app->db->quote($this->id, 'integer'),
			$this->app->db->quote($post_date->tz->getId(), 'text'),
			$this->app->db->quote($post_date->getDate(), 'date'));

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
			'bodytext',
			'extended_bodytext',
			'reply_status',
			'enabled',
			'post_date',
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
			$this->post->post_date  = $now;
			$this->post->instance   = $this->app->instance->getId();
			$this->post->author     = $this->app->session->getUserID();
		} else {
			$this->post->modified_date = $now;
			$this->post->post_date     = $values['post_date'];

			if ($this->post->post_date !== null) {
				$this->post->post_date->setTZ($this->app->default_time_zone);
				$this->post->post_date->toUTC();
			}
		}

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

		if ($this->post->post_date !== null) {
			$post_date = new SwatDate($this->post->post_date);
			$post_date->convertTZ($this->app->default_time_zone);
			$this->ui->getWidget('post_date')->date = $post_date;
		}

		$tag_list = $this->ui->getWidget('tags');
		$tag_list->values = SwatDB::queryColumn($this->app->db,
			'BlorgPostTagBinding', 'tag', 'post',
			$this->id);
	}

	// }}}
}

?>
