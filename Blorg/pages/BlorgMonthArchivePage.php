<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/SiteViewFactory.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/BlorgPostLoader.php';

/**
 * Displays an index of all posts in a given month
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
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

	public function __construct(
		SiteApplication $app,
		SiteLayout $layout = null,
		array $arguments = array()
	) {
		parent::__construct($app, $layout, $arguments);

		$year = $this->getArgument('year');
		$month_name = $this->getArgument('month_name');

		$this->initPosts($year, $month_name);
		$this->year = intval($year);
		$this->month = BlorgPageFactory::$months_by_name[$month_name];
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'year' => array(0, null),
			'month_name' => array(1, null),
		);
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts($year, $month_name)
	{
		if (!array_key_exists($month_name, BlorgPageFactory::$months_by_name)) {
			throw new SiteNotFoundException('Page not found.');
		}

		// Date parsed from URL is in locale time.
		$month = BlorgPageFactory::$months_by_name[$month_name];
		$date = new SwatDate();
		$date->setTZ($this->app->default_time_zone);
		$date->setDate($year, $month, 1);
		$date->setTime(0, 0, 0);

		$memcache = (isset($this->app->memcache)) ? $this->app->memcache : null;
		$loader = new BlorgPostLoader($this->app->db,
			$this->app->getInstance(), $memcache);

		$loader->addSelectField('title');
		$loader->addSelectField('bodytext');
		$loader->addSelectField('shortname');
		$loader->addSelectField('publish_date');
		$loader->addSelectField('author');
		$loader->addSelectField('comment_status');
		$loader->addSelectField('visible_comment_count');

		$loader->setLoadFiles(true);
		$loader->setLoadTags(true);

		$loader->setWhereClause(sprintf('enabled = %s and
			date_trunc(\'month\', convertTZ(publish_date, %s)) =
				date_trunc(\'month\', timestamp %s)',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote(
				$this->app->default_time_zone->getName(), 'text'),
			$this->app->db->quote($date->getDate(), 'date')));

		$loader->setOrderByClause('publish_date desc');

		$this->posts = $loader->getPosts();

		if (count($this->posts) == 0) {
			throw new SiteNotFoundException('Page not found.');
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		if (isset($this->layout->navbar))
			$this->buildNavBar();

		$this->layout->startCapture('content');
		Blorg::displayAd($this->app, 'top');
		$this->displayPosts();
		Blorg::displayAd($this->app, 'bottom');
		$this->layout->endCapture();

		$date = new SwatDate();
		$date->setDate($this->year, $this->month, 1);
		$date->setTZ($this->app->default_time_zone);
		$this->layout->data->title = $date->formatLikeIntl(SwatDate::DF_MY);
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
		$date->setDate(2010, $this->month, 1);
		$month_title = $date->getMonthName();
		$month_name = BlorgPageFactory::$month_names[$this->month];
		$path.= '/'.$month_name;
		$this->layout->navbar->createEntry($month_title, $path);
	}

	// }}}
	// {{{ protected function displayPosts()

	protected function displayPosts()
	{
		$view = SiteViewFactory::get($this->app, 'post');
		$view->setPartMode('bodytext', SiteView::MODE_SUMMARY);
		$view->setPartMode('extended_bodytext', SiteView::MODE_NONE);
		$first = true;
		foreach ($this->posts as $post) {
			if ($first) {
				$first_div = new SwatHtmlTag('div');
				$first_div->class = 'entry-first';
				$first_div->open();
				$view->display($post);
				$first_div->close();
				$first = false;
			} else {
				$view->display($post);
			}
		}
	}

	// }}}
}

?>
