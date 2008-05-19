<?php

require_once 'SwatDB/SwatDBRecordsetWrapper.php';
require_once 'Blorg/dataobjects/BlorgGadgetInstance.php';

/**
 * A recordset wrapper for gadget instances
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @see       BlorgGadgetInstance
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgGadgetInstanceWrapper extends SwatDBRecordsetWrapper
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->row_wrapper_class = SwatDBClassMap::get('BlorgGadgetInstance');
		$this->index_field = 'id';
	}

	// }}}
}

?>
