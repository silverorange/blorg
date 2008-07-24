<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatPagination.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/BlorgViewFactory.php';
require_once 'Blorg/BlorgPostLoader.php';
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

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		array $arguments = array())
	{
		parent::__construct($app, $layout, $arguments);

		$this->initPosts($this->getArgument('shortname'),
			$this->getArgument('current_page'));
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'shortname' => array(0, null),
			'current_page' => array(1, 1),
		);
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
			Blorg::_('Posts Tagged: <em>%s</em>'),
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
		$path = $this->app->config->blorg->path.'tag';
		$this->layout->navbar->createEntry(Blorg::_('Tags'), $path);

		$this->layout->navbar->createEntry(
			sprintf(Blorg::_('Posts Tagged: %s'), $this->tag->title),
			$path.'/'.$this->tag->shortname);
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
		/* These strings include a non-breaking space */
		$this->pager->previous_label = Blorg::_('« Newer');
		$this->pager->next_label = Blorg::_('Older »');
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

		$loader = new BlorgPostLoader($this->app->db,
			$this->app->getInstance());

		$loader->addSelectField('title');
		$loader->addSelectField('bodytext');
		$loader->addSelectField('shortname');
		$loader->addSelectField('publish_date');
		$loader->addSelectField('author');
		$loader->addSelectField('comment_status');
		$loader->addSelectField('visible_comment_count');

		$loader->setLoadFiles(true);
		$loader->setLoadTags(true);

		$loader->setWhereClause(sprintf('enabled = %s and
			id in (select post from BlorgPostTagBinding where tag = %s)',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($tag->id, 'integer')));

		$loader->setOrderByClause('publish_date desc');

		$offset = ($current_page - 1) * self::MAX_POSTS;
		$loader->setRange(self::MAX_POSTS, $offset);

		$this->posts = $loader->getPosts();
	}

	// }}}
}

?>
