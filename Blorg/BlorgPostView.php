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

	abstract public function display();

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
				$base = 'news/'; // TODO
				$year = $this->post->post_date->getYear();
				$month_name = BlorgPageFactory::$month_names[
					$this->post->post_date->getMonth()];

				$header_tag->open();

				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->href = sprintf('%sarchive/%s/%s/%s',
					$base, $year, $month_name, $this->post->shortname);

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

			$anchor_tag = new SwatHtmlTag('a');
			$author_tag->class = 'fn url';
			$anchor_tag->href = sprintf('%sauthor/%s',
				$base, $this->post->author->email); // TODO: use shortname

			$anchor_tag->setContent($this->post->author->name);
			$anchor_tag->display();

			$span_tag->close();
			$author = ob_get_clean();
		//}

		// display date information
		ob_start();
		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = sprintf('%sauthor/%s',
			$base, $this->post->author->email); // TODO: use shortname

		$abbr_tag   = new SwatHtmlTag('abbr');
		$abbr_tag->class = 'published';
		$abbr_tag->title =
			$this->post->post_date->format('%Y-%m-%dT%H-%M-%S%o');

		$this->post->post_date->convertTZ($this->app->default_time_zone);
		$abbr_tag->setContent(
			$this->post->post_date->format(SwatDate::DF_DATE_LONG));

		$anchor_tag->open();
		$abbr_tag->display();
		$anchor_tag->close();
		$post_date = ob_get_clean();

		echo '<div class="entry-subtitle">';

		printf(Blorg::_('Posted by %s on %s'),
			$author, $post_date);

		echo '</div>';
	}

	// }}}
}

?>
