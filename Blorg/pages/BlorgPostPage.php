<?php

/**
 * Post page for Blörg
 *
 * Loads and displays a post and handles adding comments to a post.
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostPage extends SitePageDecorator
{
	// {{{ protected properties

	/**
	 * @var BlorgPost
	 */
	protected $post;

	/**
	 * @var SiteCommentUi
	 */
	protected $comment_ui;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteAbstractPage $page)
	{
		parent::__construct($page);

		$year = $this->getArgument('year');
		$month_name = $this->getArgument('month_name');
		$shortname = $this->getArgument('shortname');

		$this->initPost($year, $month_name, $shortname);
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'year' => array(0, null),
			'month_name' => array(1, null),
			'shortname' => array(2, null),
		);
	}

	// }}}
	// {{{ protected function initPost()

	protected function initPost($year, $month_name, $shortname)
	{
		if (!array_key_exists($month_name, BlorgPageFactory::$months_by_name)) {
			throw new SiteNotFoundException('Post not found.');
		}

		// Date parsed from URL is in locale time.
		$date = new SwatDate();
		$date->setTZ($this->app->default_time_zone);
		$date->setDate($year, BlorgPageFactory::$months_by_name[$month_name], 1);
		$date->setTime(0, 0, 0);

		$memcache = (isset($this->app->memcache)) ? $this->app->memcache : null;
		$loader = new BlorgPostLoader($this->app->db,
			$this->app->getInstance(), $memcache);

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

		$this->post = $loader->getPostByDateAndShortname($date, $shortname);

		if ($this->post === null) {
			throw new SiteNotFoundException('Post not found.');
		}
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->comment_ui = new BlorgCommentUi($this->app, $this->post,
			$this->source);

		$this->comment_ui->init();
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();

		$this->comment_ui->process();
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		parent::build();
		$this->buildAtomLinks();
	}

	// }}}
	// {{{ protected function buildContent()

	protected function buildContent()
	{
		$this->layout->startCapture('content');
		Blorg::displayAd($this->app, 'top');
		$this->displayPost();
		$this->displayComments();
		$this->comment_ui->display();
		Blorg::displayAd($this->app, 'bottom');
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildMetaDescription()

	protected function buildMetaDescription()
	{
		$this->layout->data->meta_description = SwatString::minimizeEntities(
			SwatString::ellipsizeRight(
				SwatString::condense($this->post->bodytext), 300));
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		$this->layout->data->html_title = $this->post->getTitle();
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		if (!property_exists($this->layout, 'navbar'))
			return;

		$path = $this->app->config->blorg->path.'archive';
		$this->layout->navbar->createEntry(Blorg::_('Archive'), $path);

		$date = clone $this->post->publish_date;
		$date->convertTZ($this->app->default_time_zone);

		$path.= '/'.$date->getYear();
		$this->layout->navbar->createEntry($date->getYear(), $path);

		$month_title = $date->getMonthName();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];
		$path.= '/'.$month_name;
		$this->layout->navbar->createEntry($month_title, $path);

		$path.= '/'.$this->post->shortname;
		$this->layout->navbar->createEntry($this->post->title, $path);
	}

	// }}}
	// {{{ protected function buildAtomLinks()

	protected function buildAtomLinks()
	{
		if ($this->post->hasVisibleCommentStatus()) {
			$this->layout->addHtmlHeadEntry(new SwatLinkHtmlHeadEntry(
				$this->source.'/feed', 'alternate', 'application/atom+xml',
				sprintf(Blorg::_('Recent Comments on “%s”'),
					$this->post->title)));
		}
	}

	// }}}
	// {{{ protected function displayPost()

	protected function displayPost()
	{
		$view = SiteViewFactory::get($this->app, 'post');
		$view->setPartMode('title', SiteView::MODE_ALL, false);
		$view->display($this->post);
	}

	// }}}
	// {{{ protected function displayComments()

	protected function displayComments()
	{
		if ($this->post->comment_status != SiteCommentStatus::CLOSED) {
			Blorg::displayAd($this->app, 'post_comments');

			$div_tag = new SwatHtmlTag('div');
			$div_tag->id = 'comments';
			$div_tag->class = 'entry-comments';
			$div_tag->open();

			$comments = $this->post->getVisibleComments();

			$view = SiteViewFactory::get($this->app, 'post-comment');
			$count = count($comments);

			if ($count > 0) {
				echo '<h3 class="comments-title">',
					Blorg::_('Comments'), '</h3>';
			}

			// display message for locked comments
			if ($this->post->comment_status == SiteCommentStatus::LOCKED) {
				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'comments-locked-message';
				$div_tag->setContent(Blorg::_(
					'Comments are locked. No additional comments may be '.
					'posted.'));

				$div_tag->display();
			}

			foreach ($comments as $i => $comment) {
				if ($i == $count - 1) {
					$div_tag = new SwatHtmlTag('div');
					$div_tag->id = 'last_comment';
					$div_tag->open();
					$view->display($comment);
					$div_tag->close();
				} else {
					$view->display($comment);
				}
			}

			$div_tag->close();
		}
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->comment_ui->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
