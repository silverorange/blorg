<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A Blörg Tag
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
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
	 * @var SwatDate
	 */
	public $createdate;

	// }}}
	// {{{ public function getPostCount()

	/**
	 * Gets the number of posts this tag applies to
	 *
	 * This is more efficient than getting the set of posts and counting the
	 * set. Use this method if you don't need the actual post objects.
	 *
	 * @return integer the number of posts this tag applies to.
	 */
	public function getPostCount()
	{
		$sql = 'select count(id) from BlorgPost
			inner join BlorgPostTagBinding on id = post
			where tag = %s';

		return SwatDB::queryOne($this->db, sprintf($sql,
			$this->db->quote($this->id, 'integer')));
	}

	// }}}
	// {{{ public function getVisiblePostCount()

	/**
	 * Gets the number of visible posts this tag applies to
	 *
	 * This is more efficient than getting the set of posts and counting the
	 * set. Use this method if you don't need the actual post objects.
	 *
	 * @return integer the number of posts this tag applies to.
	 */
	public function getVisiblePostCount()
	{
		$sql = 'select count(id) from BlorgPost
			inner join BlorgPostTagBinding on id = post
			where tag = %s and BlorgPost.enabled = %s';

		return SwatDB::queryOne($this->db, sprintf($sql,
			$this->db->quote($this->id, 'integer'),
			$this->db->quote(true, 'boolean')));
	}

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads this tag by a tag shortname
	 *
	 * @param string $shortname the shortname of the tag to load.
	 * @param SiteInstance $instance optional. The instance in which to load
	 *                               tag. If the application does not use
	 *                               instances, this should be null.
	 *
	 * @return boolean true if this tag was loaded from the given shortname and
	 *                 false if it was not.
	 */
	public function loadByShortname($shortname, SiteInstance $instance = null)
	{
		$this->checkDB();

		$loaded = false;
		$row = null;
		if ($this->table !== null) {
			$instance_id  = ($instance === null) ? null : $instance->id;

			$sql = sprintf('select * from %s
				where shortname = %s and instance %s %s',
				$this->table,
				$this->db->quote($shortname, 'text'),
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
	 * Loads this tag
	 *
	 * @param integer $id the database id of this tag.
	 * @param SiteInstance $instance optional. The instance to load the tag in.
	 *                                If the application does not use instances,
	 *                                this should be null. If upsecified, the
	 *                                instance is not checked.
	 *
	 * @return boolean true if this tag was loaded and false if it was not.
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
				$sql.=sprintf(' and instance %s %s',
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
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateProperty('createdate');

		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		// for efficient loading of tag sets on post sets
		$this->registerInternalProperty('post');

		$this->table = 'BlorgTag';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function saveInternal()

	/**
	 * Saves this object to the database
	 *
	 * Only modified properties are updated.
	 */
	protected function saveInternal()
	{
		if ($this->id === null) {
			$this->shortname = $this->generateShortname($this->title);
			$this->createdate = new SwatDate();
			$this->createdate->toUTC();
		}

		parent::saveInternal();
	}

	// }}}
	// {{{ protected function generateShortname()

	/**
	 * Generate a shortname
	 *
	 * @param string $text Text to generate the shortname from.
	 * @return string A shortname.
	 */
	protected function generateShortname($text)
	{
		$shortname_base = SwatString::condenseToName($text);
		$count = 1;
		$shortname = $shortname_base;

		while ($this->validateShortname($shortname) === false)
			$shortname = $shortname_base.$count++;

		return $shortname;
	}

	// }}}
	// {{{ protected function validateShortname()

	protected function validateShortname($shortname)
	{
		$valid = true;

		$class_name = SwatDBClassMap::get('BlorgTag');
		$tag = new $class_name();
		$tag->setDatabase($this->db);

		if ($tag->loadByShortName($shortname))
			if ($tag->id !== $this->id)
				$valid = false;

		return $valid;
	}

	// }}}
}

?>
