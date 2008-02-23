<?php

set_include_path(get_include_path().':/so/packages/xml-atom/work-gauthierm');

require_once 'Site/pages/SitePage.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';

/**
 * Displays an Atom feed of all recent posts in reverse chronological order
 *
 * The constant MAX_POSTS determines how many posts are displayed in the feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAtomFeedPage extends SitePage
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
	 * @var XML_Atom_Feed
	 */
	protected $feed;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null)
	{
		$layout = new SiteLayout($app, 'Blorg/layouts/xhtml/atom.php');

		parent::__construct($app, $layout);

		$this->initPosts();
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts()
	{
		$instance_id = $this->app->instance->getId();

		$sql = sprintf('select * from BlorgPost
			where instance %s %s
				and enabled = true
			order by post_date desc limit %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(self::MAX_POSTS, 'integer'));

		$wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$this->posts = SwatDB::query($this->app->db, $sql, $wrapper);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		header('Content-type: application/atom+xml; charset="utf-8"');
		$this->buildAtomFeed();

		ob_start();
		$this->displayAtomFeed();
		$this->layout->data->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function displayAtomFeed()

	protected function displayAtomFeed()
	{
		echo $this->feed;
	}

	// }}}
	// {{{ protected function buildAtomFeed()

	protected function buildAtomFeed()
	{
		$base_href = $this->app->getBaseHref();

		$this->feed = new XML_Atom_Feed($base_href,
			$this->app->config->site->title);

		foreach ($this->posts as $post) {
			$path = $base_href.$this->app->config->blorg->path.'archive';

			$date = clone $post->post_date;
			$date->convertTZ($this->app->default_time_zone);
			$year = $date->getYear();
			$month_name = BlorgPageFactory::$month_names[$date->getMonth()];

			$post_uri = sprintf('%s/%s/%s/%s',
				$path,
				$year,
				$month_name,
				$post->shortname);

			$post_date = $post->post_date->format(DATE_FORMAT_ISO_EXTENDED);

			$entry = new XML_Atom_Entry($post_uri, $post->title, $post_date);
			$entry->setContent($post->bodytext, 'html');
			$this->feed->addEntry($entry);
		}
	}

	// }}}
}

?>
