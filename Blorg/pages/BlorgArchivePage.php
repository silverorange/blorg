<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';

/**
 * Displays an index of all years and months with posts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgArchivePage extends SitePage
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
		ob_start();
		foreach ($this->years as $year => $months) {
			echo $year, '<br/>';
			foreach ($months as $month) {
				echo $month, ', ';
			}
			echo '<br/>';
		}
		$this->layout->data->content = ob_get_clean();
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
