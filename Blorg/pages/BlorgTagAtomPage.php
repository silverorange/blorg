<?php

require_once 'Blorg/dataobjects/BlorgTag.php';
require_once 'Blorg/pages/BlorgAtomPage.php';
require_once 'XML/Atom/Feed.php';

/**
 * Displays an Atom feed of all recent posts in reverse chronological order for
 * a specific tag
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
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'shortname' => array(0, null),
			'page'      => array(1, 1),
		);
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		$this->initTag();
		parent::init();
	}

	// }}}
	// {{{ protected function initTag()

	protected function initTag()
	{
		$class_name = SwatDBClassMap::get('BlorgTag');
		$this->tag = new $class_name();
		$this->tag->setDatabase($this->app->db);
		if (!$this->tag->loadByShortname($this->getArgument('shortname'),
			$this->app->getInstance())) {
			throw new SiteNotFoundException('Page not found.');
		}
	}

	// }}}
	// {{{ protected function initPostLoader()

	protected function initPostLoader($page)
	{
		parent::initPostLoader($page);
		$this->loader->setWhereClause(sprintf('enabled = %s and
			id in (select post from BlorgPostTagBinding where tag = %s)',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote($this->tag->id, 'integer')));
	}

	// }}}

	// build phase
	// {{{ protected function buildHeader()

	protected function buildHeader(XML_Atom_Feed $feed)
	{
		BlorgAbstractAtomPage::buildHeader($feed);
		$tag_href = $this->getBlorgBaseHref().'tag/'.$this->tag->shortname;
		$feed->addLink($tag_href, 'alternate', 'text/html');
		$feed->setSubTitle(sprintf(Blorg::_('Posts Tagged: %s'),
			$this->tag->title));
	}

	// }}}
}

?>
