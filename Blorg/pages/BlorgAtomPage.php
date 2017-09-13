<?php

/**
 * Displays an Atom feed of all recent posts in reverse chronological order
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
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
	protected $loader;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->initPostLoader($this->getArgument('page'));
		$this->initPosts();
	}

	// }}}
	// {{{ protected function initPostLoader()

	protected function initPostLoader($page)
	{
		$memcache = (isset($this->app->memcache)) ? $this->app->memcache : null;
		$this->loader = new BlorgPostLoader($this->app->db,
			$this->app->getInstance(), $memcache);

		$this->loader->addSelectField('title');
		$this->loader->addSelectField('bodytext');
		$this->loader->addSelectField('extended_bodytext');
		$this->loader->addSelectField('shortname');
		$this->loader->addSelectField('publish_date');
		$this->loader->addSelectField('author');
		$this->loader->addSelectField('comment_status');
		$this->loader->addSelectField('visible_comment_count');

		$this->loader->setLoadFiles(true);
		$this->loader->setLoadTags(true);

		$this->loader->setWhereClause(sprintf('enabled = %s',
			$this->app->db->quote(true, 'boolean')));

		$this->loader->setOrderByClause('publish_date desc');

		$offset = ($page - 1) * $this->getPageSize();
		$this->loader->setRange($this->getPageSize(), $offset);
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts()
	{
		$this->posts = $this->loader->getPosts();
		if (count($this->posts) === 0) {
			throw new SiteNotFoundException('Page not found.');
		}
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
		foreach ($this->posts as $post) {
			$this->buildPost($feed, $post);
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
		if ($post->comment_status == SiteCommentStatus::OPEN ||
			$post->comment_status == SiteCommentStatus::MODERATED ||
			($post->comment_status == SiteCommentStatus::LOCKED &&
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
		return $this->loader->getPostCount();
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
