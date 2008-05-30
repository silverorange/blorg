<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/dataobjects/BlorgAuthor.php';

/**
 * Author details page for Blörg
 *
 * Loads and displays an author.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAuthorPage extends SitePage
{
	// {{{ class constants

	const MAX_POSTS = 10;

	// }}}
	// {{{ protected properties

	/**
	 * @var BlorgAuthor
	 */
	protected $author;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new post page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 * @param string $shortname
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout,
		$shortname)
	{
		parent::__construct($app, $layout);
		$this->initAuthor($shortname);
	}

	// }}}
	// {{{ protected function initAuthor()

	protected function initAuthor($shortname)
	{
		$class_name = SwatDBClassMap::get('BlorgAuthor');
		$this->author = new $class_name();
		$this->author->setDatabase($this->app->db);
		if (!$this->author->loadByShortname($shortname,
			$this->app->getInstance())) {
			throw new SiteNotFoundException('Author not found.');
		}

		if (!$this->author->show) {
			throw new SiteNotFoundException('Author not found.');
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildNavBar();
		$this->buildTitle();

		$this->layout->startCapture('content');
		$this->displayAuthor();
		$this->displayPosts();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		$this->layout->data->html_title = $this->author->name;
		$this->layout->data->meta_description = SwatString::minimizeEntities(
			SwatString::ellipsizeRight(
				SwatString::condense($this->author->bodytext), 300));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$path = $this->app->config->blorg->path.'author';
		$this->layout->navbar->createEntry(Blorg::_('Authors'), $path);
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor()
	{
		$view = BlorgViewFactory::build('author', $this->app);
		$view->setPartMode('name', BlorgView::MODE_ALL, false);
		$view->setPartMode('bodytext', BlorgView::MODE_ALL);
		$view->setPartMode('description', BlorgView::MODE_NONE);
		$view->display($this->author);
	}

	// }}}
	// {{{ protected function displayPosts()

	protected function displayPosts()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'posts';
		$div_tag->class = 'author-posts';
		$div_tag->open();

		$view = BlorgViewFactory::build('post', $this->app);
		$view->setPartMode('bodytext', BlorgView::MODE_SUMMARY);
		$view->setPartMode('extended_bodytext', BlorgView::MODE_NONE);

		$posts = $this->author->getVisiblePosts(self::MAX_POSTS);
		foreach ($posts as $post) {
			$view->display($post);
		}

		$div_tag->close();
	}

	// }}}
}

?>
