<?php

require_once 'Swat/SwatDate.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Blorg/BlorgGadgetFactory.php';
require_once 'Blorg/dataobjects/BlorgGadgetInstanceSettingValue.php';
require_once 'Blorg/dataobjects/BlorgGadgetInstanceSettingValueWrapper.php';
require_once 'Blorg/dataobjects/BlorgGadgetInstance.php';

/**
 * Page for editing sidebar gadget instance settings
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgSidebarSettings extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var BlorgGadgetInstance
	 */
	protected $gadget_instance;

	/**
	 * @var BlorgGadget
	 */
	protected $gadget;

	protected $ui_xml = 'Blorg/admin/components/Sidebar/settings.xml';

	protected $setting_widgets = array();

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initGadgetInstance();
		$this->initGadget();
		$this->initSettingsUi();
	}

	// }}}
	// {{{ protected function initGadgetInstance()

	protected function initGadgetInstance()
	{
		$class_name = SwatDBClassMap::get('BlorgGadgetInstance');
		$this->gadget_instance = new $class_name();
		$this->gadget_instance->setDatabase($this->app->db);

		if ($this->id === null || !$this->gadget_instance->load($this->id)) {
			throw new AdminNotFoundException(
				sprintf(Blorg::_('Sidebar gadget with id ‘%s’ not found.'),
					$this->id));
		}

		if ($this->gadget_instance->instance === null) {
			if ($this->app->getInstanceId() !== null) {
				throw new AdminNotFoundException(
					sprintf(Blorg::_('Sidebar gadget with id ‘%s’ not found.'),
						$this->id));
			}
		} else {
			if ($this->gadget_instance->instance->id !==
				$this->app->getInstanceId()) {
				throw new AdminNotFoundException(
					sprintf(Blorg::_('Sidebar gadget with id ‘%s’ not found.'),
						$this->id));
			}
		}
	}

	// }}}
	// {{{ protected function initGadget()

	protected function initGadget()
	{
		$this->gadget = BlorgGadgetFactory::get($this->app,
			$this->gadget_instance);
	}

	// }}}
	// {{{ protected function initSettingsUi()

	protected function initSettingsUi()
	{
		$settings = $this->gadget->getSettings();

		$container = $this->ui->getWidget('settings_container');

		foreach ($settings as $id => $setting) {
			switch ($setting->getType()) {
			case 'boolean':
				require_once 'Swat/SwatCheckbox.php';
				$widget = new SwatCheckbox();
				$widget->id = $id;
				break;

			case 'date':
				require_once 'Swat/SwatDateEntry.php';
				$widget = new SwatDateEntry();
				$widget->id = $id;
				break;

			case 'integer':
				require_once 'Swat/SwatIntegerEntry.php';
				$widget = new SwatIntegerEntry();
				$widget->id = $id;
				break;

			case 'integer':
				require_once 'Swat/SwatFloatEntry.php';
				$widget = new SwatFloatEntry();
				$widget->id = $id;
				break;

			case 'text':
				require_once 'Swat/SwatTextarea.php';
				$widget = new SwatTextarea();
				$widget->id = $id;
				$widget->rows = 4;
				$widget->columns = 60;
				break;

			case 'string':
			default:
				require_once 'Swat/SwatEntry.php';
				$widget = new SwatEntry();
				$widget->id = $id;
				break;

			}

			require_once 'Swat/SwatFormField.php';
			$field = new SwatFormField();
			$field->title = $setting->getTitle();

			if ($setting->getDefault() !== null) {
				$em_tag = new SwatHtmlTag('em');
				$em_tag->setContent($this->getSettingDefaultValue($setting));
				$field->note = sprintf(Blorg::_('Default: %s'), $em_tag);
				$field->note_content_type = 'text/xml';
			}
			$field->add($widget);

			$this->settings_widgets[$id] = $widget;

			$container->add($field);
		}
	}

	// }}}
	// {{{ protected function getSettingDefaultValue()

	protected function getSettingDefaultValue(BlorgGadgetSetting $setting)
	{
		switch ($setting->getType()) {
		case 'boolean':
			if ($setting->getDefault()) {
				$value = 'true';
			} else {
				$value = 'false';
			}
			break;

		case 'date':
			$value = $setting->getDefault()->format(SwatDate::DF_DATE_TIME);
			break;

		case 'integer':
		case 'float':
			$locale = SwatI18NLocale::get();
			$value = $locale->formatNumber($setting->getDefault());
			break;

		case 'string':
		case 'text':
		default:
			$value = $setting->getDefault();
		}

		return $value;
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		// delete old setting values
		$sql = sprintf('delete from BlorgGadgetInstanceSettingValue
			where gadget_instance = %s',
			$this->app->db->quote($this->id, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		// create new wrapper
		$class_name =
			SwatDBClassMap::get('BlorgGadgetInstanceSettingValueWrapper');

		$this->gadget_instance->setting_values = new $class_name();
		$this->gadget_instance->setting_values->setDatabase($this->app->db);

		// add new setting values to wrapper
		$class_name = SwatDBClassMap::get('BlorgGadgetInstanceSettingValue');
		$settings = $this->gadget->getSettings();
		foreach ($settings as $id => $setting) {
			$widget = $this->settings_widgets[$id];
			if ($widget->value !== null) {
				$setting_value = new $class_name();
				$setting_value->name = $id;
				$setting_value->value = $widget->value;
				$setting_value->gadget_instance = $this->gadget_instance;
				$this->gadget_instance->setting_values->add($setting_value);
			}
		}

		// save wrapper
		$this->gadget_instance->setting_values->save();

		$message = new SwatMessage(sprintf(
			Blorg::_('“%s” has been saved.'),
			$this->gadget->getTitle()));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->ui->getWidget('edit_frame')->subtitle = sprintf(
			Blorg::_('for %s'),
			$this->gadget->getTitle());
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$values = $this->gadget_instance->setting_values;
		$settings = $this->gadget->getSettings();
		foreach ($values as $object) {
			if (array_key_exists($object->name, $settings)) {
				$widget = $this->settings_widgets[$object->name];
				$widget->value = $object->value;
			}
		}
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntry();

		$this->navbar->addEntry(new SwatNavBarEntry(
			Blorg::_('Edit Sidebar Gadget Settings')));
	}

	// }}}
}

?>
