<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/BlorgViewFactory.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';
require_once 'Blorg/Blorg.php';

/**
 * Displays an index of all posts in a given month
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgMonthArchivePage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $year;

	/**
	 * @var integer
	 */
	protected $month;

	/**
	 * @var BlorgPostWrapper
	 */
	protected $posts;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new month archive page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 * @param integer $year
	 * @param string $month_name
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout,
		$year, $month_name)
	{
		parent::__construct($app, $layout);
		$this->initPosts($year, $month_name);
		$this->year = intval($year);
		$this->month = BlorgPageFactory::$months_by_name[$month_name];
	}

	// }}}
	// {{{ public function build()

	public function build()
	{
		$this->buildNavBar();

		$this->layout->startCapture('content');
		$this->displayPosts();
		$this->layout->endCapture();

		$this->layout->startCapture('html_head_entries');
		$this->displayAtomLinks();
		$this->layout->endCapture();

		$date = new SwatDate();
		$date->setYear($this->year);
		$date->setMonth($this->month);
		$date->setTZ($this->app->default_time_zone);
		$this->layout->data->title = $date->format(SwatDate::DF_MY);
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$path = $this->app->config->blorg->path.'archive';
		$this->layout->navbar->createEntry(Blorg::_('Archive'), $path);

		$path.= '/'.$this->year;
		$this->layout->navbar->createEntry($this->year, $path);

		$date = new SwatDate();
		$date->setMonth($this->month);
		$month_title = $date->getMonthName();
		$month_name = BlorgPageFactory::$month_names[$this->month];
		$path.= '/'.$month_name;
		$this->layout->navbar->createEntry($month_title, $path);
	}

	// }}}
	// {{{ protected function displayAtomLinks()

	protected function displayAtomLinks()
	{
		$link = new SwatHtmlTag('link');
		$link->rel = 'alternate';
		$link->href = $this->app->getBaseHref().
			$this->app->config->blorg->path.'atom';

		$link->type = 'application/atom+xml';
		$link->title = Blorg::_('Recent Posts');
		$link->display();

		echo "\n\t";

		$link = new SwatHtmlTag('link');
		$link->rel = 'alternate';
		$link->href = $this->app->getBaseHref().
			$this->app->config->blorg->path.'atom/replies';

		$link->type = 'application/atom+xml';
		$link->title = Blorg::_('Recent Replies');
		$link->display();

		echo "\n";
	}

	// }}}
	// {{{ protected function displayPosts()

	protected function displayPosts()
	{
		$view = BlorgViewFactory::build('post', $this->app);
		$view->showBodytext(BlorgPostView::SHOW_SUMMARY);
		$view->showExtendedBodytext(BlorgPostView::SHOW_NONE);
		foreach ($this->posts as $post) {
			$view->display($post);
		}
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts($year, $month_name)
	{
		if (!array_key_exists($month_name, BlorgPageFactory::$months_by_name)) {
			throw new SiteNotFoundException('Page not found.');
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

		$instance_id = $this->app->instance->getId();

		$sql = sprintf('select * from BlorgPost
			where date_trunc(\'month\', convertTZ(createdate, %s)) =
				date_trunc(\'month\', timestamp %s) and
				instance %s %s
				and enabled = true
			order by post_date desc',
			$this->app->db->quote($date->tz->getId(), 'text'),
			$this->app->db->quote($date->getDate(), 'date'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$this->posts = SwatDB::query($this->app->db, $sql, $wrapper);

		if (count($this->posts) == 0) {
			throw new SiteNotFoundException('Page not found.');
		}
	}

	// }}}
}

?>
