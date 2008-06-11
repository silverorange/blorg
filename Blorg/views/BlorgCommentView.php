<?php

require_once 'Blorg/views/BlorgView.php';

/**
 * View for Blörg comment objects
 *
 * By default, this comment view's parts are:
 *
 * - author    - The author of the comment. Supports MODE_ALL and MODE_NONE. By
 *               default, links to the author details page for comments made by
 *               site authors. Non-site author comments are not linked.
 * - link      - The web address of the comment. Supports MODE_ALL and MODE_NONE.
 *               By default, links to the web address entered in the comment.
 * - permalink - Permalink (and publish date) of the comment. Supports MODE_ALL
 *               and MODE_NONE. Links to the comment on the comment's post page
 *               by default.
 * - bodytext  - The comment bodytext. Supports MODE_ALL, MODE_SUMMARY and
 *               MODE_NONE. The summary mode displays a condensed, ellipsized
 *               version of the bodytext. Does not link anywhere.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentView extends BlorgView
{
	// {{{ protected function define()

	protected function define()
	{
		$this->definePart('author');
		$this->definePart('link');
		$this->definePart('permalink');
		$this->definePart('bodytext');
	}

	// }}}

	// general display methods
	// {{{ public function display()

	public function display($comment)
	{
		if (!($comment instanceof BlorgComment)) {
			throw new InvalidArgumentException(sprintf('The view "%s" can '.
				'only display BlorgComment objects.',
				get_class($this)));
		}

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'comment'.$comment->id;
		$div_tag->class = 'comment';
		$div_tag->open();

		$this->displayHeader($comment);
		$this->displayBody($comment);

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader(BlorgComment $comment)
	{
		$heading_tag = new SwatHtmlTag('h4');
		$heading_tag->class = 'comment-title';

		$heading_tag->open();

		ob_start();
		$this->displayAuthor($comment);
		$author = ob_get_clean();

		if ($author != '') {
			$elements[] = $author;
		}

		ob_start();
		$this->displayPermalink($comment);
		$permalink = ob_get_clean();

		if ($permalink != '') {
			$elements[] = $permalink;
		}

		echo implode(' - ', $elements);

		$heading_tag->close();

		$this->displayLink($comment);
	}

	// }}}
	// {{{ protected function displayBody()

	protected function displayBody(BlorgComment $comment)
	{
		$this->displayBodytext($comment);
	}

	// }}}

	// part display methods
	// {{{ protected function displayAuthor()

	protected function displayAuthor(BlorgComment $comment)
	{
		if ($this->getMode('author') > BlorgView::MODE_NONE) {
			$link = $this->getLink('author');
			if ($comment->author === null) {
				// anonymous author
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'comment-author';
				$span_tag->setContent($comment->fullname);
				$span_tag->display();
			} else {
				// system author
				if ($comment->author->show && $link !== false) {
					$span_tag = new SwatHtmlTag('span');
					$span_tag->class = 'vcard author';
					$span_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					$anchor_tag->class =
						'comment-author system-comment-author fn url';

					if (is_string($link)) {
						$anchor_tag->href = $link;
					} else {
						$anchor_tag->href =
							$this->getAuthorRelativeUri($comment->author);
					}

					$anchor_tag->setContent($comment->author->name);
					$anchor_tag->display();

					$span_tag->close();
				} else {
					$span_tag = new SwatHtmlTag('span');
					$span_tag->class = 'comment-author system-comment-author';
					$span_tag->setContent($this->comment->author->name);
					$span_tag->display();
				}
			}
		}
	}

	// }}}
	// {{{ protected function displayLink()

	protected function displayLink(BlorgComment $comment)
	{
		if ($this->getMode('link') > BlorgView::MODE_NONE) {
			if ($comment->link != '') {
				$link = $this->getLink('link');

				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'comment-link';
				$div_tag->open();

				if ($link !== false) {
					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($link)) {
						$anchor_tag->href = $link;
					} else {
						$anchor_tag->href = $comment->link;
					}
					$anchor_tag->class = 'comment-link';
					$anchor_tag->setContent($comment->link);
					$anchor_tag->display();
				} else {
					$span_tag = new SwatHtmlTag('span');
					$span_tag->class = 'comment-link';
					$span_tag->setContent($comment->link);
					$span_tag->display();
				}

				$div_tag->close();
			}
		}
	}

	// }}}
	// {{{ protected function displayPermalink()

	protected function displayPermalink(BlorgComment $comment)
	{
		if ($this->getMode('permalink') > BlorgView::MODE_NONE) {
			$link = $this->getLink('permalink');
			if ($link === false) {
				$permalink_tag = new SwatHtmlTag('span');
			} else {
				$permalink_tag = new SwatHtmlTag('a');
				if ($link === true) {
					$permalink_tag->href =
						$this->getCommentRelativeUri($comment);
				} else {
					$permalink_tag->href = $link;
				}
			}
			$permalink_tag->class = 'permalink';
			$permalink_tag->open();

			// display machine-readable date in UTC
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'comment-published';
			$abbr_tag->title =
				$comment->createdate->getDate(DATE_FORMAT_ISO_EXTENDED);

			// display human-readable date in local time
			$date = clone $comment->createdate;
			$date->convertTZ($this->app->default_time_zone);
			$abbr_tag->setContent($date->format(SwatDate::DF_DATE_TIME));
			$abbr_tag->display();

			$permalink_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayBodytext()

	protected function displayBodytext(BlorgComment $comment)
	{
		switch ($this->getMode('bodytext')) {
		case BlorgView::MODE_ALL:
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'comment-content';
			$div_tag->setContent(
				BlorgComment::getBodyTextXhtml($comment->bodytext), 'text/xml');

			$div_tag->display();
			break;
		case BlorgView::MODE_SUMMARY:
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'comment-content';
			$div_tag->setContent(SwatString::ellipsizeRight(
				SwatString::condense(BlorgComment::getBodyTextXhtml(
					$comment->bodytext))), 'text/xml');

			$div_tag->display();
			break;
		}
	}

	// }}}
}

?>
