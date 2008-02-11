<?php

require_once 'Swat/SwatCellRenderer.php';

/**
 * A cell renderer for rendering visibility of Blörg replies
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgReplyVisibilityCellRenderer extends SwatCellRenderer
{
	// {{{ public properties

	public $show;
	public $approved;
	public $spam;

	// }}}
	// {{{ public function render

	public function render()
	{
		if ($this->spam) {
			$color = 'Red';
			$alt = 'Spam';

		} elseif (!$this->show && !$this->approved) {
			$color = 'Red';
			$alt = 'Not Approved';

		} elseif (!$this->approved) {
			$color = 'Yellow';
			$alt = 'Needs Approval';

		} else {
			$color = 'Green';
			$alt = 'Shown on Site';
		}

		echo $color.' '.$alt;
	}

	// }}}
}

?>
