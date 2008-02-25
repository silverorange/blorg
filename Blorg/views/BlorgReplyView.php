<?php

/**
 * Display for a Blörg reply
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgReplyView
{
	// {{{ protected properties

	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function display()

	public function display(BlorgReply $reply)
	{
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
		$heading_tag = new SwatHtmlTag('h4');
		$heading_tag->class = 'reply-title';
		$heading_tag->open();

		$this->displayAuthor($reply);
		echo ' ';
		$this->displayPermalink($reply);

		$heading_tag->close();
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor(BlorgReply $reply)
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
			if ($reply->author->show) {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'vcard author';
				$span_tag->open();

				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->class = 'reply-author system-reply-author fn url';
				$anchor_tag->href =
					$this->getAuthorRelativeUri($reply->author);

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

	// }}}
	// {{{ protected function displayPermalink()

	protected function displayPermalink(BlorgReply $reply)
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
	// {{{ protected function displayBody()

	protected function displayBody(BlorgReply $reply)
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'reply-content';
		$div_tag->setContent(
			BlorgReply::getBodyTextXhtml($reply->bodytext), 'text/xml');

		$div_tag->display();
	}

	// }}}
	// {{{ protected function getReplyRelativeUri()

	protected function getReplyRelativeUri(BlorgReply $reply)
	{
		return $this->getPostRelativeUri($reply->post).'#reply'.$reply->id;
	}

	// }}}
	// {{{ protected function getPostRelativeUri()

	protected function getPostRelativeUri(BlorgPost $post)
	{
		$path = $this->app->config->blorg->path.'archive';

		$date = clone $post->post_date;
		$date->convertTZ($this->app->default_time_zone);
		$year = $date->getYear();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];

		return sprintf('%s/%s/%s/%s',
			$path,
			$year,
			$month_name,
			$post->shortname);
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
