<?php

require_once 'Blorg/BlorgGadget.php';
require_once 'Swat/SwatString.php';

/**
 * Displays arbitrary content
 *
 * Available settings are:
 *
 * - text   content      the content to display.
 * - string content_type the content type. If set to 'text/xml', no escaping
 *                       will be done on the content. Othewise, special HTML
 *                       characters in the content are escaped.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgContentGadget extends BlorgGadget
{
	// {{{ public function display()

	public function display()
	{
		$content = $this->getValue('content');
		if ($this->getValue('content_type') != 'text/xml') {
			$content = SwatString::minimizeEntities($content);
		}

		echo $content;
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Arbitrary Content'));
		$this->defineSetting('content', Blorg::_('Content'), 'text');
		$this->defineSetting('content_type', Blorg::_('Content Type'),
			'string', 'text/plain');

		$this->defineDescription(Blorg::_(
			'Provides a place to place arbitrary content in the sidebar. '.
			'Content may include custom XHTML by specifying the '.
			'“content_type” setting.'));
	}

	// }}}
}

?>
