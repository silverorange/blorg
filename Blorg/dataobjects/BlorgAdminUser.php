<?php

require_once 'Admin/dataobjects/AdminUser.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Author for a Blörg site
 *
 * Authors are also admin users. The <i>show</i> flag on authors determines
 * whether or not an author is visible on the front-end of a Blörg site.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @see       AdminUser
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAdminUser extends AdminUser
{
	// {{{ protected properties

	/**
	 * Array of BlorgPostWrapper objects indexed by instance id
	 *
	 * @var array
	 *
	 * @see BlorgAdminUser::getPosts()
	 */
	protected $posts_by_instance = array();

	// }}}
	// {{{ public properties

	/**
	 * Whether or not this user is shown on the front-end as an author
	 *
	 * @var boolean
	 */
	public $show;

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

	/**
	 * Short, textual identifier for this author used in URIs
	 *
	 * Often this will be a lowercase version of the author's first name.
	 *
	 * @var string
	 */
	public $shortname;

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
	// {{{ public function getPosts()

	/**
	 * Get's all the posts by this author for the specified site instance
	 *
	 * @param SiteInstance $instance optional. The instance to get the posts
	 *                                from. If the application does not use
	 *                                instances, this should be specified as
	 *                                null.
	 *
	 * @return BlorgPostWrapper the posts by this author in the specified site
	 *                          instance.
	 */
	public function getPosts(SiteInstance $instance = null)
	{
		$instance_id = ($instance === null) ? null : $instance->id;

		if (!array_key_exists($instance_id, $this->posts_by_instance)) {
			$sql = sprintf('select * from BlorgPost
				where instance %s %s and author = %s',
				SwatDB::equalityOperator($instance_id),
				$this->db->quote($instance_id, 'integer'),
				$this->db->quote($this->id, 'integer'));

			$wrapper_class = SwatDBClassMap::get('BlorgPostWrapper');
			return SwatDB::query($this->db, $sql, $wrapper_class);
		}

		return $this->posts_by_instance[$instance_id];
	}

	// }}}
}

?>
