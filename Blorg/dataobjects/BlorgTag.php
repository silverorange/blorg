<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A Blörg Tag
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 */
class BlorgTag extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique Identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Title of the tag. This is what is publicly displayed.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Auto-magically generated shortname for the tag
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * Date tag was created.
	 *
	 * @var Date
	 */
	public $createdate;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateProperty('createdate');

		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		$this->table = 'BlorgTag';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
