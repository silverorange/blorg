<?php

require_once 'Blorg/BlorgGadget.php';
require_once 'Blorg/BlorgViewFactory.php';
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
class BlorgAuthorsGadget extends BlorgGadget
{
	// {{{ public function display()

	public function display()
	{
		parent::display();

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
		$sql = sprintf('select id, name, shortname, email, description
			from BlorgAuthor
			where show = %s and instance %s %s
			order by displayorder',
			$this->app->db->quote(true, 'boolean'),
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'));

		return SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgAuthorWrapper'));
	}

	// }}}
	// {{{ protected function getAuthorRelativeUri()

	protected function getAuthorRelativeUri(BlorgAuthor $author)
	{
		$path = $this->app->config->blorg->path.'author';
		return sprintf('%s/%s',
			$path,
			$author->shortname);
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor(BlorgAuthor $author)
	{
		$view = BlorgViewFactory::build('author', $this->app);
		$view->setPartMode('name', BlorgView::MODE_SUMMARY);
		$view->setPartMode('bodytext', BlorgView::MODE_NONE);
		$view->setPartMode('email', BlorgView::MODE_NONE);
		$view->setPartMode('description', BlorgView::MODE_ALL, false);
		$view->setPartMode('post_count', BlorgView::MODE_NONE, false);
		$view->display($author);
	}

	// }}}
}

?>
