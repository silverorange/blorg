<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Blorg/dataobjects/BlorgAuthor.php';

/**
 * Page for editing authors
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAuthorEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var BlorgAuthor
	 */
	protected $author;

	protected $ui_xml = 'Blorg/admin/components/Author/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initAuthor();

		if ($this->id === null)
			$this->ui->getWidget('shortname_field')->visible = false;
	}

	// }}}
	// {{{ protected function initAuthor()

	protected function initAuthor()
	{
		$this->author = new BlorgAuthor();
		$this->author->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->author->load($this->id)) {
				throw new AdminNotFoundException(sprintf(
					Blorg::_('Author with id ‘%s’ not found.'), $this->id));
			}

			$instance_id = $this->author->getInternalValue('instance');
			if ($instance_id !== $this->app->getInstanceId()) {
				throw new AdminNotFoundException(sprintf(
					Blorg::_('Author with id ‘%d’ not found.'), $this->id));
			}
		}
	}

	// }}}

	// process phase
	// {{{ protected function validate()

	protected function validate()
	{
		$shortname = $this->ui->getWidget('shortname')->value;

		if ($this->id === null) {
			$shortname = $this->generateShortname(
				$this->ui->getWidget('name')->value);

			$this->ui->getWidget('shortname')->value = $shortname;

		} elseif (!$this->validateShortname($shortname)) {
			$message = new SwatMessage(
				Blorg::_('Author shortname already exists and must be unique.'),
				SwatMessage::ERROR);

			$this->ui->getWidget('shortname')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$sql = 'select shortname from BlorgAuthor
			where shortname = %s and id %s %s and instance %s %s';

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf($sql,
			$this->app->db->quote($shortname, 'text'),
			SwatDB::equalityOperator($this->id, true),
			$this->app->db->quote($this->id, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$query = SwatDB::query($this->app->db, $sql);

		return (count($query) == 0);
	}

	// }}}
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'name',
			'shortname',
			'visible',
			'description',
			'bodytext',
			'email',
		));

		$this->author->name     = $values['name'];
		$this->author->shortname = $values['shortname'];
		$this->author->visible = $values['visible'];
		$this->author->description = $values['description'];
		$this->author->bodytext = $values['bodytext'];
		$this->author->email = $values['email'];

		if ($this->id === null)
			$this->author->instance = $this->app->getInstanceId();

		$this->author->save();

		$message = new SwatMessage(
			sprintf(Blorg::_('“%s” has been saved.'), $this->author->name));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->author));
	}

	// }}}
}

?>
