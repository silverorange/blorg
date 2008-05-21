<?php

require_once 'Blorg/BlorgGadget.php';
require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';

/**
 * Displays authors
 *
 * There are no settings for this gadget.
 *
 * TODO: show author post count in span.
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
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'blorg-author';
		$div_tag->open();

		$header_tag = new SwatHtmlTag('h4');
		$header_tag->open();

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $this->getAuthorRelativeUri($author);
		$anchor_tag->open();

		echo SwatString::minimizeEntities($author->name), ' ';

// TODO: Show author post count here.
//		$span_tag =  new SwatHtmlTag('span');
//		$span_tag->setContent(sprintf(Blorg::ngettext(
//			'(%s reply)', '(%s replies)', $conversation->reply_count),
//			$locale->formatNumber($conversation->reply_count)));

//		$span_tag->display();

		$anchor_tag->close();

		$header_tag->close();

		if (strlen($author->description) > 0) {
			$description_div_tag = new SwatHtmlTag('div');
			$description_div_tag->setContent($author->description, 'text/xml');
			$description_div_tag->display();
		}

		$div_tag->close();
	}
}

?>
