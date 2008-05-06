<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/dataobjects/BlorgFileImage.php';

/**
 * A file attachment to a post
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFile extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Unique Identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Filename of the file
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
	 * A description of what the file contains.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Whether or not the file is automatically linked on the site.
	 *
	 * @var boolean
	 */
	public $show;

	/**
	 * Date the file entry was added.
	 *
	 * @var Date
	 */
	public $createdate;

	/**
	 * Form unique id
	 *
	 * A unique identifier for the form being used to upload. This property is
	 * used to attach files to a post that has not yet been saved. On saving
	 * the {@link BlorgPost} dataobject, the unqiue id will be used to update
	 * the post property and then be set to null.
	 *
	 * @var string
	 */
	public $form_unique_id;

	// }}}
	// {{{ public function getRelativeUri()

	public function getRelativeUri($prefix = null)
	{
		$uri = 'files/'.$this->filename;

		if ($prefix !== null)
			$uri = $prefix.$uri;

		return $uri;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateProperty('createdate');

		$this->registerInternalProperty('post',
			SwatDBClassMap::get('BlorgPost'));

		$this->registerInternalProperty('image',
			SwatDBClassMap::get('BlorgFileImage'));

		$this->table = 'BlorgFile';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
