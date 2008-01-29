<?php

require_once 'Blorg/BlorgPostLongView.php';
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
	// {{{ public function display()

	public function display($link = false)
	{
		echo '<div class="entry hentry">';

		$this->displayHeader($link);
		$this->displayBody();
		$this->displayReplies();

		echo '</div>';
	}

	// }}}
	// {{{ protected function displayReplies()

	protected function displayReplies()
	{
		foreach ($this->post->replies as $reply) {
			$this->displayReply($reply);
		}
	}

	// }}}
	// {{{ protected function displayReply()

	protected function displayReply(BlorgReply $reply)
	{
		echo $reply;
	}

	// }}}
}

?>
