<?php

require_once 'Swat/SwatCellRenderer.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

/**
 * Cell renderer that displays the current comment status of a post
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentStatusCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	public $status;

	// }}}
	// {{{ public function render()

	public function render()
	{
		echo BlorgPost::getCommentStatusTitle($this->status);
	}

	// }}}
}

?>
