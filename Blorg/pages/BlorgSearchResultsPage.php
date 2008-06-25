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
	// init phase
	// {{{ public function init()

	public function init()
	{
		$this->ui_xml = 'Blorg/pages/search-results.xml';
		parent::init();
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
	// {{{ protected function buildResults()

	protected function buildResults()
	{
		$searched = false;

		if (count($this->getSearchDataValues()) > 0) {
			$fulltext_result = $this->searchFulltext();

			$this->buildArticles($fulltext_result);
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
		$types = parent::getFulltextTypes();
		$types[] = 'post';

		return $types;
	}

	// }}}

	// build phase - posts
	// {{{ protected function buildPosts()

	protected function buildPosts($fulltext_result)
	{
		$pager = $this->ui->getWidget('post_pager');
		$engine = $this->instantiatePostSearchEngine();
		$engine->setFulltextResult($fulltext_result);
		$posts = $engine->search($pager->page_size, $pager->current_record);

		$pager->total_records = $engine->getResultCount();
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
