<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePathPage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/Blorg.php';

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
	 * Array of integers containing the months of the specified year that
	 * contain posts
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

		ob_start();
		$this->displayMonths();
		$this->layout->data->content = ob_get_clean();
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
		$path = $this->getPath();
		$path->appendEntry(
			new SitePathEntry(null, null, 'archive', Blorg::_('Archive')));

		$path->appendEntry(
			new SitePathEntry(null, null, $this->year, $this->year));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$first = true;
		$link = '';
		foreach ($this->getPath() as $path_entry) {
			if ($first) {
				$link.= $path_entry->shortname;
				$first = false;
			} else {
				$link.= '/'.$path_entry->shortname;
			}

			$this->layout->navbar->createEntry($path_entry->title, $link);
		}
	}

	// }}}
	// {{{ protected function displayMonths()

	protected function displayMonths()
	{
		$base = 'news/'; // TODO

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->class = 'months';
		$ul_tag->open();
		foreach ($this->months as $month) {
			$li_tag = new SwatHtmlTag('li');
			$li_tag->open();

			$date = new SwatDate();
			$date->setMonth($month);

			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = sprintf('%sarchive/%s/%s',
				$base, $this->year, BlorgPageFactory::$month_names[$month]);

			$anchor_tag->setContent($date->getMonthName());
			$anchor_tag->display();

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

		$sql = sprintf('select post_date from BlorgPost
			where date_trunc(\'year\', convertTZ(createdate, %s)) =
				date_trunc(\'year\', timestamp %s) and
				instance %s %s
				and enabled = true
			order by post_date desc',
			$this->app->db->quote($date->tz->getId(), 'text'),
			$this->app->db->quote($date->getDate(), 'date'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$rs = SwatDB::query($this->app->db, $sql, null);
		while ($date = $rs->fetchOne()) {
			$date = new SwatDate($date);
			$month = $date->getMonth();
			if (!in_array($month, $this->months)) {
				$this->months[] = $month;
			}
		}

		if (count($this->months) == 0) {
			throw new SiteNotFoundException('Page not found');
		}
	}

	// }}}
}

?>
