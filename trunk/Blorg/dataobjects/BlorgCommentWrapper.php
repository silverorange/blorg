<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Blorg/dataobjects/BlorgComment.php';

/**
 * A recordset wrapper class for BlorgComment objects
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @see       BlorgComment
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('BlorgComment');
		$this->index_field = 'id';
	}

	// }}}
}

?>
