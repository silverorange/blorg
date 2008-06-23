<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/dataobjects/BlorgTagWrapper.php';

/**
 * Displays an index of all tags with post counts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagArchivePage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var BlorgTagWrapper
	 */
	 protected $tags;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new archive page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout)
	{
		parent::__construct($app, $layout);
		$this->initTags();
	}

	// }}}
	// {{{ private function initTags()

	private function initTags()
	{
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select BlorgTag.*
				from BlorgTag
				inner join Instance on BlorgTag.instance = Instance.id
				where instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$wrapper = SwatDBClassMap::get('BlorgTagWrapper');
		$this->tags = SwatDB::query($this->app->db, $sql, $wrapper);
	}

	// }}}

	// {{{ public function build()

	public function build()
	{
		$this->buildNavBar();

		$this->layout->startCapture('content');
		Blorg::displayAd($this->app, 'top');
		$this->displayArchive();
		Blorg::displayAd($this->app, 'bottom');
		$this->layout->endCapture();

		$this->layout->data->title = Blorg::_('Tags');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$path = $this->app->config->blorg->path.'tag';
		$this->layout->navbar->createEntry(Blorg::_('Tags'), $path);
	}

	// }}}
	// {{{ protected function displayArchive()

	protected function displayArchive()
	{
		$path = $this->app->config->blorg->path.'tag';
		$locale = SwatI18NLocale::get();

		$ul_tag = new SwatHtmLTag('ul');
		$ul_tag->class = 'blorg-archive-tags';
		$ul_tag->open();
		foreach ($this->tags as $tag) {
			$li_tag = new SwatHtmlTag('li');
			$li_tag->open();
			$anchor_tag = new SwatHtmlTag('a');
			$anchor_tag->href = sprintf('%s/%s', $path, $tag->shortname);
			$anchor_tag->setContent($tag->title);

			$post_count = $tag->getPostCount();
			$post_count_span = new SwatHtmlTag('span');
			$post_count_span->setContent(sprintf(
				Blorg::ngettext(' (%s post)', ' (%s posts)',
				$post_count),
				$locale->formatNumber($post_count)));

			$heading_tag = new SwatHtmlTag('h4');
			$heading_tag->class = 'blorg-archive-tag-title';
			$heading_tag->open();

			$anchor_tag->display();
			$post_count_span->display();
			$heading_tag->close();
			$li_tag->close();
		}
		$ul_tag->close();
	}

	// }}}
}

?>
