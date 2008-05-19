<?php

require_once 'Site/SiteApplication.php';
require_once 'Swat/SwatUIObject.php';
require_once 'Blorg/BlorgGadgetSetting.php';
require_once 'Blorg/dataobjects/BlorgGadgetInstance.php';

/**
 * Base class for creating sidebar gadgets
 *
 * All sidebar gadgets are instantiated with a {@link BlorgGadgetInstance},
 * which binds setting values to the gadget; and with a reference to the
 * application.
 *
 * Sidebar gadgets are instances of SwatUIObject and may be added to a
 * {@link BlorgSidebar} parent widget.
 *
 * Gadgets have settings. Settings consist of a name, title, type and default
 * value. Setting values come from the {@link BlorgGadgetInstance}. Note:
 * Setting values can not exceed 255 characters.
 *
 * The settings available to all gadgets are:
 *
 * - string title the title of this gadget. Gadgets may display this title
 *                using the {@link BlorgGadget::displayTitle()} method. The
 *                default title as specified by the
 *                {@link BlorgGadget::defineDefaultTitle} method is used by
 *                default.
 *
 * Creating a gadget involves two main tasks:
 *
 * 1. Define settings, define a default title and add required style-sheet and
 *    JavaScript resources in the body of the {@link BlorgGadget::define()}
 *    method.
 *
 * 2. Implement the {@link BlorgGadget::init()}, {@link BlorgGadget::process()}
 *    and {@link BlorgGadget::display()} methods where necessary. Most gadgets
 *    will only have to implement the display() method. The init() and process()
 *    methods are hooks into the Swat UI tree initilization and processing
 *    methods.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       BlorgGadgetSetting
 */
abstract class BlorgGadget extends SwatUIObject
{
	// {{{ protected properties

	/**
	 * The application in which this gadget exists
	 *
	 * @var SiteApplication
	 *
	 * @see BlorgGadget::__construct()
	 */
	protected $app;

	// }}}
	// {{{ private properties

	/**
	 * The avaiable settings of this gadget
	 *
	 * @var array
	 *
	 * @see BlorgGadget::defineSetting()
	 * @see BlorgGadget::getSettings()
	 */
	private $settings = array();

	/**
	 * Default title of this gadget
	 *
	 * @var string
	 *
	 * @see BlorgGadget::defineDefaultTitle()
	 */
	private $default_title = '';

	/**
	 * The current setting values of this gadget for the instance in which
	 * this gadget was created
	 *
	 * @var array
	 *
	 * @see BlorgGadget::getValue()
	 */
	private $values = null;

