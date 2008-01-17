<?php

require_once 'Admin/dataobjects/AdminUser.php';

/**
 * User account for an admin
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @see       AdminUser
 */
class BlorgAdminUser extends AdminUser
{
	// {{{ public properties

	/**
	 * Whether or not this user is shown on the front-end as an author
	 *
	 * @var boolean
	 */
	public $show;

	/**
	 * Short description of the user
	 *
	 * @var string
	 */
	public $description;

	/**
	 * A long form description of the user, for the author page
	 *
	 * @var string
	 */
	public $bodytext;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		parent::init();
	}

	// }}}
	// {{{ protected function loadPosts()

	/**
	 * Get's all the users posts
	 *
	 * @return BlorgPostWrapper
	 */
	protected function loadPosts()
	{
		$sql = sprintf('select * from BlorgPost where author = %s',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql, 'BlorgPostWrapper');
	}

	// }}}
}

?>
