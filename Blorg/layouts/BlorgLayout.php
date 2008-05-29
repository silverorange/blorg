<?php

require_once 'Swat/SwatNavBar.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatStyleSheetHtmlHeadEntry.php';
require_once 'Site/layouts/SiteLayout.php';

/**
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgLayout extends SiteLayout
{
	// {{{ public properties

	/**
	 * Page navigation bar
	 *
	 * @var SwatNavBar
	 */
	public $navbar;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->initNavBar();
	}

	// }}}
	// {{{ protected function initNavBar()

	protected function initNavBar()
	{
		$this->navbar = new SwatNavBar();
		$this->navbar->link_last_entry = false;
		$this->navbar->separator = ' › ';
		$this->navbar->id = 'nav_bar';
		$this->navbar->createEntry(Blorg::_('Home'), '.');
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->startCapture('google_analytics');
		$this->displayGoogleAnalytics();
		$this->endCapture();
	}

	// }}}
	// {{{ protected function displayGoogleAnalytics()

	protected function displayGoogleAnalytics()
	{
		$google_account = $this->app->config->analytics->google_account;

		if ($google_account !== null) {
			$src = ($this->app->isSecure()) ?
				'https://ssl.google-analytics.com/urchin.js' :
				'http://www.google-analytics.com/urchin.js';

			$script_tag = new SwatHtmlTag('script');
			$script_tag->type = 'text/javascript';
			$script_tag->src = $src;
			$script_tag->setContent('');
			$script_tag->display();

			$javascript = sprintf(
				"_uacct = '%s';\n".
				"urchinTracker();",
				$google_account);

			Swat::displayInlineJavaScript($javascript);
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->finalizeNavBar();
		$this->finalizeTitle();
	}

	// }}}
	// {{{ protected function finalizeNavBar()

	protected function finalizeNavBar()
	{
		if ($this->navbar->getCount() > 1) {
			$this->startCapture('navbar');
			$this->navbar->display();
			$this->endCapture();
			$this->addHtmlHeadEntrySet($this->navbar->getHtmlHeadEntrySet());
		} else {
			$this->data->navbar = '';
		}
	}

	// }}}
	// {{{ protected function finalizeTitle()

	protected function finalizeTitle()
	{
		$site_title = $this->app->config->site->title;
		$page_title = SwatString::stripXHTMLTags($this->data->title);

		if (strlen($page_title) > 0) {
			$this->data->html_title = sprintf('%s - %s',
				SwatString::minimizeEntities($page_title),
				SwatString::minimizeEntities($site_title));
		} elseif (strlen($this->data->html_title) > 0) {
			$this->data->html_title.= sprintf(' - %s',
				SwatString::minimizeEntities($site_title));
		} else {
			$this->data->html_title =
				SwatString::minimizeEntities($site_title);
		}
	}

	// }}}
}

?>
