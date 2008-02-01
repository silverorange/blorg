<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatString.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/BlorgPageFactory.php';

/**
 * Base class for Blörg post views
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
abstract class BlorgPostView
{
	// {{{ protected properties

	protected $post;
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, BlorgPost $post)
	{
		$this->post = $post;
		$this->app = $app;
	}

	// }}}
	// {{{ abstract public function display()

	/**
	 * Displays this view for this view's post
	 *
	 * @param boolean $link optional. Whether or not to link the post title to
	 *                       the post itself. Defaults to false.
	 */
	abstract public function display($link = false);

	// }}}
	// {{{ protected function displayHeader()

	/**
	 * Displays the title and meta information for a weblog post
	 *
	 * @param boolean $link optional. Whether or not to link the post title to
	 *                       the post itself. Defaults to false.
	 */
	protected function displayHeader($link = false)
	{
		if (strlen($this->post->title) > 0) {
			$header_tag = new SwatHtmlTag('h3');
			$header_tag->class = 'entry-title';
			$header_tag->id = sprintf('post_%s', $this->post->shortname);

			if ($link) {
				$header_tag->open();

				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->href = $this->getPostRelativeUri();
				$anchor_tag->setContent($this->post->title);
				$anchor_tag->display();

				$header_tag->close();
			} else {
				$header_tag->setContent($this->post->title);
				$header_tag->display();
			}
		}

		$this->displaySubTitle();
	}

	// }}}
	// {{{ protected function displaySubTitle()

	/**
	 * Displays the title and meta information for a weblog post
	 */
	protected function displaySubTitle()
	{
		$base = 'news/'; // TODO

		// display author information
		//if ($this->post->author->) { // TODO: make sure author can be displayed
			ob_start();

			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'vcard author';
			$span_tag->open();

			$year = $this->post->post_date->getYear();
			$month_name = BlorgPageFactory::$month_names[
				$this->post->post_date->getMonth()];

			$anchor_tag = new SwatHtmlTag('a');
			$author_tag->class = 'fn url';
			$anchor_tag->href =
				$this->getAuthorRelativeUri($this->post->author);

			$anchor_tag->setContent($this->post->author->name);
			$anchor_tag->display();

			$span_tag->close();

			$author = ob_get_clean();
		//}

		// display date information
		ob_start();

		$this->displayPermalink();

		$post_date = ob_get_clean();

		echo '<div class="entry-subtitle">';

		printf(Blorg::_('Posted by %s on %s'),
			$author, $post_date);

		echo '</div>';
	}

	// }}}
	// {{{ protected function displayPermalink()

	/**
	 * Displays the date permalink for a weblog post
	 */
	protected function displayPermalink()
	{
		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $this->getPostRelativeUri();
		$anchor_tag->open();

		// display machine-readable date in UTC
		$abbr_tag = new SwatHtmlTag('abbr');
		$abbr_tag->class = 'published';
		$abbr_tag->title =
			$this->post->post_date->getDate(DATE_FORMAT_ISO_EXTENDED);

		// display human-readable date in local time
		$date = clone $this->post->post_date;
		$date->convertTZ($this->app->default_time_zone);
		$abbr_tag->setContent($date->format(SwatDate::DF_DATE_LONG));
		$abbr_tag->display();

		$anchor_tag->close();
	}

	// }}}
	// {{{ protected function displayReplyCount()

	/**
	 * Displays the number of replies for a weblog post
	 */
	protected function displayReplyCount()
	{
		$count = count($this->post->replies);

		$span_tag = new SwatHtmlTag('span');
		$span_tag->class = 'reply-count';
		if ($count == 0) {
			$span_tag->setContent(Blorg::_('no replies'));
		} else {
			$locale = SwatI18NLocale::get();
			$span_tag->setContent(sprintf(
				Blorg::ngettext('%s reply', '%s replies'),
				$locale->formatNUmber($count)));
		}
		$span_tag->display();
	}

	// }}}
	// {{{ protected function getPostRelativeUri()

	protected function getPostRelativeUri()
	{
		$path = $this->app->config->blorg->path.'archive';

		$date = clone $this->post->post_date;
		$date->convertTZ($this->app->default_time_zone);
		$year = $date->getYear();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];

		return sprintf('%s/%s/%s/%s',
			$path,
			$year,
			$month_name,
			$this->post->shortname);
	}

	// }}}
	// {{{ protected function getAuthorRelativeUri()

	protected function getAuthorRelativeUri(AdminUser $author)
	{
		$path = $this->app->config->blorg->path.'author';
		return sprintf('%s/%s',
			$path,
			$author->email); // TODO: use shortname
	}

	// }}}
}

?>
