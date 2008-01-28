<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A media attachment to a post
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgMedia extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique Identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Filename of the media
	 *
	 * @var string
	 */
	public $filename;

	/**
	 * Size of the file in bytes
	 *
	 * @var integer
	 */
	public $filesize;

	/**
	 * Mime type of the file.
	 *
	 * @var string
	 */
	public $mime_type;

	/**
	 * A description of what the media contains.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Whether or not the media is available on the site.
	 *
	 * @var boolean
	 */
	public $enabled;

	/**
	 * Date the media entry was added.
	 *
	 * @var Date
	 */
	public $createdate;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateProperty('createdate');

		$this->registerInternalProperty('post',
			SwatDBClassMap::get('BlorgPost'));

		$this->table = 'BlorgMedia';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
