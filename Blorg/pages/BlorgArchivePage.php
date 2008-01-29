<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePathPage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/Blorg.php';

/**
 * Displays an index of all years and months with posts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgArchivePage extends SitePathPage
{
	// {{{ protected properties

	/**
	 * Array of containing the years and months that contain posts
	 *
	 * The array is of the form:
	 * <code>
	 * <?php
	 * array(
	 *     2007 => array(9, 10, 11, 12),
	 *     2008 => array(1, 2),
	 * );
	 * ?>
	 * </code>
	 *
	 * @var array
	 */
	protected $years = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new archive page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$this->initYears();
	}

	// }}}
	// {{{ public function build()

	public function build()
	{
		$this->buildNavBar();

		ob_start();
		$this->displayArchive();
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
	}

	// }}}
	// {{{ protected function displayArchive()

	protected function displayArchive()
	{
		$base = (strlen($this->getPath())) ? $this->getPath().'/' : ''; // TODO

		$year_ul_tag = new SwatHtmLTag('ul');
		$year_ul_tag->class = 'blorg-archive-years';
		$year_ul_tag->open();
		foreach ($this->years as $year => $months) {
			$year_li_tag = new SwatHtmlTag('li');
			$year_li_tag->open();
			$year_anchor_tag = new SwatHtmlTag('a');
			$year_anchor_tag->href = sprintf('%sarchive/%s',
				$base, $year);

			$year_anchor_tag->setContent($year);
			$year_anchor_tag->display();

			$month_ul_tag = new SwatHtmlTag('ul');
			$month_ul_tag->open();
			foreach ($months as $month) {
				$date = new SwatDate();
				$date->setMonth($month);

				$month_li_tag = new SwatHtmlTag('li');
				$month_li_tag->open();
				$month_anchor_tag = new SwatHtmlTag('a');
				$month_anchor_tag->href = sprintf('%sarchive/%s/%s',
					$base, $year, BlorgPageFactory::$month_names[$month]);

				$month_anchor_tag->setContent($date->getMonthName());
				$month_anchor_tag->display();
				$month_li_tag->close();
			}
			$month_ul_tag->close();
			$year_li_tag->close();
		}
		$year_ul_tag->close();
	}

	// }}}
	// {{{ protected function initYears()

	protected function initYears()
	{
		$instance_id = $this->app->instance->getId();

		$sql = sprintf('select post_date from BlorgPost
				where instance %s %s and enabled = true
			order by post_date desc',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$rs = SwatDB::query($this->app->db, $sql, null);
		while ($date = $rs->fetchOne()) {
			$date = new SwatDate($date);
			$year = $date->getYear();
			$month = $date->getMonth();

			if (!array_key_exists($year, $this->years)) {
				$this->years[$year] = array();
			}

			if (!in_array($month, $this->years[$year])) {
				$this->years[$year][] = $month;
			}
		}

		if (count($this->years) == 0) {
			throw new SiteNotFoundException('Page not found');
		}
	}

	// }}}
}

?>
