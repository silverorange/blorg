<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

/**
 * A recordset wrapper class for BlorgPost objects
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @see       BlorgPost
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('BlorgPost');
		$this->index_field = 'id';
	}

	// }}}
}

?>
