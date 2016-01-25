<?php

require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminEdit.php';

/**
 * Page for editing site instance settings
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgConfigAdEdit extends AdminEdit
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Blorg/admin/components/Config/ad-edit.xml';

	/**
	 * @var array
	 */
	protected $setting_keys = array(
		'blorg' => array(
			'ad_top',
			'ad_bottom',
			'ad_post_content',
			'ad_post_comments',
			'ad_referers_only',
		),
	);

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// process phase
	// {{{ protected function saveData()

	protected function saveData()
	{
		$settings = array();

		foreach ($this->setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;
				$saver_method = 'save'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $saver_method)) {
					$this->$saver_method();
				} else {
					$widget = $this->ui->getWidget($field_name);
					$this->app->config->$section->$name = $widget->value;
				}

				$settings[] = $section.'.'.$name;
			}
		}

		$this->app->config->save($settings);

		$message = new SwatMessage(
			Blorg::_('Advertising preferences have been saved.'));

		$this->app->messages->add($message);

		return true;
	}

	// }}}

	// build phase
	// {{{ protected function buildFrame()

	protected function buildFrame()
	{
	}

	// }}}
	// {{{ protected function buildButton()

	protected function buildButton()
	{
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$form = $this->ui->getWidget('edit_form');

		if (!$form->isProcessed())
			$this->loadData();

		$form->action = $this->source;
		$form->autofocus = true;

		if ($form->getHiddenField(self::RELOCATE_URL_FIELD) === null) {
			$url = $this->getRefererURL();
			$form->addHiddenField(self::RELOCATE_URL_FIELD, $url);
		}
	}
	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$this->navbar->popEntry();
		$this->navbar->createEntry(Blorg::_('Edit Advertising Preferences'));
	}

	// }}}
	// {{{ protected function loadData()

	protected function loadData()
	{
		foreach ($this->setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;
				$loader_method = 'load'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $loader_method)) {
					$this->$loader_method();
				} else {
					$widget = $this->ui->getWidget($field_name);
					$widget->value = $this->app->config->$section->$name;
				}
			}
		}

		return true;
	}

	// }}}
}

?>
