<?php

require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Site/layouts/SiteXMLRPCServerLayout.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Blorg/dataobjects/BlorgFile.php';

/**
 * Used to delete the header image with AJAX.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgConfigHeaderImageAjaxServer extends SiteXMLRPCServer
{
	// {{{ public function delete()

	/**
	 * Deletes a file
	 *
	 * @param integer $file_id the id of the file to delete.
	 *
	 * @return boolean true.
	 */
	public function delete($file_id)
	{
		$instance_id = $this->app->getInstanceId();

		if ($this->app->getInstance() === null) {
			$path = '../../files';
		} else {
			$path = '../../files/'.$this->app->getInstance()->shortname;
		}

		$class_name = SwatDBClassMap::get('BlorgFile');
		$file = new $class_name();
		$file->setDatabase($this->app->db);
		$file->setFileBase($path);
		if ($file->load(intval($file_id))) {
			if ($file->getInternalValue('instance') === $instance_id) {
				$file->delete();
			}
		}

		$this->app->config->blorg->header_image = '';
		$this->app->config->save();

		return true;
	}

	// }}}
}

?>
