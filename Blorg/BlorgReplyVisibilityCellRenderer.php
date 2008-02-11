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

	public $status;
	public $spam;

	// }}}
	// {{{ public function render

	public function render()
	{
		if ($this->spam) {
			$color = 'Red';
			$alt = Blorg::_('Spam');

		} else {
			$alt = BlorgReply::getStatusTitle($this->status);

			switch ($this->status) {
			case (BlorgReply::STATUS_UNPUBLISHED):
				$color = 'Red';
				break;

			case (BlorgReply::STATUS_PUBLISHED):
				$color = 'Green';
				break;

			case (BlorgReply::STATUS_PENDING):
				$color = 'Yellow';
				break;
			}
		}

		echo $color.' '.$alt;
	}

	// }}}
}

?>
