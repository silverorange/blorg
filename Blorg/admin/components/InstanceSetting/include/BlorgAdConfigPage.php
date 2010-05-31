<?php

require_once 'Site/admin/components/InstanceSetting/include/SiteAbstractConfigPage.php';

/**
 * Advertising Blörg instance settings
 *
 * @package   Blörg
 * @copyright 2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAdConfigPage extends SiteAbstractConfigPage
{
	// {{{ public function getPageTitle()

	public function getPageTitle()
	{
		return Blorg::_('Advertising');
	}

	// }}}
	// {{{ public function getConfigSettings()

	public function getConfigSettings()
	{
		return array(
			'blorg' => array(
				'ad_top',
				'ad_bottom',
				'ad_post_content',
				'ad_post_comments',
				'ad_referers_only',
			),
		);
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return dirname(__FILE__).'/ad-config-page.xml';
	}

	// }}}
}

?>
