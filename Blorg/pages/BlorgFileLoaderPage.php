<?php

require_once 'Site/pages/SitePage.php';
require_once 'Blorg/dataobjects/BlorgFile.php';

/**
 * Outputs a BlorgFile
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFileLoaderPage extends SitePage
{
	// {{{ protected properties

	protected $file;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		$filename)
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/fileloader.php');
		parent::__construct($app, $layout);
		$this->initFile($filename);
	}

	// }}}
	// {{{ protected function initFile()

	protected function initFile($filename)
	{
		$class_name = SwatDBClassMap::get('BlorgFile');
		$this->file = new $class_name();
		$this->file->setDatabase($this->app->db);
		if (!$this->file->loadByFilename($filename,
			$this->app->getInstance())) {
			throw new SiteNotFoundException('File not found.');
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		header(sprintf('Content-type: %s', $this->file->mime_type));
		header(sprintf('Content-Disposition: filename="%s"',
			$this->file->filename));

		if ($this->app->getInstance() === null) {
			$path = '../files/';
		} else {
			$path = '../files/'.$this->app->getInstance()->shortname.'/';
		}

		$this->file->setFileBase($path);
		readfile($this->file->getFilePath());
	}

	// }}}
}

?>
