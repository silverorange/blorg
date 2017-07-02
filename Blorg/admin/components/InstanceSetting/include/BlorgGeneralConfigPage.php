<?php

/**
 * Main Blörg instance settings
 *
 * @package   Blörg
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgGeneralConfigPage extends SiteAbstractConfigPage
{
	// {{{ public function getPageTitle()

	public function getPageTitle()
	{
		return Blorg::_('General');
	}

	// }}}
	// {{{ public function getConfigSettings()

	public function getConfigSettings()
	{
		return array(
			'site' => array(
				'title',
				'tagline',
				'meta_description',
			),
			'date' => array(
				'time_zone',
			),
		);
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return dirname(__FILE__).'/general-config-page.xml';
	}

	// }}}
}

?>
