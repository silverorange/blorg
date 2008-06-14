<?php

require_once 'Site/layouts/SiteLayout.php';
require_once 'Admin/pages/AdminPage.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';

/**
 * Theme thumbnail image loader
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgThemeImageLoader extends AdminPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $theme;

	// }}}
	// {{{ protected function createLayout()

	protected function createLayout()
	{
		return new SiteLayout($this->app, 'Site/layouts/xhtml/fileloader.php');
	}

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->theme = SiteApplication::initVar('theme');

		if ($this->theme == '') {
			throw new AdminNotFoundException('No theme specified.');
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$themes = $this->app->theme->getAvailable();

		if (!array_key_exists($this->theme, $themes)) {
			throw new AdminNotFoundException(sprintf(
				'Theme image not found: ‘%s’.', $this->theme));
		}

		$theme = $themes[$this->theme];

		if (!$theme->fileExists('thumbnail.png')) {
			throw new AdminNotFoundException(sprintf(
				'Theme image not found: ‘%s’.', $this->theme));
		}

		header('Content-Type: image/png');

		readfile($theme->getPath().'/thumbnail.png', true);

		ob_flush();

		exit();
	}

	// }}}
}

?>