	/**
	 * The gadget instance of this gadget
	 *
	 * This is a dataobject that binds setting values to this gadget.
	 *
	 * @var BlorgGadgetInstance
	 *
	 * @see BlorgGadget::__construct()
	 */
	private $gadget_instance = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Instantiates this gadget
	 *
	 * Developers should use the {@link BlorgGadgetFactory::get()} method
	 * rather than instantiating gadgets directly. The factory will resolve
	 * and instantiate the correct gadget class automatically.
	 *
	 * @param SiteApplication $app the application in which this gadget exists.
	 * @param BlorgGadgetInstance $instance the gadget instance used to bind
	 *                                      setting values to this gadget.
	 */
	public function __construct(SiteApplication $app,
		BlorgGadgetInstance $instance)
	{
		$this->default_title = Blorg::_('Untitled Gadget');

		$this->gadget_instance = $instance;
		$this->app = $app;

		// add user defined settings and default title
		$this->define();

		// add settings common to all gadgets
		$this->defineSetting('title', 'Title', 'string', $this->default_title);
	}

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this gadget
	 *
	 * Provides a hook into the init() step of the Swat UI tree.
	 *
	 * @see SwatWidget::init()
	 */
	public function init()
	{
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this gadget
	 *
	 * Provides a hook into the process() step of the Swat UI tree.
	 *
	 * @see SwatWidget::process()
	 */
	public function process()
	{
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this gadget
	 *
	 * Provides a hook into the display() step of the Swat UI tree.
	 *
	 * By default, the title of this gadget is displayed in this method.
	 * Subclasses may optinoally omit displaying the title from implementations
	 * of the display method.
	 */
	public function display()
	{
		$this->displayTitle();
	}

	// }}}
	// {{{ public function getTitle()

	/**
	 * Gets the title of this gadget
	 *
	 * If the setting 'title' does not have a value, the default title is
	 * returned.
	 *
	 * @return string the title of this gadget.
	 */
	public function getTitle()
	{
		return $this->getValue('title');
	}

	// }}}
	// {{{ public final function getSettings()

	/**
	 * Gets all defined settings of this gadget
	 *
	 * This is intended for use in a gadget editor.
	 *
	 * @return array the defined settings of this gadget.
	 *
	 * @see BlorgGadget::defineSetting()
	 */
	public final function getSettings()
	{
		return $this->settings;
	}

	// }}}
	// {{{ protected function displayTitle()

	/**
	 * Displays the title of this gadget
	 *
	 * The title is displayed in a h3 element with the CSS class
	 * 'blorg-gadget-title'.
	 */
	protected function displayTitle()
	{
		$header = new SwatHtmlTag('h3');
		$header->class = 'blorg-gadget-title';
		$header->setContent($this->getTitle());
		$header->display();
	}

	// }}}
	// {{{ protected function hasSetting()

	/**
	 * Gets whether or not this gadget has the specified setting
	 *
	 * @param string $name the name of the setting.
	 *
	 * @return boolean true if this gadget has the specified setting and false
	 *                 if it does not.
	 */
	protected function hasSetting($name)
	{
		return (array_key_exists($name, $this->settings));
	}

	// }}}
	// {{{ protected function getValue()

	/**
	 * Gets a setting value for this gadget
	 *
	 * @param string $name the name of the setting.
	 *
	 * @return mixed the setting value for this gadget. If no value exists, the
	 *               default setting value is returned. The type of the returned
	 *               value will be the setting type.
	 */
	protected function getValue($name)
	{
		if (!$this->hasSetting($name)) {
			throw new InvalidArgumentException('Gadget "'.get_class($this).
				'" does not have a setting named "'.$name.'".');
		}

		// lazily load all instance setting values for this gadget
		if ($this->values === null) {
			$this->values = array();
			try {
				foreach ($this->gadget_instance->settings as $setting) {
					$setting_name = $setting->name;
					$setting_value = $setting->value;
					$setting_value =
						$this->settings[$name]->convertValue($setting_value);

					$this->values[$setting_name] = $setting_value;
				}
			} catch (SwatDBNoDatabaseException $e) {
				// don't try to load settings if we don't have a database
			}
		}

		if (array_key_exists($name, $this->values)) {
			$value = $this->values[$name];
		} else {
			$value = $this->getDefaultValue($name);
			$value = $this->settings[$name]->convertValue($value);
		}

		return $value;
	}

	// }}}
	// {{{ protected function getDefaultValue()

	/**
	 * Gets the default value of a setting
	 *
	 * @param string $name the name of the setting.
	 *
	 * @return mixed the default value of the specified setting.
	 *
	 * @throw InvalidArgumentException if the specified <i>$name</i> is not a
	 *                                 valid setting name for this gadget.
	 */
	protected function getDefaultValue($name)
	{
		if (!$this->hasSetting($name)) {
			throw InvalidArgumentException('Gadget "'.get_class($this).
				'" does not have a setting named "'.$name.'".');
		}

		return $this->settings[$name]->getDefault();
	}

	// }}}
	// {{{ protected function define()

	/**
	 * Provides a location for gadget subclasses to define settings; define the
	 * default title; and add external style-sheet and JavaScript resources to
	 * this gadget
	 *
	 * @see BlorgGadget::defineDefaultTitle()
	 * @see BlorgGadget::defineSetting()
	 * @see SwatUIObject::addStyleSheet()
	 * @see SwatUIObject::addJavaScript()
	 */
	protected function define()
	{
	}

	// }}}
	// {{{ protected function defineDefaultTitle()

	/**
	 * Defines the default title of this gadget
	 *
	 * The default title is used as the title of this gadget when selecting the
	 * gadget from a list or when no title value is set in the gadget instance.
	 *
	 * @param string $title the default title of this gadget.
	 */
	protected function defineDefaultTitle($title)
	{
		$this->default_title = (string)$title;
	}

	// }}}
	// {{{ protected final function defineSetting()

	/**
	 * Defines a setting for this gadget
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
	 */
	protected final function defineSetting($name, $title, $type = 'string',
		$default = null)
	{
		$this->settings[$name] = new BlorgGadgetSetting($name, $title, $type,
			$default);
	}

	// }}}
}

?>
