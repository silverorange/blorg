<?php

require_once 'Site/pages/SiteSearchResultsPage.php';
require_once 'Site/SiteViewFactory.php';
require_once 'Blorg/BlorgPostSearchEngine.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Page for displaying search results
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgSearchResultsPage extends SiteSearchResultsPage
{
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

		ob_start();
		Blorg::displayAd($this->app, 'top');
		echo $this->layout->data->content;
		Blorg::displayAd($this->app, 'bottom');
		$this->layout->data->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		$this->layout->data->title = Blorg::_('Search Results');
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
		$pager = $this->ui->getWidget('post_pager');

		// cached content
		$key = sprintf('BlorgSearchResultsPage.getPosts.%s.%s.%s',
			$this->getQueryString(),
			$pager->page_size, $pager->current_record);

		$posts = $this->getCacheValue($key, 'posts');
		$total_records = $this->getCacheValue($key.'.total_records',
			'posts');

		if ($posts !== false && $total_records !== false) {
			$posts->setDatabase($this->app->db);
			$pager->total_records = $total_records;
			return $posts;
		}

		// get posts
		$engine = $this->instantiatePostSearchEngine();
		$engine->setFulltextResult($result);
		$posts = $engine->search($pager->page_size, $pager->current_record);
		$pager->total_records = $engine->getResultCount();

		$this->addCacheValue($posts, $key, 'posts');
		$this->addCacheValue($engine->getResultCount(),
			$key.'.total_records', 'posts');

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
		$view = SiteViewFactory::get($this->app, 'post');
		$view->setPartMode('bodytext', SiteView::MODE_SUMMARY);
		$view->setPartMode('extended_bodytext', SiteView::MODE_NONE);
		foreach ($posts as $post) {
			$view->display($post);
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
