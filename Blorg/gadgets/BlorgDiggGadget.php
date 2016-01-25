<?php

require_once 'Site/gadgets/SiteGadget.php';
require_once 'Swat/SwatString.php';

/**
 * Displays recently dugg stories on Digg
 *
 * Available settings are:
 *
 * - <kbd>string username</kbd> - the Digg username for which to display
 *                                stories. If not specified, front page stories
 *                                are displayed.
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgDiggGadget extends SiteGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$username = $this->getValue('username');
		if ($username == '') {
			$what = 'front/all';
		} else {
			$username = SwatString::minimizeEntities(urlencode($username));
			$what = 'user/dugg/'.$username;
		}

		printf('<script type="text/javascript" '.
			'src="http://digg.com/diggjs/%s/3"></script>',
			$what);
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Recently Dugg'));
		$this->defineSetting('username', Blorg::_('User Name'), 'string');
		$this->defineDescription(Blorg::_(
			'Lists recently dugg stories for a user on Digg.'));
	}

	// }}}
}

?>
