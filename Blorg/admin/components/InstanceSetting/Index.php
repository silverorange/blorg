<?php

/**
 * Instance settings for Blörg
 *
 * @package   Blörg
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgInstanceSettingIndex extends SiteInstanceSettingIndex
{
	// {{{ protected function initConfigPages()

	protected function initConfigPages()
	{
		$this->config_pages = array(
			new BlorgGeneralConfigPage(),
			new BlorgPostConfigPage(),
			new BlorgSpamConfigPage(),
			new BlorgAnalyticsConfigPage(),
			new BlorgAdConfigPage(),
		);
	}

	// }}}
}

?>
