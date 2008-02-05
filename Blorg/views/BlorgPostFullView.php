<?php

require_once 'Blorg/views/BlorgPostLongView.php';
require_once 'Swat/SwatString.php';

/**
 * Full display for a Blörg post
 *
 * Displays as a complete weblog post with title, header information, full
 * bodytext and replies.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostFullView extends BlorgPostLongView
{
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
