<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatObject.php';

/**
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgGadgetSetting extends SwatObject
{
	// {{{ protected properties

	/**
	 * The name of this setting
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The title of this setting
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * The type of this setting
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The default value of this setting
	 *
	 * @var mixed
	 */
	protected $default;

	/**
	 * Valid type names
	 *
	 * @var array
	 */
	protected static $valid_types = array(
		'boolean',
		'integer',
		'float',
		'date',
		'string',
		'text',
	);

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new gadget setting
	 *
	 * @param string $name the programmatic name of the setting. This should
	 *                     follow the naming rules for PHP variables.
	 * @param string $title the title of the setting. This may be used for
	 *                      display in a settings editor, for example.
	 * @param string $type optional. the type. Should be one of: 'boolean',
	 *                     'integer', 'float', 'date', 'string' or 'text'. Text
	 *                     and string are equivalent except they may be edited
	 *                     differently in a settings editor. If not specified,
	 *                     'string' is used.
	 * @param string $default optional. The default value of the setting. If
	 *                         not specified, null is used.
	 *
	 * @throws InvalidArgumentException if the specified <i>$type</i> is not a
	 *                                  valid type.
	 */
	public function __construct($name, $title, $type, $default)
	{
		if (!in_array($type, self::$valid_types)) {
			throw new InvalidArgumentException('Type "'.$type.'" is not a '.
				'valid setting type.');
		}

		$this->name    = (string)$name;
		$this->title   = (string)$title;
		$this->type    = (string)$type;
		$this->default = (string)$default;
	}

	// }}}
	// {{{ public function getName()

	/**
	 * Gets the name of this setting
	 *
	 * @return string the name of this setting
	 */
	public function getName()
	{
		return $this->name;
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this setting
	 *
	 * @return string the title of this setting.
	 */
	public function getTitle()
	{
		return $this->title;
	}

	// }}}
	// {{{ public function getDefault()

	/**
	 * Gets the default value of this setting
	 *
	 * Note: This is the default value as specified in the constructor. This
	 * value should be passed to the {@link BlorgGadgetSetting::convertValue()}
	 * method before being used.
	 *
	 * @return mixed the default value of this setting.
	 */
	public function getDefault()
	{
		return $this->default;
	}

	// }}}
	// {{{ public function getType()

	/**
	 * Gets the type of this setting
	 *
	 * @return string one of 'string', 'text', 'integer', 'float', 'boolean' or
	 *                'date'.
	 */
	public function getType()
	{
		return $this->type;
	}

	// }}}
	// {{{ public function convertValue()

	/**
	 * Converts a string to a value of this setting's type
	 *
	 * @param string $value the value to convert.
	 *
	 * @return mixed the converted value.
	 */
	public function convertValue($value)
	{
		switch ($this->type) {
		case 'date':
			$value = new SwatDate($value);
			break;

		case 'boolean':
			switch (strtolower($value)) {
			case 'no':
			case 'off':
			case 'f':
			case 'false':
			case '0':
				$value = false;
				break;
			default:
				$value = true;
				break;
			}
			break;

		case 'integer':
			$value = intval($value);
			break;

		case 'float':
			$value = floatval($value);
			break;

		case 'string':
		case 'text':
		default:
			break;
		}

		return $value;
	}

	// }}}
}

?>
