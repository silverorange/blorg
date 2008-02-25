<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Site/pages/SitePage.php';
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
class BlorgArchivePage extends SitePage
{
	// {{{ protected properties

	/**
	 * Array of containing the years and months that contain posts as well
	 * as yearly and monthly post counts
	 *
	 * The array is of the form:
	 * <code>
	 * <?php
	 * array(
	 *     2007 => array(
	 *         'post_count' => 7,
	 *         'months'     => array(12 => 1, 11 => 2, 10 => 1, 9 => 3),
	 *     ),
	 *     2008 => array(
	 *         'post_count' => 3,
	 *         'months'     => array(2 => 1, 1 => 2),
	 *     ),
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

		$this->layout->startCapture('content');
		$this->displayArchive();
		$this->layout->endCapture();

		$this->layout->startCapture('html_head_entries');
		$this->displayAtomLinks();
		$this->layout->endCapture();

		$this->layout->data->title = Blorg::_('Archive');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$path = $this->app->config->blorg->path.'archive';
		$this->layout->navbar->createEntry(Blorg::_('Archive'), $path);
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
		$link->title = sprintf(Blorg::_('%s - Recent Posts'),
			$this->app->config->site->title);

		$link->display();

		echo "\n\t";

		$link = new SwatHtmlTag('link');
		$link->rel = 'alternate';
		$link->href = $this->app->getBaseHref().
			$this->app->config->blorg->path.'atom/replies';

		$link->type = 'application/atom+xml';
		$link->title = sprintf(Blorg::_('%s - Recent Replies'),
			$this->app->config->site->title);

		$link->display();

		echo "\n";
	}

	// }}}
	// {{{ protected function displayArchive()

	protected function displayArchive()
	{
		$path = $this->app->config->blorg->path.'archive';
		$locale = SwatI18NLocale::get();

		$year_ul_tag = new SwatHtmLTag('ul');
		$year_ul_tag->class = 'blorg-archive-years';
		$year_ul_tag->open();
		foreach ($this->years as $year => $values) {
			$year_li_tag = new SwatHtmlTag('li');
			$year_li_tag->open();
			$year_anchor_tag = new SwatHtmlTag('a');
			$year_anchor_tag->href = sprintf('%s/%s',
				$path,
				$year);

			$year_anchor_tag->setContent($year);
			$year_anchor_tag->display();

			$post_count_span = new SwatHtmlTag('span');
			$post_count_span->setContent(sprintf(
				Blorg::ngettext(' (%s post)', ' (%s posts)',
				$values['post_count']),
				$locale->formatNumber($values['post_count'])));

			$post_count_span->display();

			$month_ul_tag = new SwatHtmlTag('ul');
			$month_ul_tag->open();
			foreach ($values['months'] as $month => $post_count) {
				$date = new SwatDate();
				$date->setMonth($month);

				$month_li_tag = new SwatHtmlTag('li');
				$month_li_tag->open();
				$month_anchor_tag = new SwatHtmlTag('a');
				$month_anchor_tag->href = sprintf('%s/%s/%s',
					$path,
					$year,
					BlorgPageFactory::$month_names[$month]);

				$month_anchor_tag->setContent($date->getMonthName());
				$month_anchor_tag->display();

				$post_count_span = new SwatHtmlTag('span');
				$post_count_span->setContent(sprintf(
					Blorg::ngettext(' (%s post)', ' (%s posts)', $post_count),
					$locale->formatNumber($post_count)));

				$post_count_span->display();

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
				where instance %s %s and enabled = %s
				order by post_date desc',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$rs = SwatDB::query($this->app->db, $sql, null);
		while ($date = $rs->fetchOne()) {
			$date = new SwatDate($date);
			$year = $date->getYear();
			$month = $date->getMonth();

			if (!array_key_exists($year, $this->years)) {
				$this->years[$year] = array(
					'post_count' => 0,
					'months'     => array(),
				);
			}

			if (!array_key_exists($month, $this->years[$year]['months'])) {
				$this->years[$year]['months'][$month] = 0;
			}

			$this->years[$year]['post_count']++;
			$this->years[$year]['months'][$month]++;
		}

		if (count($this->years) == 0) {
			throw new SiteNotFoundException('Page not found');
		}
	}

	// }}}
}

?>
