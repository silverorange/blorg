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
	 * The boolean value of the setting
	 *
	 * @var boolean
	 */
	public $value_boolean;

	/**
	 * The date value of the setting
	 *
	 * @var SwatDate
	 */
	public $value_date;

	/**
	 * The float value of the setting
	 *
	 * @var float
	 */
	public $value_float;

	/**
	 * The integer value of the setting
	 *
	 * @var integer
	 */
	public $value_integer;

	/**
	 * The string value of the setting
	 *
	 * @var string
	 */
	public $value_string;

	/**
	 * The text value of the setting
	 *
	 * @var string
	 */
	public $value_text;

	// }}}
	// {{{ public function getValue()

	public function getValue($type)
	{
		$value = null;

		switch ($type) {
		case 'boolean':
			$value = $this->value_boolean;
			break;

		case 'date':
			$value = $this->value_date;
			break;

		case 'float':
			$value = $this->value_float;
			break;

		case 'integer':
			$value = $this->value_integer;
			break;

		case 'text':
			$value = $this->value_text;
			break;

		case 'string':
		default:
			$value = $this->value_string;
			break;
		}

		return $value;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'BlorgGadgetInstanceSettingValue';
		$this->registerInternalProperty('gadget_instance',
			'BlorgGadgetInstance');

		$this->registerDateProperty('value_date');
	}

	// }}}
}

?>
