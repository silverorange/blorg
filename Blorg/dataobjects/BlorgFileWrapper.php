<?php

/**
 * A recordset wrapper class for BlorgFile objects
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @see       BlorgFile
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFileWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('BlorgFile');
		$this->index_field = 'id';
	}

	// }}}
}

?>
