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
	}

	// }}}
	// {{{ protected function initTag()

	protected function initTag()
	{
		$this->tag = new BlorgTag();
		$this->tag->setDatabase($this->app->db);

		if ($this->id !== null && !$this->tag->load($this->id))
			throw new AdminNotFoundException(
				sprintf(Blorg::_('Tag with id ‘%s’ not found.'), $this->id));
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$this->tag->title =
			$this->ui->getWidget('title')->value;

		if ($this->id === null) {
			$now = new SwatDate();
			$now->toUTC();
			$this->tag->createdate = $now;

			$this->tag->instance = $this->app->instance->getId();
		}

		$this->tag->save();

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
}

?>
