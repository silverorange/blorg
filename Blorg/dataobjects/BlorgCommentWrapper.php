<?php

/**
 * A recordset wrapper class for BlorgComment objects
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @see       SiteCommentWrapper
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentWrapper extends SiteCommentWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('BlorgComment');
	}

	// }}}
}

?>
