<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatPagination.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/BlorgViewFactory.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';
require_once 'Blorg/dataobjects/BlorgTag.php';
require_once 'Blorg/Blorg.php';

/**
 * Displays recent posts with a given tag in reverse chronological order
 *
 * The constant MAX_POSTS determines how many posts are displayed on the page.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagPage extends SitePage
{
	// {{{ class constants

	const MAX_POSTS = 10;

	// }}}
	// {{{ protected properties

	/**
	 * @var BlorgPostWrapper
	 */
	protected $posts;

	/**
	 * @var BlorgTag
	 */
	protected $tag;

	/**
	 * @var integer
	 */
	protected $current_page = 1;

	/**
	 * @var SwatPagination
	 */
	protected $pager;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new month archive page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 * @param string $shortname
	 * @param integer $page
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout,
		$shortname, $page = 1)
	{
		parent::__construct($app, $layout);
		$this->initPosts($shortname, $page);
	}

	// }}}
	// {{{ public function build()

	public function build()
	{
		$this->buildNavBar();
		$this->buildAtomLinks();

		$this->layout->startCapture('content');
		Blorg::displayAd($this->app, 'top');
		$this->displayPosts();
		$this->displayFooter();
		Blorg::displayAd($this->app, 'bottom');
		$this->layout->endCapture();

		$this->layout->data->title = sprintf(
			Blorg::_('Posts Tagged: %s'),
			$this->tag->title);
	}

	// }}}
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->pager->getHtmlHeadEntrySet());
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$path = $this->app->config->blorg->path.'tag/'.$this->tag->shortname;
		$this->layout->navbar->createEntry(
			sprintf(Blorg::_('Posts Tagged: %s'), $this->tag->title),
			$path);
	}

	// }}}
	// {{{ protected function buildAtomLinks()

	protected function buildAtomLinks()
	{
		$path = $this->app->config->blorg->path.'tag/'.$this->tag->shortname;
		$this->layout->addHtmlHeadEntry(new SwatLinkHtmlHeadEntry(
			$path.'/feed', 'alternate', 'application/atom+xml',
			sprintf(Blorg::_('Posts Tagged: %s'),
				$this->tag->title)));
	}

	// }}}
	// {{{ protected function displayPosts()

	protected function displayPosts()
	{
		$view = BlorgViewFactory::get($this->app, 'post');
		$view->setPartMode('bodytext', BlorgView::MODE_SUMMARY);
		$view->setPartMode('extended_bodytext', BlorgView::MODE_NONE);
		foreach ($this->posts as $post) {
			$view->display($post);
		}
	}

	// }}}
	// {{{ protected function displayFooter()

	protected function displayFooter()
	{
		echo '<div class="footer">';

		$path = $this->app->config->blorg->path.'tag/'.$this->tag->shortname;

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select count(id) from BlorgPost
			where
				id in (select post from BlorgPostTagBinding where tag = %s) and
				instance %s %s and enabled = %s',
			$this->app->db->quote($this->tag->id, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$post_count = SwatDB::queryOne($this->app->db, $sql, 'integer');

		$this->pager = new SwatPagination();
		$this->pager->display_parts ^= SwatPagination::POSITION;
		$this->pager->total_records = $post_count;
		$this->pager->page_size = self::MAX_POSTS;
		$this->pager->setCurrentPage($this->current_page);
		$this->pager->link = $path.'/page%s';

		$this->pager->display();

		echo '<div class="results-message">';
		echo $this->pager->getResultsMessage(
			Blorg::_('post'), Blorg::_('posts'));

		echo '</div>';

		echo '</div>';
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts($shortname, $current_page)
	{
		$class_name = SwatDBClassMap::get('BlorgTag');
		$tag = new $class_name();
		$tag->setDatabase($this->app->db);
		if (!$tag->loadByShortname($shortname, $this->app->getInstance())) {
			throw new SiteNotFoundException('Page not found.');
		}

		$this->tag = $tag;

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select * from BlorgPost
			where
				id in (select post from BlorgPostTagBinding where tag = %s) and
				instance %s %s and enabled = %s
			order by publish_date desc',
			$this->app->db->quote($tag->id, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$offset = ($current_page - 1) * self::MAX_POSTS;
		$this->app->db->setLimit(self::MAX_POSTS, $offset);

		$this->current_page = $current_page;

		$wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$this->posts = SwatDB::query($this->app->db, $sql, $wrapper);
	}

	// }}}
}

?>
