<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Admin/dataobjects/AdminUserWrapper.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Author for a Blörg site
 *
 * The <i>visible</i> flag on authors determines whether or not an author is
 * visible on the front-end of a Blörg site.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @see       AdminUser
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAuthor extends SwatDBDataObject
{
	// {{{ public properties

	/**
	 * Id of this author
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Full name of this author
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Short, textual identifier for this author used in URIs
	 *
	 * Often this will be a lowercase version of the author's first name. This
	 * identifier must be unique within a site instance.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * Email address of this author
	 *
	 * @var string
	 */
	public $email;

	/**
	 * Whether or not this user is shown on the front-end as an author
	 *
	 * @var boolean
	 */
	public $visible;

	/**
	 * Short description of this author, for sidebars and lists
	 *
	 * @var string
	 */
	public $description;

	/**
	 * A long description of this author, for the author details page
	 *
	 * @var string
	 */
	public $bodytext;

	// }}}
	// {{{ public function loadByShortname()

	/**
	 * Loads this author by the author's shortname
	 *
	 * @param string $shortname the shortname of the author to load.
	 * @param SiteInstance $instance optional. The instance to load the author
	 *                                in. If the application does not use
	 *                                instances, this should be null.
	 *
	 * @return boolean true if this author was loaded from the given shortname
	 *                 and false if it was not.
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
	// {{{ public function getVisiblePosts()

	/**
	 * Gets visible posts of this author
	 *
	 * @param integer $limit optional. Limit number of returned posts. If not
	 *                        specified, all posts are returned.
	 * @param integer $offset optional. Offset returned results. If not
	 *                         specified, results are not offset.
	 *
	 * @return BlorgPostWrapper the visible posts by this author.
	 */
	public function getVisiblePosts($limit = null, $offset = 0)
	{
		$sql = sprintf('select * from BlorgPost
			where author = %s and enabled = %s
			order by publish_date desc',
			$this->db->quote($this->id, 'integer'),
			$this->db->quote(true, 'boolean'));

		if ($limit !== null) {
			$this->db->setLimit($limit, $offset);
		}

		$wrapper_class = SwatDBClassMap::get('BlorgPostWrapper');
		return SwatDB::query($this->db, $sql, $wrapper_class);
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads this author
	 *
	 * @param integer $id the database id of this author.
	 * @param SiteInstance $instance optional. The instance to load the author
	 *                                in. If the application does not use
	 *                                instances, this should be null. If
	 *                                upsecified, the instance is not checked.
	 *
	 * @return boolean true if this author was loaded and false if it was not.
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
		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		$this->table = 'BlorgAuthor';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function loadPosts()

	/**
	 * Get's all the posts by this author for the specified site instance
	 *
	 * @return BlorgPostWrapper the posts by this author.
	 */
	protected function loadPosts()
	{
		$sql = sprintf('select * from BlorgPost
			where author = %s
			order by publish_date desc',
			$this->db->quote($this->id, 'integer'));

		$wrapper_class = SwatDBClassMap::get('BlorgPostWrapper');
		return SwatDB::query($this->db, $sql, $wrapper_class);
	}

	// }}}
}

?>
