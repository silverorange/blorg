<?php

require_once 'Site/SiteGadget.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * Displays feed links
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFeedGadget extends SiteGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$base_href = $this->app->config->blorg->path;

		echo '<ul class="blorg-syndication"><li>';

		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $base_href.'feed';
		$a_tag->setContent(Blorg::_('Recent Posts'));
		$a_tag->display();

		echo '</li><li>';

		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = $base_href.'feed/comments';
		$a_tag->setContent(Blorg::_('Recent Comments'));
		$a_tag->display();

		echo '</li></ul>';
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Feeds'));
		$this->defineDescription(Blorg::_(
			'Displays links to the feeds for recent posts and reader '.
			'comments.'));
	}

	// }}}
}

?>
