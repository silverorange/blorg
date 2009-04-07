<?php

require_once 'Site/pages/SiteSearchResultsPage.php';
require_once 'Blorg/BlorgViewFactory.php';
require_once 'Blorg/BlorgPostSearchEngine.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Page for displaying search results
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgSearchResultsPage extends SiteSearchResultsPage
{
	// {{{ private properties

	private $memcache_posts;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteAbstractPage $page)
	{
		parent::__construct($page);
		$this->ui_xml = 'Blorg/pages/search-results.xml';
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();

		$this->layout->data->title = Blorg::_('Search Results');

		ob_start();
		Blorg::displayAd($this->app, 'top');
		echo $this->layout->data->content;
		Blorg::displayAd($this->app, 'bottom');
		$this->layout->data->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();
		$path = $this->app->config->blorg->path.'search';
		$this->layout->navbar->createEntry(Blorg::_('Search'), $path);
	}

	// }}}
	// {{{ protected function buildResults()

	protected function buildResults()
	{
		$searched = false;

		if (count($this->getSearchDataValues()) > 0) {
			$fulltext_result = $this->searchFulltext();

			$this->buildPosts($fulltext_result);

			if ($fulltext_result !== null) {
				$this->buildMisspellings($fulltext_result);
				$fulltext_result->saveHistory();
			}

			$searched = true;
		}

		return $searched;
	}

	// }}}
	// {{{ protected function getFulltextTypes()

	protected function getFulltextTypes()
	{
		return array('post');
	}

	// }}}

	// build phase - posts
	// {{{ protected function buildPosts()

	protected function buildPosts($fulltext_result)
	{
		$pager = $this->ui->getWidget('post_pager');
		$posts = $this->getPosts($fulltext_result, $pager);

		$pager->link = $this->source;

		$this->result_count['post'] = count($posts);

		if (count($posts) > 0) {
			$this->has_results[] = 'post';

			$frame = $this->ui->getWidget('post_results_frame');
			$results = $this->ui->getWidget('post_results');
			$frame->visible = true;

			ob_start();
			$this->displayPosts($posts);
			$results->content = ob_get_clean();
		}
	}

	// }}}
	// {{{ protected function getPosts()

	protected function getPosts(SiteNateGoFulltextSearchResult $result,
		SwatPagination $pager)
	{
		if (isset($this->app->memcache)) {
			$key = $this->getPostsMemcacheKey();
			$posts = $this->app->memcache->getNs('posts', $key);
			$total_records = $this->app->memcache->getNs('posts',
				$key.'.total_records');

			if ($posts !== false && $total_records !== false) {
				$posts->setDatabase($this->app->db);
				$pager->total_records = $total_records;
				return $posts;
			}
		}

		$engine = $this->instantiatePostSearchEngine();
		$engine->setFulltextResult($result);
		$posts = $engine->search($pager->page_size, $pager->current_record);
		$pager->total_records = $engine->getResultCount();

		// used to memcache the posts
		if (isset($this->app->memcache)) {
			$this->memcache_posts = $posts;
			$this->app->memcache->setNs('posts', $key.'.total_records',
				$engine->getResultCount());
		}

		return $posts;
	}

	// }}}
	// {{{ protected function instantiatePostSearchEngine()

	protected function instantiatePostSearchEngine()
	{
		$engine = new BlorgPostSearchEngine($this->app);
		$this->setSearchEngine('post', $engine);
		return $engine;
	}

	// }}}
	// {{{ protected function displayPosts()

	/**
	 * Displays search results for a collection of posts
	 *
	 * @param BlorgPostWrapper $posts the posts to display
	 *                                          search results for.
	 */
	protected function displayPosts(BlorgPostWrapper $posts)
	{
		$view = BlorgViewFactory::get($this->app, 'post');
		$view->setPartMode('bodytext', BlorgView::MODE_SUMMARY);
		$view->setPartMode('extended_bodytext', BlorgView::MODE_NONE);
		foreach ($posts as $post) {
			$view->display($post);
		}
	}

	// }}}
	// {{{ protected function getPostsMemcacheKey()

	protected function getPostsMemcacheKey()
	{
		$pager = $this->ui->getWidget('post_pager');
		$instance = $this->app->instance->getInstance();

		return sprintf('BlorgSearchResultsPage.posts.%s.%s.%s.%s',
			($instance === null ? 'null' : $instance->id),
			$this->getQueryString(),
			$pager->page_size, $pager->current_record);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());

		if ($this->memcache_posts !== null) {
			$key = $this->getPostsMemcacheKey();
			$this->app->memcache->setNs('posts', $key, $this->memcache_posts);
		}
	}

	// }}}
}

?>
