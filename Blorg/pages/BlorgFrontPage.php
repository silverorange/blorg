<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatPagination.php';
require_once 'Site/pages/SitePageDecorator.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgViewFactory.php';
require_once 'Blorg/BlorgPostLoader.php';

/**
 * Displays all recent posts in reverse chronological order
 *
 * The constant {@link BlorgFrontPage::MAX_POSTS} determines how many posts are
 * displayed on the page.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFrontPage extends SitePageDecorator
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
	 * @var integer
	 */
	protected $current_page = 1;

	/**
	 * @var SwatPagination
	 */
	protected $pager;

	// }}}
	// {{{ protected function getArgumentMap()

	/**
	 * @return array
	 *
	 * @see SitePage::getArgumentMap()
	 */
	protected function getArgumentMap()
	{
		return array(
			'page' => array(0, 1),
		);
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->initPosts($this->getArgument('page'));
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts($current_page)
	{
		$loader = new BlorgPostLoader($this->app->db,
			$this->app->getInstance());

		$loader->addSelectField('title');
		$loader->addSelectField('bodytext');
		$loader->addSelectField('extended_bodytext');
		$loader->addSelectField('shortname');
		$loader->addSelectField('publish_date');
		$loader->addSelectField('author');
		$loader->addSelectField('comment_status');
		$loader->addSelectField('visible_comment_count');

		$loader->setLoadFiles(true);
		$loader->setLoadTags(true);

		$loader->setWhereClause(sprintf('enabled = %s',
			$this->app->db->quote(true, 'boolean')));

		$loader->setOrderByClause('publish_date desc');

		$offset = ($current_page - 1) * self::MAX_POSTS;
		$loader->setRange(self::MAX_POSTS, $offset);

		$this->posts = $loader->getPosts();

		if (count($this->posts) == 0) {
			throw new SiteNotFoundException('Page not found.');
		}

		$this->current_page = $current_page;
	}

	// }}}

	// build phase
	// {{{ protected function buildContent()

	protected function buildContent()
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
		$view = BlorgViewFactory::get($this->app, 'post');
		$view->setPartMode('extended_bodytext', BlorgView::MODE_SUMMARY);
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

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select count(id) from BlorgPost
			where instance %s %s
				and enabled = %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$post_count = SwatDB::queryOne($this->app->db, $sql, 'integer');

		$this->pager = new SwatPagination();
		$this->pager->display_parts ^= SwatPagination::POSITION;
		$this->pager->total_records = $post_count;
		$this->pager->page_size = self::MAX_POSTS;
		$this->pager->setCurrentPage($this->current_page);
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
/*		$this->layout->addHtmlHeadEntrySet(
			$this->pager->getHtmlHeadEntrySet());*/
	}

	// }}}
}

?>
