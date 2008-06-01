<?php

require_once 'Blorg/views/BlorgView.php';

/**
 * View for Blörg reply objects
 *
 * By default, this reply view's parts are:
 *
 * - author    - The author of the reply. Supports MODE_ALL and MODE_NONE. By
 *               default, links to the author details page for replies made by
 *               site authors and links to the entered web address for non-site
 *               author replies.
 * - permalink - Permalink (and publish date) of the reply. Supports MODE_ALL
 *               and MODE_NONE. Links to the reply on the reply's post page
 *               by default.
 * - bodytext  - The reply bodytext. Supports MODE_ALL, MODE_SUMMARY and
 *               MODE_NONE. The summary mode displays a condensed, ellipsized
 *               version of the bodytext. Does not link anywhere.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgReplyView extends BlorgView
{
	// {{{ protected function define()

	protected function define()
	{
		$this->definePart('author');
		$this->definePart('permalink');
		$this->definePart('bodytext');
	}

	// }}}

	// general display methods
	// {{{ public function display()

	public function display($reply)
	{
		if (!($reply instanceof BlorgReply)) {
			throw new InvalidArgumentException(sprintf('The view "%s" can '.
				'only display BlorgReply objects.',
				get_class($this)));
		}

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'reply'.$reply->id;
		$div_tag->class = 'reply';
		$div_tag->open();

		$this->displayHeader($reply);
		$this->displayBody($reply);

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader(BlorgReply $reply)
	{
		ob_start();
		$this->displayAuthor($reply);
		$author = ob_get_clean();

		if (strlen($author) > 0) {
			$elements[] = $author;
		}

		ob_start();
		$this->displayPermalink($reply);
		$permalink = ob_get_clean();

		if (strlen($permalink) > 0) {
			$elements[] = $permalink;
		}

		$heading_tag = new SwatHtmlTag('h4');
		$heading_tag->class = 'reply-title';
		$heading_tag->open();

		echo implode(' ', $elements);

		$heading_tag->close();
	}

	// }}}
	// {{{ protected function displayBody()

	protected function displayBody(BlorgReply $reply)
	{
		$this->displayBodytext($reply);
	}

	// }}}

	// part display methods
	// {{{ protected function displayAuthor()

	protected function displayAuthor(BlorgReply $reply)
	{
		if ($this->getMode('author') > BlorgView::MODE_NONE) {
			$link = $this->getLink('author');
			if ($reply->author === null) {
				// anonymous author
				$reply_link = (is_string($link)) ? $link : $reply->link;

				if (strlen($reply_link) > 0 && $link !== false) {
					$anchor_tag = new SwatHtmlTag('a');
					$anchor_tag->href = $reply_link;
					$anchor_tag->class = 'reply-author';
					$anchor_tag->setContent($reply->fullname);
					$anchor_tag->display();
				} else {
					$span_tag = new SwatHtmlTag('span');
					$span_tag->class = 'reply-author';
					$span_tag->setContent($reply->fullname);
					$span_tag->display();
				}
			} else {
				// system author
				if ($reply->author->show && $link !== false) {
					$span_tag = new SwatHtmlTag('span');
					$span_tag->class = 'vcard author';
					$span_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					$anchor_tag->class = 'reply-author system-reply-author '.
						'fn url';

					if (is_string($link)) {
						$anchor_tag->href = $link;
					} else {
						$anchor_tag->href =
							$this->getAuthorRelativeUri($reply->author);
					}

					$anchor_tag->setContent($reply->author->name);
					$anchor_tag->display();

					$span_tag->close();
				} else {
					$span_tag = new SwatHtmlTag('span');
					$span_tag->class = 'reply-author system-reply-author';
					$span_tag->setContent($this->reply->author->name);
					$span_tag->display();
				}
			}
		}
	}

	// }}}
	// {{{ protected function displayPermalink()

	protected function displayPermalink(BlorgReply $reply)
	{
		if ($this->getMode('permalink') > BlorgView::MODE_NONE) {
			$link = $this->getLink('permalink');
			if ($link === false) {
				$permalink_tag = new SwatHtmlTag('span');
			} else {
				$permalink_tag = new SwatHtmlTag('a');
				if ($link === true) {
					$permalink_tag->href = $this->getReplyRelativeUri($reply);
				} else {
					$permalink_tag->href = $link;
				}
			}
			$permalink_tag->class = 'permalink';
			$permalink_tag->open();

			// display machine-readable date in UTC
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'reply-published';
			$abbr_tag->title =
				$reply->createdate->getDate(DATE_FORMAT_ISO_EXTENDED);

			// display human-readable date in local time
			$date = clone $reply->createdate;
			$date->convertTZ($this->app->default_time_zone);
			$abbr_tag->setContent($date->format(SwatDate::DF_DATE));
			$abbr_tag->display();

			$permalink_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayBodytext()

	protected function displayBodytext(BlorgReply $reply)
	{
		switch ($this->getMode('bodytext')) {
		case BlorgView::MODE_ALL:
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'reply-content';
			$div_tag->setContent(
				BlorgReply::getBodyTextXhtml($reply->bodytext), 'text/xml');

			$div_tag->display();
			break;
		case BlorgView::MODE_SUMMARY:
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'reply-content';
			$div_tag->setContent(SwatString::ellipsizeRight(
				SwatString::condense(BlorgReply::getBodyTextXhtml(
					$reply->bodytext))), 'text/xml');

			$div_tag->display();
			break;
		}
	}

	// }}}
}

?>
