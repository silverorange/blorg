<?php

/**
 * View for Blörg comment objects
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentView extends SiteCommentView
{
	// {{{ protected function getRelativeUri()

	protected function getRelativeUri(SiteComment $comment)
	{
		return Blorg::getCommentRelativeUri($this->app, $comment);
	}

	// }}}
	// {{{ protected function getAuthorRelativeUri()

	protected function getAuthorRelativeUri(BlorgAuthor $author)
	{
		return Blorg::getAuthorRelativeUri($this->app, $author);
	}

	// }}}

	// part display methods
	// {{{ protected function displayAuthor()

	protected function displayAuthor(SiteComment $comment)
	{
		if ($this->getMode('author') > SiteView::MODE_NONE) {
			$link = $this->getLink('author');
			if ($comment->author === null) {
				parent::displayAuthor($comment);
			} else {
				// System author
				//
				// Don't link to non-visible system authors but still show
				// their name on the comment.
				if ($comment->author->visible && $link !== false) {
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
					$span_tag->setContent($comment->author->name);
					$span_tag->display();
				}
			}
		}
	}

	// }}}
}

?>
