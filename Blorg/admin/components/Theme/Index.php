<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'Blorg/admin/BlorgThemeDisplay.php';

/**
 * Page for selecting a theme
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgThemeIndex extends AdminPage
{
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = 'Blorg/admin/components/Theme/index.xml';

	/**
	 * @var array
	 */
	protected $themes;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
		$this->initThemes();
		$this->initThemeReplicator();
	}

	// }}}
	// {{{ protected function initThemes()

	protected function initThemes()
	{
		$current_theme = $this->app->config->site->theme;
		$themes = $this->app->theme->getAvailable();

		// sorts themes by title according to locale
		foreach ($themes as $theme) {
			if ($theme->getShortname() !== $current_theme) {
				$titles[$theme->getShortname()] = $theme->getTitle();
			}
		}

		asort($titles, SORT_LOCALE_STRING);

		// current theme is always placed at the top
		$this->themes = array($themes[$current_theme]);

		foreach ($titles as $shortname => $title) {
			$this->themes[] = $themes[$shortname];
		}
	}

	// }}}
	// {{{ protected function initThemeReplicator()

	protected function initThemeReplicator()
	{
		$theme_replicator = $this->ui->getWidget('theme_replicator');
		$theme_replicator->replication_ids = array_keys($this->themes);
	}

	// }}}

	// process phase
	// {{{ protected function processInternal()

	protected function processInternal()
	{
		parent::processInternal();

		$form = $this->ui->getWidget('form');

		$theme_replicator = $this->ui->getWidget('theme_replicator');
		if ($form->isProcessed()) {
			foreach ($theme_replicator->replication_ids as $shortname) {
				$theme_display = $theme_replicator->getWidget('theme',
					$shortname);

				if ($theme_display->hasBeenClicked()) {
					$this->updateTheme($shortname);
					$this->relocate();
				}
			}
		}
	}

	// }}}
	// {{{ protected function updateTheme()

	protected function updateTheme($shortname)
	{
		$theme = $this->themes[$shortname];

		// TODO: update theme

		$message = new SwatMessage(sprintf(
			Blorg::_('The theme “%s” has been selected.'),
			$theme->getTitle()));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function relocate()

	/**
	 * Relocate after process
	 */
	protected function relocate()
	{
		$this->app->relocate($this->source);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildMessages();
		$this->buildForm();
		$this->buildThemeReplicator();
	}

	// }}}
	// {{{ protected function buildThemeReplicator()

	protected function buildThemeReplicator()
	{
		$current_theme = $this->app->config->site->theme;
		$theme_replicator = $this->ui->getWidget('theme_replicator');
		foreach ($theme_replicator->replication_ids as $shortname) {
			$theme_display = $theme_replicator->getWidget('theme', $shortname);
			$theme_display->setTheme($this->themes[$shortname]);
			$theme_display->selected = ($shortname == $current_theme);
		}

		$theme_display->classes[] = 'blorg-theme-display-last';
	}

	// }}}
	// {{{ protected function buildForm()

	protected function buildForm()
	{
		$form = $this->ui->getWidget('form');
		$form->action = $this->source;
	}

	// }}}
}

?>
