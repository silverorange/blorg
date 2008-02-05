<?php

require_once 'Blorg/views/BlorgPostView.php';
require_once 'Swat/SwatString.php';

/**
 * Long display for a Blörg post
 *
 * Displays as a complete weblog post with title, header information and
 * bodytext. Extended bodytext is displayed as a 'Read more ...' link. Replies
 * are not displayed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostLongView extends BlorgPostView
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

		$this->displayExtendedBody();
	}

	// }}}
	// {{{ protected function displayExtendedBody()

	protected function displayExtendedBody()
	{
		if (strlen($this->post->extended_bodytext) > 0) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'entry-content entry-content-extended';
			$div_tag->open();

			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = $this->getPostRelativeUri();
			$anchor_tag->setContent(Blorg::_('Read more …'));
			$anchor_tag->display();

			$div_tag->close();
		}
	}

	// }}}
}

?>
