<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A Blörg Tag
 *
 * @package   Blörg
 * @copyright 2008 silverorange
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
	 * @var Date
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
