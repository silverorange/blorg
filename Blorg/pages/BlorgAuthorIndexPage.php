<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePageDecorator.php';
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
class BlorgAuthorIndexPage extends SitePageDecorator
{
	// {{{ protected properties

	/**
	 * @var BlorgAuthorWrapper
	 */
	protected $authors;

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();

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
	// {{{ protected function buildContent()

	protected function buildContent()
	{
		$this->layout->startCapture('content');
		Blorg::displayAd($this->app, 'top');
		$this->displayAuthors();
		Blorg::displayAd($this->app, 'bottom');
		$this->layout->endCapture();
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
		$this->layout->data->title = Blorg::_('Authors');
		$this->layout->data->html_title = Blorg::_('Authors');
	}

	// }}}
	// {{{ protected function buildMetaDescription()

	protected function buildMetaDescription()
	{
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
			$view = BlorgViewFactory::get($this->app, 'author');
			$view->setPartMode('name', BlorgView::MODE_SUMMARY);
			$view->setPartMode('bodytext', BlorgView::MODE_NONE);
			$view->setPartMode('description', BlorgView::MODE_SUMMARY);
			$view->setPartMode('post_count', BlorgView::MODE_NONE);
			$view->setPartMode('email', BlorgView::MODE_NONE);
			$view->display($author);
		}
	}

	// }}}
}

?>
