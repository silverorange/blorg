<?php

require_once 'Site/SiteViewFactory.php';
require_once 'Site/gadgets/SiteGadget.php';
require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * Displays authors
 *
 * There are no settings for this gadget.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAuthorsGadget extends SiteGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$authors = $this->getAuthors();

		if (count($authors) == 1) {
			$this->displayAuthor($authors->getFirst());
		} else {
			echo '<ul>';

			foreach ($authors as $author) {
				$li_tag = new SwatHtmlTag('li');
				$li_tag->open();

				$this->displayAuthor($author);

				$li_tag->close();
			}

			echo '</ul>';
		}
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Authors'));
		$this->defineDescription(Blorg::_(
			'Lists the visible authors of this site. The author '.
			'descriptions are also displayed if they exist.'));
	}

	// }}}
	// {{{ protected function getAuthors()

	protected function getAuthors()
	{
		$authors = false;

		if (isset($this->app->memcache)) {
			$authors = $this->app->memcache->getNs('authors', 'authors_gadget');
		}

		if ($authors === false) {
			$sql = sprintf('select id, name, shortname, email, description
				from BlorgAuthor
				where visible = %s and instance %s %s
				order by displayorder',
				$this->app->db->quote(true, 'boolean'),
				SwatDB::equalityOperator($this->app->getInstanceId()),
				$this->app->db->quote($this->app->getInstanceId(), 'integer'));

			$authors = SwatDB::query($this->app->db, $sql,
				SwatDBClassMap::get('BlorgAuthorWrapper'));

			if (isset($this->app->memcache)) {
				$this->app->memcache->setNs('authors', 'authors_gadget',
					$authors);
			}
		} else {
			$authors->setDatabase($this->app->db);
		}

		return $authors;
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor(BlorgAuthor $author)
	{
		$view = SiteViewFactory::get($this->app, 'author');
		$view->setPartMode('name',        SiteView::MODE_SUMMARY);
		$view->setPartMode('bodytext',    SiteView::MODE_NONE);
		$view->setPartMode('email',       SiteView::MODE_NONE);
		$view->setPartMode('description', SiteView::MODE_ALL, false);
		$view->setPartMode('post_count',  SiteView::MODE_NONE, false);
		$view->display($author);
	}

	// }}}
}

?>
