<?php

require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPostLoader.php';
require_once 'Blorg/pages/BlorgAbstractAtomPage.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';
require_once 'XML/Atom/Link.php';

/**
 * Displays an Atom feed of all recent posts in reverse chronological order
 *
 * The number of posts is always at least {@link BlorgAtomPage::$min_entries},
 * but if a recently published set of posts (within the time of
 * {@link BlorgAtomPage::$recent_period}) exceeds <code>$min_entries</code>,
 * up to, {@link BlorgAtomPage::$max_entries} posts will be displayed. This
 * makes it easier to ensure that a subscriber won't miss any posts, while
 * limiting server load for the feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAtomPage extends BlorgAbstractAtomPage
{
	// {{{ protected properties

	/**
	 * @var BlorgPostWrapper
	 */
	protected $posts;

	/**
	 * @var BlorgPostLoader
	 */
	protected $post_loader;

	/**
	 * @var integer
	 */
	protected $front_page_count;

	// }}}

	// init phase
	// {{{ protected function initEntries()

	protected function initEntries()
	{
		$this->initPostLoader();
		$this->initFrontPagePosts();
	}

	// }}}
	// {{{ protected function initPostLoader()

	protected function initPostLoader()
	{
		$this->post_loader = new BlorgPostLoader($this->app->db,
			$this->app->getInstance());

		$this->post_loader->addSelectField('title');
		$this->post_loader->addSelectField('bodytext');
		$this->post_loader->addSelectField('extended_bodytext');
		$this->post_loader->addSelectField('shortname');
		$this->post_loader->addSelectField('publish_date');
		$this->post_loader->addSelectField('author');
		$this->post_loader->addSelectField('comment_status');
		$this->post_loader->addSelectField('visible_comment_count');

		$this->post_loader->setLoadFiles(true);
		$this->post_loader->setLoadTags(true);

		$this->post_loader->setWhereClause(sprintf('enabled = %s',
			$this->app->db->quote(true, 'boolean')));

		$this->post_loader->setOrderByClause('publish_date desc');
		$this->post_loader->setRange(new SwatDBRange($this->max_entries));
	}

	// }}}
	// {{{ protected function initFrontPagePosts()

	protected function initFrontPagePosts()
	{
		$posts = $this->post_loader->getPosts();
		$count = 0;

		foreach ($posts as $post) {
			if ($count > $this->max_entries || ($count > $this->min_entries)
				&& $this->isEntryRecent($post->publish_date))
				break;

			$count++;
		}

		$this->front_page_count = $count;
		$this->posts = $posts;
	}

	// }}}

	// build phase
	// {{{ protected function buildHeader()

	protected function buildHeader(XML_Atom_Feed $feed)
	{
		parent::buildHeader($feed);
		$feed->setSubTitle(Blorg::_('Recent Posts'));
		$feed->addLink($this->getBlorgBaseHref(), 'alternate', 'text/html');
	}

	// }}}
	// {{{ protected function buildEntries()

	protected function buildEntries(XML_Atom_Feed $feed)
	{
		$limit = $this->getFrontPageCount();
		if ($this->page > 1) {
			$offset = $this->getFrontPageCount()
				+ ($this->page - 2) * $this->min_entries;

			$this->post_loader->setRange($this->min_entries, $offset);
			$this->posts = $this->post_loader->getPosts();
			$limit = $this->min_entries;
		}


		$count = 0;
		foreach ($this->posts as $post) {
			if ($count < $limit)
				$this->buildPost($feed, $post);

			$count++;
		}
	}

	// }}}
	// {{{ protected function buildPost()

	protected function buildPost(XML_Atom_Feed $feed, BlorgPost $post)
	{
		$site_base_href  = $this->app->getBaseHref();
		$blorg_base_href = $site_base_href.$this->app->config->blorg->path;
		$path = $blorg_base_href.'archive';

		$date = clone $post->publish_date;
		$date->convertTZ($this->app->default_time_zone);
		$year = $date->getYear();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];

		$post_uri = sprintf('%s/%s/%s/%s',
			$path,
			$year,
			$month_name,
			$post->shortname);

		$entry = new XML_Atom_Entry($post_uri, $post->getTitle(),
			$post->publish_date);

		if ($post->extended_bodytext != '') {
			$full_bodytext = $post->bodytext.$post->extended_bodytext;
			$entry->setSummary($post->bodytext, 'html');
			$entry->setContent($full_bodytext, 'html');
		} else {
			$entry->setContent($post->bodytext, 'html');
		}

		foreach ($post->getTags() as $tag) {
			$entry->addCategory($tag->shortname, $blorg_base_href,
				$tag->title);
		}

		$entry->addLink($post_uri, 'alternate', 'text/html');

		foreach ($post->getVisibleFiles() as $file) {
			$link = new XML_Atom_Link(
				$site_base_href.$file->getRelativeUri(
					$this->app->config->blorg->path),
				'enclosure',
				$file->mime_type);

			$link->setTitle($file->getDescription());
			$link->setLength($file->filesize);
			$entry->addLink($link);
		}

		if ($post->author->visible) {
			$author_uri = $blorg_base_href.'author/'.
				$post->author->shortname;
		} else {
			$author_uri = '';
		}

		$entry->addAuthor($post->author->name, $author_uri,
			$post->author->email);

		$visible_comment_count = $post->getVisibleCommentCount();
		if ($post->comment_status == BlorgPost::COMMENT_STATUS_OPEN ||
			$post->comment_status == BlorgPost::COMMENT_STATUS_MODERATED ||
			($post->comment_status == BlorgPost::COMMENT_STATUS_LOCKED &&
			$visible_comment_count > 0)) {
			$entry->addLink($post_uri.'#comments', 'comments', 'text/html');
		}

		$feed->addEntry($entry);
	}

	// }}}

	// helper methods
	// {{{ protected function getTotalCount()

	protected function getTotalCount()
	{
		return $this->post_loader->getPostCount();
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
		return $this->app->getBaseHref().$this->app->config->blorg->path;
	}

	// }}}
	// {{{ protected function getFeedBaseHref()

	protected function getFeedBaseHref()
	{
		return $this->getBlorgBaseHref().'feed';
	}

	// }}}
}

?>
