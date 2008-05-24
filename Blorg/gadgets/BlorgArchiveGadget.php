<?php

require_once 'Blorg/BlorgGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Displays a link to the weblog archive
 *
 * TODO: add options to display time-based archive lives (years/months, etc0>
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgArchiveGadget extends BlorgGadget
{
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Archive'));
	}

	// }}}
	// {{{ protected function displayTitle()

	/**
	 * Displays the title of title of this widget with a link to the archive.
	 *
	 * The title is displayed in a h3 element with the CSS class
	 * 'blorg-gadget-title'.
	 */
	protected function displayTitle()
	{
		$header = new SwatHtmlTag('h3');
		$header->class = 'blorg-gadget-title';

		$link = new SwatHtmlTag('a');
		$link->setContent($this->getTitle());
		$link->href = 'archive';

		$header->open();
		$link->display();
		$header->close();
	}

	// }}}
}

?>
