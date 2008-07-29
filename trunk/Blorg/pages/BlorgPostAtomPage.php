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
	 * @var integer
	 */
	protected $front_page_count;

	// }}}

	// init phase
	// {{{ protected function initEntries()

	protected function initEntries()
	{
		$year = $this->getArgument('year');
		$month_name = $this->getArgument('month_name');
		$shortname = $this->getArgument('shortname');
		if (!array_key_exists($month_name, BlorgPageFactory::$months_by_name)) {
			throw new SiteNotFoundException(Blorg::_('Post not found.'));
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
			throw new SiteNotFoundException(Blorg::_('Post not found.'));
		}

		if (!$this->post->enabled) {
			throw new SiteNotFoundException(Blorg::_('Post not found.'));
		}

		if ($this->page > 1) {
			// page of comments
			$this->front_page_count = $this->min_entries;
			$offset = ($this->page - 1) * $this->min_entries;
			$comments = $this->post->getVisibleComments($this->min_entries,
				$offset);
		} else {
			// first page, default page length
			$comments = $this->post->getVisibleComments($this->min_entries);
			$this->front_page_count = count($comments);
		}

		$this->comments = $comments;
	}

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
	// {{{ protected function buildHeader()

	protected function buildHeader(XML_Atom_Feed $feed)
	{
		parent::buildHeader($feed);
		$feed->setSubTitle(sprintf(Blorg::_('Comments on “%s”'),
			$this->post->getTitle()));
	}

	// }}}
	// {{{ protected function buildEntries()

	protected function buildEntries(XML_Atom_Feed $feed)
	{
		if ($this->post->author->visible) {
			$author_uri = $this->getBlorgBaseHref().'author/'.
				$this->post->author->shortname;
		} else {
			$author_uri = '';
		}

		$feed->addAuthor($this->post->author->name, $author_uri,
			$this->post->author->email);

		$comments = array();
		foreach ($this->comments as $comment) {
			$comments[] = $comment;
		}

		$comments = array_reverse($comments);

		foreach ($comments as $comment)
			$this->buildComment($feed, $comment);
	}

	// }}}
	// {{{ protected function buildComment()

	protected function buildComment(XML_Atom_Feed $feed, BlorgComment $comment)
	{
		$comment_uri = $this->getBlorgBaseHref().'#comment'.$comment->id;

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
		return $this->post->getVisibleCommentCount();
	}

	// }}}
	// {{{ protected function getFrontPageCount()

	protected function getFrontPageCount()
	{
		return $this->front_page_count;
	}

	// }}}
	// {{{ protected function getBlorgBaseHref()

	protected function getBlorgBaseHref()
	{
		$path = $this->app->getBaseHref().$this->app->config->blorg->path.'archive';

		$date = clone $this->post->publish_date;
		$date->convertTZ($this->app->default_time_zone);
		$year = $date->getYear();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];

		return sprintf('%s/%s/%s/%s',
			$path,
			$year,
			$month_name,
			$this->post->shortname);
	}

	// }}}
	// {{{ protected function getFeedBaseHref()

	protected function getFeedBaseHref()
	{
		return $this->getBlorgBaseHref().'/feed';
	}

	// }}}
}

?>
