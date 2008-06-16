<?php

require_once 'Blorg/BlorgGadget.php';
require_once 'Site/dataobjects/SiteArticleWrapper.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Displays top-level articles
 *
 * This gadget has no settings.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgArticleGadget extends BlorgGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$articles = $this->getArticles();
		if (count($articles) > 0) {
			echo '<ul>';

			$locale = SwatI18NLocale::get();
			foreach ($articles as $article) {
				echo '<li>';

				$title_div = new SwatHtmlTag('div');
				$title_div->open();

				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->href = $this->getArticleRelativeUri($article);
				$anchor_tag->setContent($article->title);
				$anchor_tag->display();

				$title_div->close();

				$content_div = new SwatHtmlTag('div');
				$content_div->setContent($article->description, 'text/xml');
				$content_div->display();

				echo '</li>';
			}

			echo '</ul>';
		}
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Top-Level Articles'));
		$this->defineDescription(Blorg::_(
			'Displays a list of top-level article links.'));
	}

	// }}}
	// {{{ protected function getArticles()

	protected function getArticles()
	{
		$sql = sprintf('select title, shortname, description from Article
			where enabled = %s and visible = %s and parent is null
				and instance %s %s
			order by title asc',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote(true, 'boolean'),
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'));

		$wrapper = SwatDBClassMap::get('SiteArticleWrapper');

		return SwatDB::query($this->app->db, $sql, $wrapper);
	}

	// }}}
	// {{{ protected function getArticleRelativeUri()

	protected function getArticleRelativeUri(SiteArticle $article)
	{
		$path = $this->app->config->blorg->path.'article';
		return $path.'/'.$article->shortname;
	}

	// }}}
}

?>
