<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePage.php';
require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';

/**
 * Author index page for Blörg
 *
 * Loads and displays the blog's authors.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAuthorIndexPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var BlorgAuthorWrapper
	 */
	protected $authors;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new author index page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout,
		array $arguments = array())
	{
		parent::__construct($app, $layout, $arguments);

		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select * from BlorgAuthor
			where instance %s %s and visible = %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$this->authors = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgAuthorWrapper'));
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		if (isset($this->layout->navbar))
			$this->buildNavBar();

		$this->buildTitle();

		$this->layout->startCapture('content');
		Blorg::displayAd($this->app, 'top');
		$this->displayAuthors();
		Blorg::displayAd($this->app, 'bottom');
		$this->layout->endCapture();

		$this->layout->data->title = Blorg::_('Authors');
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$path = $this->app->config->blorg->path.'author';
		$this->layout->navbar->createEntry(Blorg::_('Authors'), $path);
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		$this->layout->data->html_title = Blorg::_('Authors');
		$authors = array();

		foreach ($this->authors as $author)
			$authors[] = sprintf(Blorg::_('%s - %s'),
				$author->name,
				SwatString::ellipsizeRight(SwatString::condense(
					$author->description), 200));

		$this->layout->data->meta_description = SwatString::minimizeEntities(
			implode('; ', $authors));
	}

	// }}}
	// {{{ protected function displayAuthors()

	protected function displayAuthors()
	{
		foreach ($this->authors as $author) {
			$view = SiteViewFactory::get($this->app, 'author');
			$view->setPartMode('name', SiteView::MODE_SUMMARY);
			$view->setPartMode('bodytext', SiteView::MODE_NONE);
			$view->setPartMode('description', SiteView::MODE_SUMMARY);
			$view->setPartMode('post_count', SiteView::MODE_NONE);
			$view->setPartMode('email', SiteView::MODE_NONE);
			$view->display($author);
		}
	}

	// }}}
}

?>
