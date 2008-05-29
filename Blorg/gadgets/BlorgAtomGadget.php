<?php

require_once 'Blorg/BlorgGadget.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * Displays atom links
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAtomGadget extends BlorgGadget
{
	// {{{ public function display()

	public function display()
	{
		parent::display();

		$base_href = $this->app->config->blorg->path;

		echo '<ul class="blorg-syndication"><li>';

		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $base_href.'atom';
		$a_tag->setContent(Blorg::_('Recent Posts'));
		$a_tag->display();

		echo '</li><li>';

		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $base_href.'atom/replies';
		$a_tag->setContent(Blorg::_('Recent Replies'));
		$a_tag->display();

		echo '</li></ul>';
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Syndication'));
		$this->defineDescription(Blorg::_(
			'Displays links to the Atom feeds for recent posts and reader '.
			'comments.'));
	}

	// }}}
}

?>
