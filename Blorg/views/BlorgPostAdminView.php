<?php

require_once 'Blorg/views/BlorgPostLongView.php';
require_once 'Swat/SwatString.php';

/**
 * Full display for a Blörg post in the admin
 *
 * Displays as a complete weblog post with title, header information and full
 * bodytext. Replies are not displayed. Links are not linked.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostAdminView extends BlorgPostLongView
{
	// {{{ public function display()

	public function display($link = false)
	{
		echo '<div class="entry hentry">';

		$this->displayHeader();
		$this->displayBody();

		echo '</div>';
	}

	// }}}
	// {{{ protected function displayExtendedBody()

	protected function displayExtendedBody()
	{
		if (strlen($this->post->extended_bodytext) > 0) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'entry-content entry-content-extended';
			$div_tag->setContent($this->post->extended_bodytext, 'text/xml');
			$div_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayPermalink()

	/**
	 * Displays the date permalink for a weblog post
	 *
	 * Admin permalinks are not links.
	 */
	protected function displayPermalink()
	{
		// display machine-readable date in UTC
		$abbr_tag   = new SwatHtmlTag('abbr');
		$abbr_tag->class = 'published';
		$abbr_tag->title =
			$this->post->post_date->getDate(DATE_FORMAT_ISO_EXTENDED);

		// display human-readable date in local time
		$date = clone $this->post->post_date;
		$date->convertTZ($this->app->default_time_zone);
		$abbr_tag->setContent($date->format(SwatDate::DF_DATE_LONG));
		$abbr_tag->display();
	}

	// }}}
	// {{{ protected function displayPostAuthor()

	protected function displayPostAuthor()
	{
		//if ($this->post->author->) { // TODO: make sure author can be displayed
			$span_tag = new SwatHtmlTag('span');
			$span_tag->class = 'vcard author';
			$span_tag->open();

			$name_span_tag = new SwatHtmlTag('a');
			$name_span_tag->class = 'fn url';
			$name_span_tag->setContent($this->post->author->name);
			$name_span_tag->display();

			$span_tag->close();
		//}
	}

	// }}}
}

?>
