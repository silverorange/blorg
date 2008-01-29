<?php

require_once 'Blorg/BlorgPostView.php';
require_once 'Swat/SwatString.php';

/**
 * Full display for a Blörg post
 *
 * Displays as a complete weblog post with title and header information.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostFullView extends BlorgPostView
{
	// {{{ public function display()

	public function display($link = false)
	{
		echo '<div class="entry hentry">';

		$this->displayHeader($link);
		$this->displayBody();

		echo '</div>';
	}

	// }}}
	// {{{ protected function displayBody()

	protected function displayBody()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'entry-content';
		$div_tag->setContent($this->post->bodytext, 'text/xml');
		$div_tag->display();

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
