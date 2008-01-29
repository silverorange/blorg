<?php

require_once 'Blorg/BlorgPostLongView.php';
require_once 'Swat/SwatString.php';

/**
 * Full display for a Blörg post in the admin
 *
 * Displays as a complete weblog post with title, header information and full
 * bodytext. Replies are not displayed. Links are not linked.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostAdminView extends BlorgPostLongView
{
	// {{{ public function display()

	public function display($link = false)
	{
		echo '<div class="entry hentry">';

		$this->displayHeader();
		$this->displayBody();

		echo '</div>';
	}

	// }}}
	// {{{ protected function displayExtendedBody()

	protected function displayExtendedBody()
	{
		if (strlen($this->post->extended_bodytext) > 0) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'entry-content entry-content-extended';
			$div_tag->setContent($this->post->extended_bodytext, 'text/xml');
			$div_tag->display();
		}
	}

	// }}}
}

?>
