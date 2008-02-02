<?php

require_once 'Blorg/BlorgPostLongView.php';
require_once 'Swat/SwatString.php';

/**
 * Full display for a Blörg post
 *
 * Displays as a complete weblog post with title, header information, full
 * bodytext and replies.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostFullView extends BlorgPostLongView
{
	// {{{ public function display()

	public function display($link = false)
	{
		echo '<div class="entry hentry">';
		$this->displayHeader($link);
		$this->displayBody();
		echo '</div>';

		$replies_div = new SwatHtmlTag('div');
		$replies_div->id = 'replies'.$this->post->id;
		$replies_div->class = 'entry-replies';
		$replies_div->open();

		$this->displayReplies();

		$replies_div->close();
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
	// {{{ protected function displayReplies()

	protected function displayReplies()
	{
		if ($this->post->reply_status != BlorgPost::REPLY_STATUS_CLOSED) {
			foreach ($this->post->replies as $reply) {
				if ($reply->approved && $reply->show && !$reply->spam) {
					$this->displayReply($reply);
				}
			}
		}
	}

	// }}}
	// {{{ protected function displayReply()

	protected function displayReply(BlorgReply $reply)
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'reply'.$reply->id;
		$div_tag->class = 'reply';
		$div_tag->open();

		$this->displayReplyHeader($reply);
		$this->displayReplyBody($reply);

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayReplyHeader()

	protected function displayReplyHeader(BlorgReply $reply)
	{
		$heading_tag = new SwatHtmlTag('h4');
		$heading_tag->class = 'reply-title';
		$heading_tag->open();

		$this->displayReplyAuthor($reply);
		echo ' ';
		$this->displayReplyPermalink($reply);

		$heading_tag->close();
	}

	// }}}
	// {{{ protected function displayReplyAuthor()

	protected function displayReplyAuthor(BlorgReply $reply)
	{
		if ($reply->author === null) {
			// anonymous author
			if (strlen($reply->link) > 0) {
				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->href = $reply->link;
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
			//if ($reply->author->show) {
				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->class = 'reply-author system-reply-author';
				$anchor_tag->href = $this->getAuthorRelativeUri($reply->author);
				$anchor_tag->setContent($reply->author->name);
				$anchor_tag->display();
			//} else {
			//	$span_tag = new SwatHtmlTag('span');
			//	$span_tag->class = 'reply-author system-reply-author';
			//	$span_tag->setContent($reply->author->name);
			//	$span_tag->display();
			//}
		}
	}

	// }}}
	// {{{ protected function displayReplyPermalink()

	protected function displayReplyPermalink(BlorgReply $reply)
	{
		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $this->getReplyRelativeUri($reply);
		$anchor_tag->open();

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

		$anchor_tag->close();
	}

	// }}}
	// {{{ protected function displayReplyBody()

	protected function displayReplyBody(BlorgReply $reply)
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'reply-content';
		$div_tag->setContent($this->getReplyBodyText($reply), 'text/xml');
		$div_tag->display();
	}

	// }}}
	// {{{ protected function getReplyRelativeUri()

	protected function getReplyRelativeUri(BlorgReply $reply)
	{
		return $this->getPostRelativeUri().'#reply'.$reply->id;
	}

	// }}}
	// {{{ protected function getReplyBodytext()

	protected function getReplyBodytext(BlorgReply $reply)
	{
		$bodytext = $reply->bodytext;
		$bodytext = str_replace('%', '%%', $bodytext);

		$allowed_tags = '/(<a href="http[^"]+?">|<\/a>|<\/?strong>|<\/?em>)/ui';
		$matches = array();
		preg_match_all($allowed_tags, $bodytext, $matches);
		$bodytext = preg_replace($allowed_tags, '%s', $bodytext);

		$bodytext = SwatString::minimizeEntities($bodytext);
		$bodytext = vsprintf($bodytext, $matches[0]);
		$bodytext = SwatString::toXHTML($bodytext);

		return $bodytext;
	}

	// }}}
}

?>
