<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatPagination.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPostLoader.php';
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

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		array $arguments = array())
	{
		parent::__construct($app, $layout, $arguments);
		$this->initAuthor($this->getArgument('shortname'));
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'shortname' => array(0, null),
			'page'      => array(1, 1),
		);
	}

	// }}}
	// {{{ protected function initAuthor()

	protected function initAuthor($shortname)
	{
		$this->author = false;

		if (isset($this->app->memcache)) {
			$key = 'author_'.$shortname;
			$this->author = $this->app->memcache->getNs('authors', $key);
		}

		if ($this->author === false) {
			$class_name = SwatDBClassMap::get('BlorgAuthor');
			$this->author = new $class_name();
			$this->author->setDatabase($this->app->db);
			if (!$this->author->loadByShortname($shortname,
				$this->app->getInstance())) {
				throw new SiteNotFoundException('Author not found.');
			}

			if (!$this->author->visible) {
				throw new SiteNotFoundException('Author not found.');
			}

			if (isset($this->app->memcache)) {
				$this->app->memcache->setNs('authors', $key, $this->author);
			}
		} else {
			$this->author->setDatabase($this->app->db);
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
		Blorg::displayAd($this->app, 'top');
		$this->displayAuthor();

		if ($this->app->config->blorg->show_author_posts) {
			$this->displayPosts();
			$this->displayFooter();
		}

		Blorg::displayAd($this->app, 'bottom');
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
		$this->layout->navbar->createEntry($this->author->name);
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor()
	{
		$view = BlorgViewFactory::get($this->app, 'author');
		$view->setPartMode('name', BlorgView::MODE_ALL, false);
		$view->setPartMode('bodytext', BlorgView::MODE_ALL);
		$view->setPartMode('description', BlorgView::MODE_NONE);
		$view->setPartMode('post_count', BlorgView::MODE_NONE);
		$view->setPartMode('email', BlorgView::MODE_NONE);
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

		$view = BlorgViewFactory::get($this->app, 'post');
		$view->setPartMode('bodytext', BlorgView::MODE_SUMMARY);
		$view->setPartMode('extended_bodytext', BlorgView::MODE_NONE);

		$posts = $this->getAuthorPosts();
		$first = true;
		foreach ($posts as $post) {
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

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayFooter()

	protected function displayFooter()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'footer';
		$div_tag->open();

		$path = $this->app->config->blorg->path.'author/'.
			$this->author->shortname;

		$post_count = $this->loader->getPostCount();

		$this->pager = new SwatPagination();
		$this->pager->display_parts ^= SwatPagination::POSITION;
		$this->pager->total_records = $post_count;
		$this->pager->page_size = self::MAX_POSTS;
		$this->pager->setCurrentPage($this->getArgument('page'));
		/* These strings include a non-breaking space */
		$this->pager->previous_label = Blorg::_('« Newer');
		$this->pager->next_label = Blorg::_('Older »');
		$this->pager->link = $path.'/page%s';

		$this->pager->display();

		$div_tag->class = 'results-message';
		$div_tag->open();

		echo $this->pager->getResultsMessage(
			Blorg::_('post'), Blorg::_('posts'));

		$div_tag->close();
		$div_tag->close();
	}

	// }}}
	// {{{ protected function getAuthorPosts()

	protected function getAuthorPosts()
	{
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

		$this->loader->setLoadFiles(true);
		$this->loader->setLoadTags(true);

		$this->loader->setWhereClause(sprintf('enabled = %s and author = %s',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($this->author->id, 'integer')));

		$this->loader->setOrderByClause('publish_date desc');

		$offset = ($this->getArgument('page') - 1) * self::MAX_POSTS;
		$this->loader->setRange(self::MAX_POSTS, $offset);

		return $this->loader->getPosts();
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();

		if ($this->author->openid_server != '') {
			$this->layout->addHtmlHeadEntry(new SwatLinkHtmlHeadEntry(
				$this->author->openid_server, 'openid.server'));
		}

		if ($this->author->openid_delegate != '') {
			$this->layout->addHtmlHeadEntry(new SwatLinkHtmlHeadEntry(
				$this->author->openid_delegate, 'openid.delegate'));
		}

		if ($this->pager !== null) {
			$this->layout->addHtmlHeadEntrySet(
				$this->pager->getHtmlHeadEntrySet());
		}
	}

	// }}}
}

?>
