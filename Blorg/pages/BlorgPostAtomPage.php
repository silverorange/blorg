<?php

require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/dataobjects/BlorgCommentWrapper.php';
require_once 'Blorg/pages/BlorgAbstractAtomPage.php';
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
class BlorgPostAtomPage extends BlorgAbstractAtomPage
{
	// {{{ protected properties

	/**
	 * @var BlorgPost
	 */
	protected $post;

	/**
	 * @var BlorgCommentWrapper
	 */
	protected $comments;

	/**
	 * The total number of comments for this feed.
	 *
	 * @var integer
	 */
	protected $total_count;

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'year'       => array(0, null),
			'month_name' => array(1, null),
			'shortname'  => array(2, null),
			'page'       => array(3, 1),
		);
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

		$this->initComments(
			$this->getArgument('year'),
			$this->getArgument('month_name'),
			$this->getArgument('shortname'),
			$this->getArgument('page'));
	}

	// }}}
	// {{{ protected function initComments()

	protected function initComments($year, $month_name, $shortname, $page)
	{
		if (!array_key_exists($month_name, BlorgPageFactory::$months_by_name)) {
			throw new SiteNotFoundException(Blorg::_('Page not found.'));
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
			throw new SiteNotFoundException(Blorg::_('Page not found.'));
		}

		if (!$this->post->enabled) {
			throw new SiteNotFoundException(Blorg::_('Page not found.'));
		}

		$offset = ($page - 1) * $this->getPageSize();
		$this->comments = $this->post->getVisibleComments(
			$this->getPageSize(), $offset);

		if (count($this->comments) === 0) {
			throw new SiteNotFoundException(Blorg::_('Page not found.'));
		}

		$this->total_count = $this->post->getVisibleCommentCount();
	}

	// }}}

	// build phase
	// {{{ protected function buildFeed()

	protected function buildFeed()
	{
		$feed = new XML_Atom_Feed($this->getBlorgBaseHref().'#comments',
			$this->app->config->site->title);

		$this->buildContent($feed);
		$this->feed = $feed;
	}

	// }}}
	// {{{ protected function buildContent()

	protected function buildContent(XML_Atom_Feed $feed)
	{
		parent::buildContent($feed);
		$this->buildAuthor($feed);
	}

	// }}}
	// {{{ protected function buildAuthor()

	protected function buildAuthor(XML_Atom_Feed $feed)
	{
		if ($this->post->author->visible) {
			$author_uri = $this->getBlorgBaseHref().'author/'.
				$this->post->author->shortname;
		} else {
			$author_uri = '';
		}

		$feed->addAuthor($this->post->author->name, $author_uri,
			$this->post->author->email);
	}

	// }}}
	// {{{ protected function buildHeader()

	protected function buildHeader(XML_Atom_Feed $feed)
	{
		parent::buildHeader($feed);

		$feed->addLink($this->getPostUri($this->post), 'alternate',
			'text/html');

		$feed->setSubTitle(sprintf(Blorg::_('Comments on “%s”'),
			$this->post->getTitle()));
	}

	// }}}
	// {{{ protected function buildEntries()

	protected function buildEntries(XML_Atom_Feed $feed)
	{
		// reverse chronoligical ordering
		$comments = array();
		foreach ($this->comments as $comment) {
			$comments[] = $comment;
		}

		$comments = array_reverse($comments);

		foreach ($comments as $comment) {
			$this->buildComment($feed, $comment);
		}
	}

	// }}}
	// {{{ protected function buildComment()

	protected function buildComment(XML_Atom_Feed $feed, BlorgComment $comment)
	{
		$comment_uri = $this->getPostUri($this->post).'#comment'.$comment->id;

		if ($comment->author !== null) {
			$author_name = $comment->author->name;
			if ($comment->author->visible) {
				$author_uri = $this->getBlorgBaseHref().'author/'.
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

		$feed->addEntry($entry);
	}

	// }}}

	// helper methods
	// {{{ protected function getTotalCount()

	protected function getTotalCount()
	{
		return $this->total_count;
	}

	// }}}
	// {{{ protected function getFeedBaseHref()

	protected function getFeedBaseHref()
	{
		return $this->getPostUri($this->post).'/feed';
	}

	// }}}
	// {{{ protected function getPageSize()

	protected function getPageSize()
	{
		return 50;
	}

	// }}}
	// {{{ protected function getPostUri()

	protected function getPostUri(BlorgPost $post)
	{
		$path = $this->getBlorgBaseHref().'archive';

		$date = clone $post->publish_date;
		$date->convertTZ($this->app->default_time_zone);
		$year = $date->getYear();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];

		return sprintf('%s/%s/%s/%s',
			$path,
			$year,
			$month_name,
			$post->shortname);
	}

	// }}}
}

?>
