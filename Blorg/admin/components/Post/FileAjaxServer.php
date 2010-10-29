<?php

require_once 'Site/dataobjects/SiteImageWrapper.php';
require_once 'Site/dataobjects/SiteImageSetWrapper.php';
require_once 'Site/pages/SiteXMLRPCServer.php';
require_once 'Site/layouts/SiteXMLRPCServerLayout.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Blorg/dataobjects/BlorgFile.php';
require_once 'Blorg/dataobjects/BlorgFileWrapper.php';

/**
 * Performs actions on files via AJAX
 *
 * @package   Blörg
 * @copyright 2008-2010 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostFileAjaxServer extends SiteXMLRPCServer
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
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('update BlorgFile set visible = %s
			where instance %s %s and id = %s',
			$this->app->db->quote(true, 'boolean'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote($file_id, 'integer'));

		$num = SwatDB::exec($this->app->db, $sql);

		if ($num > 0 && isset($this->app->memcache)) {
			$this->app->memcache->flushNS('posts');
		}

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
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('update BlorgFile set visible = %s
			where instance %s %s and id = %s',
			$this->app->db->quote(false, 'boolean'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote($file_id, 'integer'));

		$num = SwatDB::exec($this->app->db, $sql);

		if ($num > 0 && isset($this->app->memcache)) {
			$this->app->memcache->flushNS('posts');
		}

		return true;
	}

	// }}}
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
				if (isset($this->app->memcache)) {
					$this->app->memcache->flushNS('posts');
				}
			}
		}

		return true;
	}

	// }}}
	// {{{ public function dir()

	/**
	 * Gets information about files attached to a post
	 *
	 * @param integer $post_id the id of the post. Use 0 if there is no post
	 *                          id.
	 * @param string $form_unique_id the id of form for use when the post has
	 *                                not yet been saved. Use an empty string if
	 *                                there is a post id.
	 *
	 * @return array a structure containing file info.
	 */
	public function dir($post_id, $form_unique_id)
	{
		$instance_id = $this->app->getInstanceId();

		// this is because XML-RPC has strict types.
		if ($form_unique_id == '') {
			$form_unique_id = null;
		} else {
			$post_id = null;
		}

		if ($this->app->getInstance() === null) {
			$path = '../../files';
		} else {
			$path = '../../files/'.$this->app->getInstance()->shortname;
		}

		$file_sql = sprintf('select * from BlorgFile
			where post %s %s and form_unique_id %s %s
			order by id',
			SwatDB::equalityOperator($post_id),
			$this->app->db->quote($post_id, 'integer'),
			SwatDB::equalityOperator($form_unique_id),
			$this->app->db->quote($form_unique_id, 'text'));

		$files = SwatDB::query(
			$this->app->db,
			$file_sql,
			SwatDBClassMap::get('BlorgFileWrapper'));

		// efficiently load images
		$image_sql = 'select * from Image where id in (%s)';
		$images = $files->loadAllSubDataObjects(
			'image',
			$this->app->db,
			$image_sql,
			SwatDBClassMap::get('SiteImageWrapper'));

		// efficiently load image sets
		$image_set_sql = 'select * from ImageSet where id in (%s)';
		$image_sets = $images->loadAllSubDataObjects(
			'image_set',
			$this->app->db,
			$image_set_sql,
			SwatDBClassMap::get('SiteImageSetWrapper'));

		// build response struct
		$response = array();

		foreach ($files as $file) {
			$utc = clone $file->createdate;
			$utc->toUTC();

			$local = clone $utc;
			$local->convertTZ($this->app->default_time_zone);

			$local = $local->formatLikeIntl('yyyy-MM-dd\'T\'hh:mm:ss');
			$utc   = $utc->formatLikeIntl('yyyy-MM-dd\'T\'hh:mm:ss');

			$info = array(
				'id'               => $file->id,
				'filename'         => $file->filename,
				'filepath'         => $path.$file->filename,
				'filesize'         => $file->filesize,
				'uri'              => $file->getRelativeUri(),
				'mime_type'        => $file->mime_type,
				'visible'          => $file->visible,
				'createdate_utc'   => $utc,
				'createdate_local' => $local,
				'description'      => $file->getDescription(),
				'images'           => array(),
			);

			if ($file->image instanceof SiteImage) {
				$image  = $file->image;
				$images = array();
				foreach ($image->image_set->dimensions as $dimension) {
					$images[$dimension->shortname] = array(
						'uri'    => $image->getUri($dimension->shortname),
						'width'  => $image->getWidth($dimension->shortname),
						'height' => $image->getHeight($dimension->shortname),
					);
				}
				$info['images'] = $images;
			}

			$response[] = $info;
		}

		return $response;
	}

	// }}}
}

?>
