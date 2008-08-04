<?php

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

		parent::initPostLoader();

		$this->loader->setWhereClause(sprintf('enabled = %s and
			id in (select post from BlorgPostTagBinding where tag = %s)',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($tag->id, 'integer')));

		$this->tag = $tag;
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
	// {{{ protected function buildHeader()

	protected function buildHeader(XML_Atom_Feed $feed)
	{
		parent::buildHeader($feed);
		$tag_href = $this->getBlorgBaseHref().'tag/'.$this->tag->shortname;
		$feed->addLink($tag_href, 'alternate', 'text/html');
		$feed->setSubTitle(sprintf(Blorg::_('Posts Tagged: %s'),
			$this->tag->title));
	}

	// }}}
}

?>
