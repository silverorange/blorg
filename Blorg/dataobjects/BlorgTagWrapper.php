<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Blorg/dataobjects/BlorgTag.php';

/**
 * A recordset wrapper class for BlorgTag objects
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @see       BlorgTag
 */
class BlorgTagWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('BlorgTag');
	}

	// }}}
}

?>
