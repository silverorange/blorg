<?php

/**
 * Lists file images
 *
 * @package   Blörg
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostFileImageServer extends AdminPage
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $post_id;

	/**
	 * @var string
	 */
	protected $form_unqiue_id;

	// }}}
	// {{{ public function __construct()

	public function __construct(
		SiteApplication $app,
		SiteLayout $layout = null,
		array $arguments = array()
	) {
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/json.php');
		parent::__construct($app, $layout, $arguments);
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->post_id = SiteApplication::initVar(
			'post_id',
			null,
			SiteApplication::VAR_GET);

		$this->form_unique_id = SiteApplication::initVar(
			'form_unique_id',
			null,
			SiteApplication::VAR_GET);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$response = $this->getImages($this->post_id, $this->form_unique_id);
		$this->layout->data->content = json_encode($response);
	}

	// }}}
	// {{{ protected function getImages()

	/**
	 * Gets information about image files attached to a post
	 *
	 * @param integer $post_id the id of the post.
	 * @param string $form_unique_id the id of form for use when the post has
	 *                                not yet been saved.
	 *
	 * @return array a structure containing file info.
	 */
	protected function getImages($post_id, $form_unique_id)
	{
		$instance_id = $this->app->getInstanceId();

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

		if (count($files) > 0) {

			// efficiently load images
			$image_sql = 'select * from Image where id in (%s)';
			$images = $files->loadAllSubDataObjects(
				'image',
				$this->app->db,
				$image_sql,
				SwatDBClassMap::get('SiteImageWrapper'));

			if (count($images) > 0) {

				// efficiently load image sets
				$image_set_sql = 'select * from ImageSet where id in (%s)';
				$image_sets = $images->loadAllSubDataObjects(
					'image_set',
					$this->app->db,
					$image_set_sql,
					SwatDBClassMap::get('SiteImageSetWrapper'));

			}

		}

		// build response struct
		$response = array();

		foreach ($files as $file) {
			if ($file->image instanceof SiteImage) {

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

				$image  = $file->image;
				$images = array();
				foreach ($image->image_set->dimensions as $dimension) {
					$images[$dimension->shortname] = array(
						'title'  => $dimension->title,
						'uri'    => $image->getUri($dimension->shortname),
						'width'  => $image->getWidth($dimension->shortname),
						'height' => $image->getHeight($dimension->shortname),
					);
				}
				$info['images'] = $images;

				$response[] = $info;
			}
		}

		return $response;
	}

	// }}}
}

?>
