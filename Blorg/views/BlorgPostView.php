<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatString.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/views/BlorgView.php';

/**
 * View for Blörg post objects
 *
 * By default, this post view's parts are:
 *
 * - title             - The title of the post. Supports MODE_ALL, MODE_SUMMARY
 *                       and MODE_NONE. Links to the post page by default.
 * - author            - The author of the post. Supports MODE_ALL and
 *                       MODE_NONE. Links to the author details page by default.
 * - permalink         - The permalink (and publish date) of the post. Supports
 *                       MODE_ALL and MODE_NONE. Links to the post page by
 *                       default.
 * - reply_count       - The number of visible replies of this post. Supports
 *                       MODE_ALL and MODE_NONE. Links to the post page with
 *                       a URI fragment of '#replies' appended.
 * - tags              - Tags attached to this post. Supports MODE_ALL,
 *                       MODE_SUMMARY and MODE_NONE. Links to tag archive pages
 *                       by default.
 * - bodytext          - The post bodytext. Supports MODE_ALL, MODE_SUMMARY and
 *                       MODE_NONE. The summary mode displays a condensed,
 *                       ellipsized version of the post bodytext that is no more
 *                       than {@link BlorgPostView::$bodytext_summary_length}
 *                       characters long. Does not link anywhere.
 * - extended_bodytext - The post extended bodytext. Supports MODE_ALL,
 *                       MODE_SUMMARY and MODE_NONE. The summary mode displays
 *                       a “Read More …” link if the post contains extended
 *                       bodytext. Links to the post page by default.
 * - files             - Files attached to this post. Supports MODE_ALL and
 *                       MODE_NONE. Links to the files by default.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostView extends BlorgView
{
	// {{{ protected properties

	/**
	 * Maximum length of bodytext before it is ellipsized in the summary
	 * display mode
	 *
	 * @var integer
	 *
	 * @see setBodytextSummaryLength()
	 */
	protected $bodytext_summary_length = 300;

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->definePart('title');
		$this->definePart('author');
		$this->definePart('permalink');
		$this->definePart('reply_count');
		$this->definePart('tags');
		$this->definePart('files');
		$this->definePart('bodytext');
		$this->definePart('extended_bodytext');
	}

	// }}}
	// {{{ public function setBodytextSummaryLength()

	/**
	 * Sets the maximum length of bodytext before it is ellipsized in the
	 * summary display mode
	 *
	 * @param integer $length the maximum length of bodytext before it is
	 *                         ellipsized in the summary display mode.
	 */
	public function setBodytextSummaryLength($length)
	{
		$this->summary_bodytext_length = intval($length);
	}

	// }}}

	// general display methods
	// {{{ public function display()

	/**
	 * Displays this view for a post
	 *
	 * @param BlorgPost $post
	 */
	public function display(BlorgPost $post)
	{
		if ($this->isVisible($post)) {
			echo '<div class="entry hentry">';

			$this->displayHeader($post);
			$this->displayBody($post);
			$this->displayFooter($post);

			echo '</div>';
		}
	}

	// }}}
	// {{{ protected function displayHeader()

	/**
	 * Displays the title and meta information for a weblog post
	 *
	 * @param BlorgPost $post
	 */
	protected function displayHeader(BlorgPost $post)
	{
		if ($this->isHeaderVisible($post)) {
			$this->displayTitle($post);
			$this->displaySubHeader($post);
		}
	}

	// }}}
	// {{{ protected function displaySubHeader()

	/**
	 * Displays the title and meta information for a weblog post
	 *
	 * @param BlorgPost $post
	 */
	protected function displaySubHeader(BlorgPost $post)
	{
		ob_start();
		$this->displayAuthor($post);
		$author = ob_get_clean();

		ob_start();
		$this->displayPermalink($post);
		$permalink = ob_get_clean();

		ob_start();
		$this->displayReplyCount($post);
		$reply_count = ob_get_clean();

		echo '<div class="entry-subtitle">';

		/*
		 * Reply count is shown if and only if reply_count element is shown AND
		 * the following:
		 * - replies are locked AND there is one or more visible reply OR
		 * - replies are open OR
		 * - replies are moderated.
		 */
		$show_reply_count =
			(strlen($reply_count) > 0 &&
				(($post->reply_status == BlorgPost::REPLY_STATUS_LOCKED &&
					count($post->getVisibleReplies()) > 0) ||
				$post->reply_status == BlorgPost::REPLY_STATUS_OPEN ||
				$post->reply_status == BlorgPost::REPLY_STATUS_MODERATED));

		if (strlen($author) > 0) {
			if ($show_reply_count) {
				printf(Blorg::_('Posted by %s on %s - %s'),
					$author, $permalink, $reply_count);
			} else {
				printf(Blorg::_('Posted by %s on %s'), $author, $permalink);
			}
		} else {
			if ($show_reply_count) {
				printf('%s - %s', $permalink, $reply_count);
			} else {
				echo $permalink;
			}
		}

		echo '</div>';
	}

	// }}}
	// {{{ protected function displayBody()

	protected function displayBody(BlorgPost $post)
	{
		$this->displayBodytext($post);
		$this->displayExtendedBodytext($post);
	}

	// }}}
	// {{{ protected function displayFooter()

	protected function displayFooter(BlorgPost $post)
	{
		$this->displayFiles($post);
	}

	// }}}

	// part display methods
	// {{{ protected function displayTitle()

	protected function displayTitle(BlorgPost $post)
	{
		switch ($this->getMode('title')) {
		case BlorgView::MODE_ALL:
			if (strlen($post->title) > 0) {
				$link = $this->getLink('title');

				$header_tag = new SwatHtmlTag('h3');
				$header_tag->class = 'entry-title';
				$header_tag->id = sprintf('post_%s', $post->shortname);

				if ($link === false) {
					$header_tag->setContent($post->title);
					$header_tag->display();
				} else {
					$header_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($link)) {
						$anchor_tag->href = $link;
					} else {
						$anchor_tag->href = $this->getPostRelativeUri($post);
					}
					$anchor_tag->setContent($post->title);
					$anchor_tag->display();

					$header_tag->close();
				}
			}
			break;
		case BlorgView::MODE_SUMMARY:
			$title = $post->getTitle();
			if (strlen($title) > 0) {
				$link = $this->getLink('title');

				$header_tag = new SwatHtmlTag('h3');
				$header_tag->class = 'entry-title';
				$header_tag->id = sprintf('post_%s', $post->shortname);

				if ($link === false) {
					$header_tag->setContent($title);
					$header_tag->display();
				} else {
					$header_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($link)) {
						$anchor_tag->href = $link;
					} else {
						$anchor_tag->href = $this->getPostRelativeUri($post);
					}
					$anchor_tag->setContent($title);
					$anchor_tag->display();

					$header_tag->close();
				}
			}

			break;
		}
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor(BlorgPost $post)
	{
		if ($this->getMode('author') > BlorgView::MODE_NONE) {
			if ($post->author->show) {
				$link = $this->getLink('author');
				if ($link === false) {
					$span_tag = new SwatHtmlTag('span');
					$span_tag->class = 'vcard author';
					$span_tag->setContent($post->author->name);
					$span_tag->display();
				} else {
					$span_tag = new SwatHtmlTag('span');
					$span_tag->class = 'vcard author';
					$span_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					$anchor_tag->class = 'fn url';
					if (is_string($link)) {
						$anchor_tag->href = $link;
					} else {
						$anchor_tag->href =
							$this->getAuthorRelativeUri($post->author);
					}

					$anchor_tag->setContent($post->author->name);
					$anchor_tag->display();

					$span_tag->close();
				}
			}
		}
	}

	// }}}
	// {{{ protected function displayPermalink()

	/**
	 * Displays the date permalink for a weblog post
	 *
	 * @param BlorgPost $post
	 */
	protected function displayPermalink(BlorgPost $post)
	{
		if ($this->getMode('permalink') > BlorgView::MODE_NONE) {
			$link = $this->getLink('permalink');
			if ($link === false) {
				$permalink_tag = new SwatHtmlTag('span');
			} else {
				$permalink_tag = new SwatHtmlTag('a');
				if ($link === true) {
					$permalink_tag->href = $this->getPostRelativeUri($post);
				} else {
					$permalink_tag->href = $link;
				}
			}
			$permalink_tag->class = 'permalink';
			$permalink_tag->open();

			// display machine-readable date in UTC
			$abbr_tag = new SwatHtmlTag('abbr');
			$abbr_tag->class = 'published';
			$abbr_tag->title =
				$post->publish_date->getDate(DATE_FORMAT_ISO_EXTENDED);

			// display human-readable date in local time
			$date = clone $post->publish_date;
			$date->convertTZ($this->app->default_time_zone);
			$abbr_tag->setContent($date->format(SwatDate::DF_DATE_LONG));
			$abbr_tag->display();

			$permalink_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayReplyCount()

	/**
	 * Displays the number of replies for a weblog post
	 */
	protected function displayReplyCount(BlorgPost $post)
	{
		if ($this->getMode('reply_count') > BlorgView::MODE_NONE) {
			$link = $this->getLink('reply_count');
			$count = count($post->getVisibleReplies());

			if ($link === false) {
				$reply_count_tag = new SwatHtmlTag('span');
			} else {
				$reply_count_tag = new SwatHtmlTag('a');
				if (is_string($link)) {
					$reply_count_tag->href = $link;
				} else {
					$reply_count_tag->href =
						$this->getPostRelativeUri($post).'#replies';
				}
			}

			$reply_count_tag->class = 'reply-count';

			if ($count == 0) {
				$reply_count_tag->setContent(Blorg::_('no replies'));
			} else {
				$locale = SwatI18NLocale::get();
				$reply_count_tag->setContent(sprintf(
					Blorg::ngettext('%s reply', '%s replies', $count),
					$locale->formatNumber($count)));
			}

			$reply_count_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayBodytext()

	protected function displayBodytext(BlorgPost $post)
	{
		switch ($this->getMode('bodytext')) {
		case BlorgView::MODE_ALL:
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'entry-content';
			$div_tag->setContent($post->bodytext, 'text/xml');
			$div_tag->display();
			break;

		case BlorgView::MODE_SUMMARY:
			$bodytext = SwatString::ellipsizeRight(SwatString::condense(
				$post->bodytext), $this->bodytext_summary_length);

			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'entry-content';
			$div_tag->setContent($bodytext, 'text/xml');
			$div_tag->display();
			break;
		}
	}

	// }}}
	// {{{ protected function displayExtendedBodytext()

	protected function displayExtendedBodytext(BlorgPost $post)
	{
		if (strlen($post->extended_bodytext) > 0) {
			switch ($this->getMode('extended_bodytext')) {
			case BlorgView::MODE_ALL:
				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'entry-content entry-content-extended';
				$div_tag->setContent($post->extended_bodytext, 'text/xml');
				$div_tag->display();
				break;

			case BlorgView::MODE_SUMMARY:
				$link = $this->getLink('extended_bodytext');

				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'entry-content entry-content-extended';
				$div_tag->open();

				if ($link === false) {
					$span_tag = new SwatHtmlTag('span');
					$span_tag->setContent(Blorg::_('Read more …'));
					$span_tag->display();
				} else {
					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($link)) {
						$anchor_tag->href = $link;
					} else {
						$anchor_tag->href = $this->getPostRelativeUri($post);
					}
					$anchor_tag->setContent(Blorg::_('Read more …'));
					$anchor_tag->display();
				}

				$div_tag->close();
				break;
			}
		}
	}

	// }}}
	// {{{ protected function displayFiles()

	protected function displayFiles(BlorgPost $post)
	{
		if ($this->getMode('files') > BlorgView::MODE_NONE) {
			$files = $post->getVisibleFiles();
			if (count($files) > 0) {
				$link = $this->getLink('files');
				echo '<ul class="attachments">';
				foreach ($files as $file) {
					$li_tag = new SwatHtmlTag('li');
					$li_tag->open();

					if ($link === false) {
						$span_tag = new SwatHtmlTag('span');
						$span_tag->setContent($file->getDescription());
						$span_tag->display();
					} else {
						$a_tag = new SwatHtmlTag('a');
						$a_tag->href = $file->getRelativeUri(
							$this->app->config->blorg->path,
							$this->path_prefix);

						$a_tag->setContent($file->getDescription());
						$a_tag->display();
					}

					echo ' '.SwatString::byteFormat($file->filesize);

					$li_tag->close();
				}
				echo '</ul>';
			}
		}
	}

	// }}}

	// helper methods
	// {{{ protected function isVisible()

	/**
	 * Gets whether or not this view is visible for a given post
	 *
	 * This takes into account the display modes of this view's elements and
	 * the available content in the specified post object.
	 *
	 * @param BlorgPost $post the post to check visibility against.
	 *
	 * @return boolean true if this view is visible and false if this view is
	 *                 not visible (nothing will be displayed if display() is
	 *                 called).
	 */
	protected function isVisible(BlorgPost $post)
	{
		// make sure we have post content
		$keys = array('reply_count', 'permalink', 'author', 'tags');
		$content_properties = array('title', 'bodytext', 'extended_bodytext');
		foreach ($content_properties as $property) {
			if (strlen($post->$property) > 0) {
				$keys[] = $property;
			}
		}

		$visible = false;
		foreach ($this->getParts() as $part) {
			if (in_array($part, $keys) &&
				$this->getMode($part) > BlorgView::MODE_NONE) {
				$visible = true;
				break;
			}
		}
		return $visible;
	}

	// }}}
	// {{{ protected function isHeaderVisible()

	/**
	 * Gets whether or not the header of this view is visible for a given post
	 *
	 * This takes into account the display modes of this view's elements and
	 * the available content in the specified post object.
	 *
	 * @param BlorgPost $post the post to check visibility against.
	 *
	 * @return boolean true if the header of this view is visible and false if
	 *                 the header of this view is not visible (nothing will be
	 *                 displayed if displayHeader() is called).
	 */
	protected function isHeaderVisible(BlorgPost $post)
	{
		// make sure we have post content for the header
		$keys = array('reply_count', 'permalink', 'author');
		$content_properties = array('title');
		foreach ($content_properties as $property) {
			if (strlen($post->$property) > 0) {
				$keys[] = $property;
			}
		}

		// make sure fields that have content are visible
		$visible = false;
		foreach ($this->getParts() as $part) {
			if (in_array($part, $keys) &&
				$this->getMode($part) > BlorgView::MODE_NONE) {
				$visible = true;
				break;
			}
		}

		return $visible;
	}

	// }}}
	// {{{ protected function isBodyVisible()

	/**
	 * Gets whether or not the body of this view is visible for a given post
	 *
	 * This takes into account the display modes of this view's elements and
	 * the available content in the specified post object.
	 *
	 * @param BlorgPost $post the post to check visibility against.
	 *
	 * @return boolean true if the body of this view is visible and false if
	 *                 the body of this view is not visible (nothing will be
	 *                 displayed if displayBody() is called).
	 */
	protected function isBodyVisible(BlorgPost $post)
	{

		// make sure we have post content for the body
		$keys = array();
		$content_properties = array('bodytext', 'extended_bodytext');
		foreach ($content_properties as $property) {
			if (strlen($post->$property) > 0) {
				$keys[] = $property;
			}
		}

		// make sure fields that have content are visible
		$visible = false;
		foreach ($this->getParts() as $part) {
			if (in_array($part, $keys) &&
				$this->getMode($part) > BlorgView::MODE_NONE) {
				$visible = true;
				break;
			}
		}

		return $visible;
	}

	// }}}
}

?>
