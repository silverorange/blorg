<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Site/dataobjects/SiteInstance.php';

/**
 * A gadget that belongs to a site instance
 *
 * Responsible for binding settings in the database to a gadget object.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       BlorgGadget
 */
class BlorgGadgetInstance extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Id of this gadget instance
	 *
	 * @var integer
	 */
	public $id = 0;

	/**
	 * The gadget class of this instance
	 *
	 * This must be a valid gadget class name. See
	 * {@link BlorgGadgetFactory::getAvailable() for a list of available gadget
	 * classes.
	 *
	 * @var string
	 */
	public $gadget = '';

	/**
	 * Position of this sidebar gadget relative to other gadgets
	 *
	 * This should be the natural ordering of gadgets when selecting multiple
	 * gadget instances from the database.
	 *
	 * @var integer
	 */
	public $displayorder = 0;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'BlorgGadgetInstance';
		$this->id_field = 'integer:id';
		$this->registerInternalProperty('instance', 'SiteInstance');
	}

	// }}}
	// {{{ protected function loadSettings()

	/**
	 * Loads setting values s for this gadget instance
	 *
	 * @return SwatDBDefaultRecordsetWrapper the setting values s of this
	 *                                       gadget instance.
	 */
	protected function loadSettings()
	{
		$sql = sprintf('select name, value from BlorgGadgetSetting
			where gadget_instance = %s',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql);
	}

	// }}}
}

?>