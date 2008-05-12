<?php

require_once 'Swat/SwatTextarea.php';

/**
 * Control for displaying embed markup for files
 *
 * Auto-selects textarea on focus.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgMarkupTextarea extends SwatTextarea
{
	// {{{ protected function getTextareaTag()

	/**
	 * Adds auto-select functionality to the textarea tag used by this control
	 *
	 * @return SwatHtmlTag the textarea tag used by this textarea control.
	 */
	protected function getTextareaTag()
	{
		$tag = parent::getTextareaTag();
		$tag->onfocus = 'this.select();';
		return $tag;
	}

	// }}}
}

?>
