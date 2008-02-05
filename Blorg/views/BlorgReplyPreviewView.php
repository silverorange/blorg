<?php

require_once 'Blorg/views/BlorgReplyView.php';

/**
 * Displays a reply with a preview warning
 *
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgReplyPreviewView extends BlorgReplyView
{
	// {{{ public function display()

	public function display()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'reply'.$this->reply->id;
		$div_tag->class = 'reply';
		$div_tag->open();

		$this->displayPreviewWarning();
		$this->displayHeader();
		$this->displayBody();

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayPreviewWarning()

	protected function displayPreviewWarning()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'entry-reply-preview';
		$div_tag->setContent(
			Blorg::_('This is a preview. Your reply has not yet been saved.'));

		$div_tag->display();
	}

	// }}}

}

?>
