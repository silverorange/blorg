<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePathPage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPostFullView.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

/**
 * Post page for Blörg
 *
 * Loads and displays a post and handles adding replies to a post.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostPage extends SitePathPage
{
	// {{{ protected properties

	/**
	 * @var BlorgPost
	 */
	protected $post;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new post page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 * @param integer $year
	 * @param string $month_name
	 * @param string $shortname
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout,
		$year, $month_name, $shortname)
	{
		parent::__construct($app, $layout);
		$this->initPost($year, $month_name, $shortname);
	}

	// }}}
	// {{{ public function build()

	public function build()
	{
		$this->buildNavBar();

		ob_start();
		$this->displayPost();
		$this->layout->data->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->getPath()->addEntriesToNavBar($this->layout->navbar);
		$path = $this->getPath()->__toString();

		if ($path == '') {
			$path = 'archive';
		} else {
			$path.= '/archive';
		}
		$this->layout->navbar->createEntry(Blorg::_('Archive'), $path);

		$date = clone $this->post->post_date;
		$date->convertTZ($this->app->default_time_zone);

		$path.= '/'.$date->getYear();
		$this->layout->navbar->createEntry($date->getYear(), $path);

		$month_title = $date->getMonthName();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];
		$path.= '/'.$month_name;
		$this->layout->navbar->createEntry($month_title, $path);

		$path.= '/'.$this->post->shortname;
		$this->layout->navbar->createEntry($this->post->title, $path);
	}

	// }}}
	// {{{ protected function displayPost()

	protected function displayPost()
	{
		$view = new BlorgPostFullView($this->app, $this->post);
		$view->display();
	}

	// }}}
	// {{{ protected function initPost()

	protected function initPost($year, $month_name, $shortname)
	{
		if (!array_key_exists($month_name, BlorgPageFactory::$months_by_name)) {
			throw new SiteNotFoundException('Post not found.');
		}

		// Date parsed from URL is in locale time.
		$date = new SwatDate();
		$date->setTZ($this->app->default_time_zone);
		$date->setYear($year);
		$date->setMonth(BlorgPageFactory::$months_by_name[$month_name]);
		$date->setDay(1);
		$date->setHour(0);
		$date->setMinute(0);
		$date->setSecond(0);

		$class_name = SwatDBClassMap::get('BlorgPost');
		$this->post = new $class_name();
		$this->post->setDatabase($this->app->db);
		if (!$this->post->loadByDateAndShortname($date, $shortname,
			$this->app->instance->getInstance())) {
			throw new SiteNotFoundException('Post not found.');
		}

		if (!$this->post->enabled) {
			throw new SiteNotFoundException('Post not found.');
		}
	}

	// }}}
}

?>
