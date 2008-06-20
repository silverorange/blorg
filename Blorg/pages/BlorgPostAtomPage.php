<?php

require_once 'Site/pages/SitePage.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';

/**
 * Displays an Atom feed of all comments for a particular post in reverse
 * chronological order
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostAtomPage extends SitePage
{
	// {{{ class constants

	const MAX_COMMENTS = 50;

	// }}}
	// {{{ protected properties

	/**
	 * @var BlorgPost
	 */
	protected $post;

	/**
	 * @var XML_Atom_Feed
	 */
	protected $feed;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new Atom post page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 * @param integer $year
	 * @param string $month_name
	 * @param string $shortname
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout,
		$year, $month_name, $shortname)
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');

		parent::__construct($app, $layout);

		$this->initPost($year, $month_name, $shortname);
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
		$date->setYear($year);
		$date->setMonth(BlorgPageFactory::$months_by_name[$month_name]);
		$date->setDay(1);
		$date->setHour(0);
		$date->setMinute(0);
		$date->setSecond(0);

		$class_name = SwatDBClassMap::get('BlorgPost');
		$this->post = new $class_name();
		$this->post->setDatabase($this->app->db);
		if (!$this->post->loadByDateAndShortname($date, $shortname,
			$this->app->getInstance())) {
			throw new SiteNotFoundException('Post not found.');
		}

		if (!$this->post->enabled) {
			throw new SiteNotFoundException('Post not found.');
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildAtomFeed();

		$this->layout->startCapture('content');
		$this->displayAtomFeed();
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildAtomFeed()

	protected function buildAtomFeed()
	{
		$site_base_href  = $this->app->getBaseHref();
		$blorg_base_href = $site_base_href.$this->app->config->blorg->path;
		$path            = $blorg_base_href.'archive';

		$date = clone $this->post->publish_date;
		$date->convertTZ($this->app->default_time_zone);
		$year = $date->getYear();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];

		$post_uri = sprintf('%s/%s/%s/%s',
			$path,
			$year,
			$month_name,
			$this->post->shortname);

		$this->feed = new XML_Atom_Feed($post_uri.'#comments',
			sprintf(Blorg::_('Comments on “%s”'),
				$this->post->getTitle()));

		$this->feed->addLink($site_base_href.$this->source, 'self',
			'application/atom+xml');

		$this->feed->setGenerator('Blörg');
		$this->feed->setBase($site_base_href);

		if ($this->post->author->visible) {
			$author_uri = $blorg_base_href.'author/'.
				$this->post->author->shortname;
		} else {
			$author_uri = '';
		}

		$this->feed->addAuthor($this->post->author->name, $author_uri,
			$this->post->author->email);

		$comments = array();
		$visible_comments = $this->post->getVisibleComments(self::MAX_COMMENTS);
		foreach ($visible_comments as $comment) {
			$comments[] = $comment;
		}

		$comments = array_reverse($comments);

		foreach ($comments as $comment) {
			$comment_uri = $post_uri.'#comment'.$comment->id;

			if ($comment->author !== null) {
				$author_name = $comment->author->name;
				if ($comment->author->visible) {
					$author_uri = $blorg_base_href.'author/'.
						$this->post->author->shortname;

					$author_email = $this->post->author->email;
				} else {
					$author_uri   = '';
					$author_email = '';
				}
			} else {
				$author_name  = $comment->fullname;
				$author_uri   = $comment->link;
				// don't show anonymous author email
				$author_email = '';
			}

			$entry = new XML_Atom_Entry($comment_uri,
				sprintf(Blorg::_('By: %s'), $author_name),
				$comment->createdate);

			$entry->setContent(BlorgComment::getBodytextXhtml(
				$comment->bodytext), 'html');

			$entry->addAuthor($author_name, $author_uri, $author_email);
			$entry->addLink($comment_uri, 'alternate', 'text/html');

			$this->feed->addEntry($entry);
		}
	}

	// }}}
	// {{{ protected function displayAtomFeed()

	protected function displayAtomFeed()
	{
		echo $this->feed;
	}

	// }}}
}

?>
