<?php

require_once 'Site/pages/SitePage.php';
require_once 'Blorg/dataobjects/BlorgTag.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';
require_once 'XML/Atom/Feed.php';
require_once 'XML/Atom/Entry.php';
require_once 'XML/Atom/Link.php';

/**
 * Displays an Atom feed of all recent posts in reverse chronological order for
 * a specified tag
 *
 * The number of posts is always at least $min_entries, but if a recently
 * published set of posts (within the time of $recent_period) exceeds
 * $min_entries, up to $max_entries posts will be displayed. This makes it
 * easier to ensure that a subscriber won't miss any posts, while
 * limiting server load for the feed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagAtomPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var BlorgPostWrapper
	 */
	protected $posts;

	/**
	 * @var XML_Atom_Feed
	 */
	protected $feed;

	/**
	 * The minimum number of entries to display
	 *
	 * @var integer
	 */
	protected $min_entries = 20;

	/**
	 * The maximum number of entries to display
	 *
	 * @var integer
	 */
	protected $max_entries = 100;

	/**
	 * Period for recently added posts (in seconds)
	 *
	 * Default value is two days.
	 *
	 * @var interger
	 */
	protected $recent_period = 172800;

	/**
	 * @var BlorgTag
	 */
	protected $tag;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		$shortname)
	{
		$layout = new SiteLayout($app, 'Site/layouts/xhtml/atom.php');
		parent::__construct($app, $layout);
		$this->initPosts($shortname);
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts($shortname)
	{
		$class_name = SwatDBClassMap::get('BlorgTag');
		$tag = new $class_name();
		$tag->setDatabase($this->app->db);
		if (!$tag->loadByShortname($shortname, $this->app->getInstance())) {
			throw new SiteNotFoundException('Page not found.');
		}

		$this->tag = $tag;

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select * from BlorgPost
			where
				id in (select post from BlorgPostTagBinding where tag = %s) and
				instance %s %s and enabled = %s
			order by publish_date desc',
			$this->app->db->quote($tag->id, 'integer'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$this->app->db->setLimit($this->max_entries);

		$wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$this->posts = SwatDB::query($this->app->db, $sql, $wrapper);
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
		$tag_href = $blorg_base_href.'tag/'.$this->tag->shortname;

		$this->feed = new XML_Atom_Feed($blorg_base_href,
			sprintf(Blorg::_('%s - %s'),
				$this->app->config->site->title,
				$this->tag->title));

		$this->feed->setSubTitle(sprintf(
			Blorg::_('Posts Tagged: %s'),
			$this->tag->title));

		$this->feed->addLink($site_base_href.$this->source, 'self',
			'application/atom+xml');

		$this->feed->addLink($tag_href, 'alternate', 'text/html');

		$this->feed->setGenerator('Blörg');
		$this->feed->setBase($site_base_href);

		$this->feed->setLogo($site_base_href.'images/elements/title-atom.png');
		$this->feed->setIcon($site_base_href.'favicon.ico');

		$threshold = new SwatDate();
		$threshold->toUTC();
		$threshold->subtractSeconds($this->recent_period);

		$count = 0;

		foreach ($this->posts as $post) {
			$count++;

			if ($count > $this->max_entries ||
				($count > $this->min_entries) &&
					$post->publish_date->before($threshold))
				break;

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

			$entry = new XML_Atom_Entry($post_uri, $post->title,
				$post->publish_date);

			if (strlen($post->extended_bodytext) > 0) {
				$full_bodytext = $post->bodytext.$post->extended_bodytext;
				$entry->setSummary($post->bodytext, 'html');
				$entry->setContent($full_bodytext, 'html');
			} else {
				$entry->setContent($post->bodytext, 'html');
			}

			foreach ($post->tags as $tag) {
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

			if ($post->author->show) {
				$author_uri = $blorg_base_href.'author/'.
					$post->author->shortname;
			} else {
				$author_uri = '';
			}

			$entry->addAuthor($post->author->name, $author_uri,
				$post->author->email);

			$visible_comment_count = count($post->getVisibleComments());
			if ($post->comment_status == BlorgPost::COMMENT_STATUS_OPEN ||
				$post->comment_status == BlorgPost::COMMENT_STATUS_MODERATED ||
				($post->comment_status == BlorgPost::COMMENT_STATUS_LOCKED &&
				$visible_comment_count > 0)) {
				$entry->addLink($post_uri.'#comments', 'comments', 'text/html');
			}

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
