<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Blorg/dataobjects/BlorgAuthor.php';

/**
 * A recordset wrapper class for BlorgAuthor objects
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @see       BlorgAuthor
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAuthorWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('BlorgAuthor');
		$this->index_field = 'id';
	}

	// }}}
}

?>
