<?php

/**
 * A file attachment to a post
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
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
	public $visible;

	/**
	 * Date the file entry was added.
	 *
	 * @var SwatDate
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
	// {{{ private properties

	private $file_base;

	// }}}
	// {{{ public function getDescription()

	public function getDescription()
	{
		if ($this->description === null)
			return $this->filename;
		else
			return $this->description;
	}

	// }}}
	// {{{ public function getRelativeUri()

	public function getRelativeUri($blorg_path = '', $prefix = null)
	{
		$uri = $blorg_path.'file/'.$this->filename;

		if ($prefix !== null)
			$uri = $prefix.$uri;

		return $uri;
	}

	// }}}
	// {{{ public function setFileBase()

	public function setFileBase($path)
	{
		$this->file_base = $path;
	}

	// }}}
	// {{{ public function getFilePath()

	public function getFilePath()
	{
		return $this->getFileBase().'/'.$this->filename;
	}

	// }}}
	// {{{ public function loadByFilename()

	/**
	 * Loads a file by its filename
	 *
	 * @param string $filename the filename of the file to load.
	 * @param SiteInstance $instance optional. The instance to load the file in.
	 *                               If the site does not use instances, this
	 *                               should be null.
	 *
	 * @return boolean true if this file was loaded and false if it was not.
	 */
	public function loadByFilename($filename, SiteInstance $instance = null)
	{
		$this->checkDB();

		$loaded = false;
		$row = null;
		if ($this->table !== null) {
			$instance_id  = ($instance === null) ? null : $instance->id;

			$sql = sprintf('select * from %s
				where filename = %s and instance %s %s',
				$this->table,
				$this->db->quote($filename, 'text'),
				SwatDB::equalityOperator($instance_id),
				$this->db->quote($instance_id, 'integer'));

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads this file
	 *
	 * @param integer $id the database id of this file.
	 * @param SiteInstance $instance optional. The instance to load the file in.
	 *                                If the application does not use instances,
	 *                                this should be null. If unspecified,
	 *                                the instance is not checked.
	 *
	 * @return boolean true if this file was loaded and false if it was not.
	 */
	public function load($id, SiteInstance $instance = null)
	{
		$this->checkDB();

		$loaded = false;
		$row = null;
		if ($this->table !== null && $this->id_field !== null) {
			$id_field = new SwatDBField($this->id_field, 'integer');

			$sql = sprintf('select * from %s where %s = %s',
				$this->table,
				$id_field->name,
				$this->db->quote($id, $id_field->type));

			$instance_id  = ($instance === null) ? null : $instance->id;
			if ($instance_id !== null) {
				$sql.= sprintf(' and instance %s %s',
					SwatDB::equalityOperator($instance_id),
					$this->db->quote($instance_id, 'integer'));
			}

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ public function createFileBase()

	public function createFileBase()
	{
		$path = $this->getFileBase();

		$create_parts = array();
		while (!file_exists($path) && strpos($path, '/') !== false) {
			$directory = substr($path, strrpos($path, '/') + 1);
			$path      = substr($path, 0, strrpos($path, '/'));
			array_unshift($create_parts, $directory);
		}

		foreach ($create_parts as $directory) {
			$path = $path.'/'.$directory;
			mkdir($path);
		}
	}

	// }}}
	// {{{ protected function getFileBase()

	protected function getFileBase()
	{
		if ($this->file_base === null)
			throw new SwatException('File base has not been set on the '.
				'dataobject. Set the path to the webroot using '.
				'setFileBase().');

		return $this->file_base;
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

		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		$this->table = 'BlorgFile';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function deleteInternal()

	/**
	 * Deletes this object from the database
	 */
	protected function deleteInternal()
	{
		$filename = $this->getFilePath();

		if ($this->image !== null) {
			$this->image->setFileBase($this->getFileBase());
			$this->image->delete();
		}

		parent::deleteInternal();

		if (file_exists($filename))
			unlink($filename);
	}

	// }}}
}

?>
