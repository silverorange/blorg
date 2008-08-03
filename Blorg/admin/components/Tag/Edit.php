<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Blorg/dataobjects/BlorgTag.php';

/**
 * Page for editing tags
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var BlorgTag
	 */
	protected $tag;

	protected $ui_xml = 'Blorg/admin/components/Tag/edit.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initTag();

		if ($this->id === null)
			$this->ui->getWidget('shortname_field')->visible = false;
	}

	// }}}
	// {{{ protected function initTag()

	protected function initTag()
	{
		$this->tag = new BlorgTag();
		$this->tag->setDatabase($this->app->db);

		if ($this->id !== null) {
			if (!$this->tag->load($this->id)) {
				throw new AdminNotFoundException(sprintf(
					Blorg::_('Tag with id ‘%s’ not found.'), $this->id));
			}

			$instance_id = $this->tag->getInternalValue('instance');
			if ($instance_id !== $this->app->getInstanceId()) {
				throw new AdminNotFoundException(sprintf(
					Blorg::_('Tag with id ‘%d’ not found.'), $this->id));
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
				$this->ui->getWidget('title')->value);

			$this->ui->getWidget('shortname')->value = $shortname;

		} elseif (!$this->validateShortname($shortname)) {
			$message = new SwatMessage(
				Blorg::_('Tag shortname already exists and must be unique.'),
				SwatMessage::ERROR);

			$this->ui->getWidget('shortname')->addMessage($message);
		}
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$sql = 'select shortname from BlorgTag
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
			'title',
			'shortname',
		));

		$this->tag->title     = $values['title'];-
		$this->tag->shortname = $values['shortname'];

		if ($this->id === null) {
			$now = new SwatDate();
			$now->toUTC();
			$this->tag->createdate = $now;

			$this->tag->instance = $this->app->getInstanceId();
		}

		$this->tag->save();

		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs('tags');
			$this->app->memcache->flushNs('posts');
		}

		$message = new SwatMessage(
			sprintf(Blorg::_('“%s” has been saved.'), $this->tag->title));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->tag));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		if ($this->id !== null) {
			$edit = $this->navbar->popEntry();
			$this->navbar->addEntry(new SwatNavBarEntry($this->tag->title,
				$this->getComponentName().'/Details?id='.$this->id));

			$this->navbar->addEntry($edit);
		}
	}

	// }}}
}

?>
