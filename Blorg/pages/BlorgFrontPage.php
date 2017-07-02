<?php

/**
 * Displays all recent posts in reverse chronological order
 *
 * The constant MAX_POSTS determines how many posts are displayed on the page.
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFrontPage extends SitePage
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
	 * @var SwatPagination
	 */
	protected $pager;

	/**
	 * @var BlorgPostLoader
	 */
	protected $loader;

	// }}}
	// {{{ public function __construct()

	public function __construct(
		SiteApplication $app,
		SiteLayout $layout = null,
		array $arguments = array()
	) {
		parent::__construct($app, $layout, $arguments);
		$this->initPosts($this->getArgument('page'));
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'page' => array(0, 1),
		);
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts($page)
	{
		$memcache = (isset($this->app->memcache)) ? $this->app->memcache : null;
		$this->loader = new BlorgPostLoader($this->app->db,
			$this->app->getInstance(), $memcache);

		$this->loader->addSelectField('title');
		$this->loader->addSelectField('bodytext');
		$this->loader->addSelectField('extended_bodytext');
		$this->loader->addSelectField('shortname');
		$this->loader->addSelectField('publish_date');
		$this->loader->addSelectField('author');
		$this->loader->addSelectField('comment_status');
		$this->loader->addSelectField('visible_comment_count');

		$this->loader->setLoadFiles(true);
		$this->loader->setLoadTags(true);

		$this->loader->setWhereClause(sprintf('enabled = %s',
			$this->app->db->quote(true, 'boolean')));

		$this->loader->setOrderByClause('publish_date desc');

		$offset = ($page - 1) * self::MAX_POSTS;
		$this->loader->setRange(self::MAX_POSTS, $offset);

		$this->posts = $this->loader->getPosts();

		if (count($this->posts) == 0) {
			throw new SiteNotFoundException('Page not found.');
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->layout->startCapture('content');
		Blorg::displayAd($this->app, 'top');
		$this->displayPosts();
		Blorg::displayAd($this->app, 'bottom');
		$this->displayFooter();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function displayPosts()

	protected function displayPosts()
	{
		$view = SiteViewFactory::get($this->app, 'post');
		$view->setPartMode('extended_bodytext', SiteView::MODE_SUMMARY);
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
	// {{{ protected function displayFooter()

	protected function displayFooter()
	{
		echo '<div class="footer">';

		$path = $this->app->config->blorg->path;

		$post_count = $this->loader->getPostCount();

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
			Blorg::_('post'), Blorg::_('posts'));

		echo '</div>';

		echo '<div class="archive-link">';
		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $path.'archive';
		$anchor_tag->setContent(Blorg::_('Archive'));
		$anchor_tag->display();
		echo '</div>';

		echo '</div>';
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		if ($this->pager !== null) {
			$this->layout->addHtmlHeadEntrySet(
				$this->pager->getHtmlHeadEntrySet());
		}
	}

	// }}}
}

?>
