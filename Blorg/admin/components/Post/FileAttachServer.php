<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Site/layouts/SiteXMLRPCServerLayout.php';
require_once 'SwatDB/SwatDB.php';

/**
 * Updates attachment status of files
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostFileAttachServer extends SiteXMLRPCServer
{
	// {{{ public function attach()

	/**
	 * Marks a file as attached
	 *
	 * @param integer $file_id the id of the file to mark as attached.
	 *
	 * @return boolean true.
	 */
	public function attach($file_id)
	{
		$sql = sprintf('update BlorgFile set show = %s where id = %s',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($file_id, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		return true;
	}

	// }}}
	// {{{ public function detach()

	/**
	 * Marks a file as not attached
	 *
	 * @param integer $file_id the id of the file to mark as not attached.
	 *
	 * @return boolean true.
	 */
	public function detach($file_id)
	{
		$sql = sprintf('update BlorgFile set show = %s where id = %s',
			$this->app->db->quote(false, 'boolean'),
			$this->app->db->quote($file_id, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		return true;
	}

	// }}}
}

?>
