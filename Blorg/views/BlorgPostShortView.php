<?php

require_once 'Blorg/views/BlorgPostView.php';
require_once 'Swat/SwatString.php';

/**
 * Short post display displays only the header and a condensed version of the
 * bodytext
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostShortView extends BlorgPostView
{
	// {{{ class constants

	const MAX_POST_LENGTH = 300;

	// }}}
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
		$bodytext = SwatString::ellipsizeRight(
			SwatString::condense($this->post->bodytext), self::MAX_POST_LENGTH);

		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'entry-content';
		$div_tag->setContent($bodytext, 'text/xml');
		$div_tag->display();
	}

	// }}}
}

?>
