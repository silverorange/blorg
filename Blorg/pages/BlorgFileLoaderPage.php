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
		array $arguments = array())
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/fileloader.php');
		parent::__construct($app, $layout);
		$this->initFile($this->getArgument('filename'));
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'filename' => array(0, null),
		);
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
		if ($this->app->getInstance() === null) {
			$path = '../files/';
		} else {
			$path = '../files/'.$this->app->getInstance()->shortname.'/';
		}

		$this->file->setFileBase($path);
		$full_filename = $this->file->getFilePath();

		header(sprintf('Content-Length: %s', filesize($full_filename)));
		header(sprintf('Content-Type: %s', $this->file->mime_type));
		header(sprintf('Content-Disposition: filename="%s"',
			$this->file->filename));

		readfile($full_filename);
	}

	// }}}
}

?>
