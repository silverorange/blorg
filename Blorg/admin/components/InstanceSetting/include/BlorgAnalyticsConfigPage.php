<?php

require_once 'Site/admin/components/InstanceSetting/include/SiteAbstractConfigPage.php';

/**
 * Analytics Blörg instance settings
 *
 * @package   Blörg
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAnalyticsConfigPage extends SiteAbstractConfigPage
{
	// {{{ public function getPageTitle()

	public function getPageTitle()
	{
		return Blorg::_('Analytics');
	}

	// }}}
	// {{{ public function getConfigSettings()

	public function getConfigSettings()
	{
		return array(
			'analytics' => array(
				'google_account',
			),
		);
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return dirname(__FILE__).'/analytics-config-page.xml';
	}

	// }}}
}

?>
