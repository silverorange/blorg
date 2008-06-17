<?php

require_once 'Blorg/BlorgGadget.php';
require_once 'Swat/SwatString.php';

/**
 * Displays an ad given the ad's markup
 *
 * Available settings are:
 *
 * - <code>text    ad_markup</code>      - the html markup for embedding an ad
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAdGadget extends BlorgGadget
{
	// {{{ public function displayContent()

	public function displayContent()
	{
		$ad_markup = $this->getValue('ad_markup');
		echo $ad_markup;
	}

	// }}}
	// {{{ protected function displayTitle()

	public function displayTitle()
	{
		if ($this->hasValue('title')) {
			parent::displayTitle();
		}
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Advertisement'));
		$this->defineSetting('ad_markup',
			Blorg::_('XHTML Markup for Embedding an Ad'), 'text');

		$this->defineDescription(Blorg::_(
			'Allows embedding ad code in the sidebar.'));
	}

	// }}}
}

?>
