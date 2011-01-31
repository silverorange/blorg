<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatPagination.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/SiteViewFactory.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/BlorgPostLoader.php';

/**
 * Displays an index of all months with posts in a given year
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgYearArchivePage extends SitePage
{
	// {{{ class constants

	const MAX_POSTS = 50;

	// }}}
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

	/**
	 * @var BlorgPostLoader
	 */
	protected $loader = null;

	/**
	 * @var SwatPagination
	 */
	protected $pager;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		array $arguments = array())
	{
		parent::__construct($app, $layout, $arguments);

		$year = $this->getArgument('year');
		$this->initMonths($year, $this->getArgument('page'));
		$this->year = intval($year);
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'year' => array(0, null),
			'page' => array(1, 1),
		);
	}

	// }}}
	// {{{ protected function initMonths()

	protected function initMonths($year, $page)
	{
		// Date parsed from URL is in locale time.
		$date = new SwatDate();
		$date->setTZ($this->app->default_time_zone);
		$date->setDate($year, 1, 1);
		$date->setTime(0, 0, 0);

		$memcache = (isset($this->app->memcache)) ? $this->app->memcache : null;
		$this->loader = new BlorgPostLoader($this->app->db,
			$this->app->getInstance(), $memcache);

		$this->loader->addSelectField('title');
		$this->loader->addSelectField('bodytext');
		$this->loader->addSelectField('shortname');
		$this->loader->addSelectField('publish_date');
		$this->loader->addSelectField('author');
		$this->loader->addSelectField('comment_status');
		$this->loader->addSelectField('visible_comment_count');

		$this->loader->setWhereClause(sprintf('enabled = %s and
			date_trunc(\'year\', convertTZ(publish_date, %s)) =
				date_trunc(\'year\', timestamp %s)',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote(
				$this->app->default_time_zone->getName(), 'text'),
			$this->app->db->quote($date->getDate(), 'date')));

		$this->loader->setOrderByClause('publish_date desc');

		$offset = ($page - 1) * self::MAX_POSTS;
		$this->loader->setRange(self::MAX_POSTS, $offset);

		$posts = $this->loader->getPosts();

		foreach ($posts as $post) {
			$publish_date = clone $post->publish_date;
			$publish_date->convertTZ($this->app->default_time_zone);
			$month = $publish_date->getMonth();
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

	// build phase
	// {{{ public function build()

	public function build()
	{
		if (isset($this->layout->navbar))
			$this->buildNavBar();

		$this->layout->startCapture('content');
		Blorg::displayAd($this->app, 'top');
		$this->displayMonths();
		Blorg::displayAd($this->app, 'bottom');
		$this->displayFooter();
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
	// {{{ protected function displayMonths()

	protected function displayMonths()
	{
		$path = $this->app->config->blorg->path.'archive';

		$view = SiteViewFactory::get($this->app, 'post');
		$view->setPartMode('title', SiteView::MODE_SUMMARY);
		$view->setPartMode('bodytext', SiteView::MODE_NONE);
		$view->setPartMode('extended_bodytext', SiteView::MODE_NONE);
		$view->setPartMode('tags', SiteView::MODE_NONE);
		$view->setPartMode('files', SiteView::MODE_NONE);

		$ul_tag = new SwatHtmlTag('ul');
		$ul_tag->class = 'blorg-archive-months';
		$ul_tag->open();
		foreach ($this->months as $month => $posts) {
			$li_tag = new SwatHtmlTag('li');
			$li_tag->open();

			$heading_tag = new SwatHtmlTag('h4');
			$heading_tag->class = 'blorg-archive-month-title';
			$heading_tag->open();

			$date = new SwatDate();
			$date->setDate(2010, $month, 1);

			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = sprintf('%s/%s/%s',
				$path,
				$this->year,
				BlorgPageFactory::$month_names[$month]);

			$anchor_tag->setContent($date->getMonthName());
			$anchor_tag->display();

			$heading_tag->close();

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
	// {{{ protected function displayFooter()

	protected function displayFooter()
	{
		$post_count = $this->loader->getPostCount();

		echo '<div class="footer">';

		$path = $this->app->config->blorg->path.'archive/'.$this->year.'/';

		$this->pager = new SwatPagination();
		$this->pager->display_parts ^= SwatPagination::POSITION;
		$this->pager->total_records = $post_count;
		$this->pager->page_size = self::MAX_POSTS;
		$this->pager->setCurrentPage($this->getArgument('page'));
		/* These strings include a non-breaking space */
		$this->pager->previous_label = Blorg::_('« Newer');
		$this->pager->next_label = Blorg::_('Older »');
		$this->pager->link = $path.'page%s';

		$this->pager->display();

		echo '<div class="results-message">';
		echo $this->pager->getResultsMessage(
			sprintf(Blorg::_('post in %s'), $this->year),
			sprintf(Blorg::_('posts in %s'), $this->year));

		echo '</div>';

		echo '</div>';
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->pager->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
