<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Blorg/dataobjects/BlorgReply.php';

/**
 * A recordset wrapper class for BlorgReply objects
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @see       BlorgReply
 */
class BlorgReplyWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('BlorgReply');
	}

	// }}}
}

?>
