<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

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
	}

	// }}}
	// {{{ public function build()

	public function build()
	{
		ob_start();
		foreach ($this->posts as $post) {
			$post->displayFull();
		}
		$this->layout->data->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts($year, $month_name)
	{
		$months_by_name = array(
			'january'   => 1,
			'february'  => 2,
			'march'     => 3,
			'april'     => 4,
			'may'       => 5,
			'june'      => 6,
			'july'      => 7,
			'august'    => 8,
			'september' => 9,
			'october'   => 10,
			'november'  => 11,
			'december'  => 12,
		);

		if (!array_key_exists($month_name, $months_by_name)) {
			throw new SiteNotFoundException('Page not found.');
		}

		// Date parsed from URL is in locale time.
		$date = new SwatDate();
		$date->setTZ($this->app->default_time_zone);
		$date->setYear($year);
		$date->setMonth($months_by_name[$month_name]);
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
