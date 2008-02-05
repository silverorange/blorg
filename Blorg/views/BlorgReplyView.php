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

	protected $reply;
	protected $app;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, BlorgReply $reply)
	{
		$this->reply = $reply;
		$this->app = $app;
	}

	// }}}
	// {{{ protected function display()

	public function display()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = 'reply'.$this->reply->id;
		$div_tag->class = 'reply';
		$div_tag->open();

		$this->displayHeader();
		$this->displayBody();

		$div_tag->close();
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader()
	{
		$heading_tag = new SwatHtmlTag('h4');
		$heading_tag->class = 'reply-title';
		$heading_tag->open();

		$this->displayAuthor();
		echo ' ';
		$this->displayPermalink();

		$heading_tag->close();
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor()
	{
		if ($this->reply->author === null) {
			// anonymous author
			if (strlen($this->reply->link) > 0) {
				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->href = $this->reply->link;
				$anchor_tag->class = 'reply-author';
				$anchor_tag->setContent($this->reply->fullname);
				$anchor_tag->display();
			} else {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'reply-author';
				$span_tag->setContent($this->reply->fullname);
				$span_tag->display();
			}
		} else {
			// system author
			//if ($reply->author->show) {
				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->class = 'reply-author system-reply-author';
				$anchor_tag->href =
					$this->getAuthorRelativeUri($this->reply->author);

				$anchor_tag->setContent($this->reply->author->name);
				$anchor_tag->display();
			//} else {
			//	$span_tag = new SwatHtmlTag('span');
			//	$span_tag->class = 'reply-author system-reply-author';
			//	$span_tag->setContent($this->reply->author->name);
			//	$span_tag->display();
			//}
		}
	}

	// }}}
	// {{{ protected function displayPermalink()

	protected function displayPermalink()
	{
		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = $this->getReplyRelativeUri();
		$anchor_tag->open();

		// display machine-readable date in UTC
		$abbr_tag = new SwatHtmlTag('abbr');
		$abbr_tag->class = 'reply-published';
		$abbr_tag->title =
			$this->reply->createdate->getDate(DATE_FORMAT_ISO_EXTENDED);

		// display human-readable date in local time
		$date = clone $this->reply->createdate;
		$date->convertTZ($this->app->default_time_zone);
		$abbr_tag->setContent($date->format(SwatDate::DF_DATE));
		$abbr_tag->display();

		$anchor_tag->close();
	}

	// }}}
	// {{{ protected function displayBody()

	protected function displayBody()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->class = 'reply-content';
		$div_tag->setContent(
			BlorgReply::getBodyTextXhtml($this->reply->bodytext), 'text/xml');

		$div_tag->display();
	}

	// }}}
	// {{{ protected function getReplyRelativeUri()

	protected function getReplyRelativeUri()
	{
		return $this->getPostRelativeUri($this->reply->post).
			'#reply'.$this->reply->id;
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
