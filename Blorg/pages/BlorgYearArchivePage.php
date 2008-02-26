<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePathPage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/BlorgViewFactory.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Displays an index of all months with posts in a given year
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgYearArchivePage extends SitePathPage
{
	// {{{ protected properties

	/**
	 * @var integer
	 */
	protected $year;

	/**
	 * Associative array with array keys containing the months of the specified
	 * year that contain posts and values being an array of posts in the
	 * month
	 *
	 * @var array
	 */
	protected $months = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new year archive page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 * @param integer $year
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout,
		$year)
	{
		parent::__construct($app, $layout);
		$this->initMonths($year);
		$this->year = intval($year);
	}

	// }}}
	// {{{ public function build()

	public function build()
	{
		$this->buildNavBar();

		$this->layout->startCapture('content');
		$this->displayMonths();
		$this->layout->endCapture();

		$this->layout->startCapture('html_head_entries');
		$this->displayAtomLinks();
		$this->layout->endCapture();

		$this->layout->data->title = $this->year;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$path = $this->app->config->blorg->path.'archive';
		$this->layout->navbar->createEntry(Blorg::_('Archive'), $path);

		$path.= '/'.$this->year;
		$this->layout->navbar->createEntry($this->year, $path);
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
	// {{{ protected function displayMonths()

	protected function displayMonths()
	{
		$path = $this->app->config->blorg->path.'archive';

		$view = BlorgViewFactory::build('post', $this->app);

		$view->showTitle(BlorgPostView::SHOW_SUMMARY);
		$view->showBodytext(BlorgPostView::SHOW_NONE);
		$view->showExtendedBodytext(BlorgPostView::SHOW_NONE);
		$view->showTags(BlorgPostView::SHOW_NONE);

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->class = 'months';
		$ul_tag->open();
		foreach ($this->months as $month => $posts) {
			$li_tag = new SwatHtmlTag('li');
			$li_tag->open();

			$date = new SwatDate();
			$date->setMonth($month);

			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = sprintf('%s/%s/%s',
				$path,
				$this->year,
				BlorgPageFactory::$month_names[$month]);

			$anchor_tag->setContent($date->getMonthName());
			$anchor_tag->display();

			$post_ul_tag = new SwatHtmlTag('ul');
			$post_ul_tag->class = 'entries';
			$post_ul_tag->open();

			foreach ($posts as $post) {
				$post_li_tag = new SwatHtmlTag('li');
				$post_li_tag->open();

				$view->display($post);

				$post_li_tag->close();
			}

			$post_ul_tag->close();

			$li_tag->close();
		}
		$ul_tag->close();
	}

	// }}}
	// {{{ protected function initMonths()

	protected function initMonths($year)
	{
		// Date parsed from URL is in locale time.
		$date = new SwatDate();
		$date->setTZ($this->app->default_time_zone);
		$date->setYear($year);
		$date->setMonth(1);
		$date->setDay(1);
		$date->setHour(0);
		$date->setMinute(0);
		$date->setSecond(0);

		$instance_id = $this->app->instance->getId();

		$sql = sprintf('select id, title, bodytext, shortname, post_date,
				reply_status
			from BlorgPost
			where date_trunc(\'year\', convertTZ(createdate, %s)) =
				date_trunc(\'year\', timestamp %s) and
				instance %s %s
				and enabled = true
			order by post_date desc',
			$this->app->db->quote($date->tz->getId(), 'text'),
			$this->app->db->quote($date->getDate(), 'date'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$posts = SwatDB::query($this->app->db, $sql, $wrapper);
		foreach ($posts as $post) {
			$month = $post->post_date->getMonth();
			if (!array_key_exists($month, $this->months)) {
				$this->months[$month] = array();
			}
			$this->months[$month][] = $post;
		}

		if (count($this->months) == 0) {
			throw new SiteNotFoundException('Page not found');
		}
	}

	// }}}
}

?>
