<?php

require_once 'Site/pages/SitePage.php';
require_once 'Blorg/dataobjects/BlorgTag.php';
require_once 'Blorg/BlorgPostLoader.php';
require_once 'Blorg/pages/BlorgAtomPage.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';
require_once 'XML/Atom/Link.php';

/**
 * Displays an Atom feed of all recent posts in reverse chronological order for
 * a specified tag
 *
 * The number of posts is always at least
 * {@link BlorgAtomPage::$min_entries}, but if a recently published set of
 * posts (within the time of {@link BlorgAtomPage::$recent_period}) exceeds
 * <code>$min_entries</code>, up to, {@link BlorgAtomPage::$max_entries}
 * posts will be displayed. This makes it easier to ensure that a subscriber
 * won't miss any posts, while limiting server load for the feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagAtomPage extends BlorgAtomPage
{
	// {{{ protected properties

	/**
	 * @var BlorgTag
	 */
	protected $tag;

	// }}}

	// init phase
	// {{{ protected function initPostLoader()

	protected function initPostLoader()
	{
		$class_name = SwatDBClassMap::get('BlorgTag');
		$tag = new $class_name();
		$tag->setDatabase($this->app->db);
		if (!$tag->loadByShortname($this->getArgument('shortname'),
				$this->app->getInstance())) {
					throw new SiteNotFoundException('Page not found.');
		}

		$this->tag = $tag;

		parent::initPostLoader();

		$this->post_loader->setWhereClause(sprintf('enabled = %s and
			id in (select post from BlorgPostTagBinding where tag = %s)',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($tag->id, 'integer')));
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

	// build phase
	// {{{ protected function buildEntries()

	protected function buildEntries(XML_Atom_Feed $feed)
	{
		$tag_href = $this->getBlorgBaseHref().'tag/'.$this->tag->shortname;

		$feed->setSubTitle(sprintf(Blorg::_('Posts Tagged: %s'),
			$this->tag->title));

		$feed->addLink($this->app->getBaseHref().$this->source, 'self',
			'application/atom+xml');

		$feed->addLink($tag_href, 'alternate', 'text/html');
		$feed->setGenerator('Blörg');
		$feed->setBase($this->app->getBaseHref());

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
}

?>
