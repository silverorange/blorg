<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A setting value for a particular gadget instance
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       BlorgGadgetInstance
 */
class BlorgGadgetInstanceSettingValue extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * The name of the setting
	 *
	 * @var string
	 */
	public $name = '';

	/**
	 * The value of the setting
	 *
	 * @var string
	 */
	public $value = null;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'BlorgGadgetInstanceSettingValue';
		$this->registerInternalProperty('gadget_instance',
			'BlorgGadgetInstance');
	}

	// }}}
}

?>
