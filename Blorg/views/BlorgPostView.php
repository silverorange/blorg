<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatString.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Site/views/SiteView.php';
require_once 'Site/SiteCommentStatus.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/views/BlorgAuthorView.php';
require_once 'Blorg/BlorgPageFactory.php';

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
 * - comment_count     - The number of visible comments of this post. Supports
 *                       MODE_ALL and MODE_NONE. Links to the post page with
 *                       a URI fragment of '#comments' appended.
 * - tags              - Tags on this post. Supports MODE_ALL and MODE_NONE.
 *                       Links to the tag pages for tags by default.
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
 * @copyright 2008-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostView extends SiteView
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

	/**
	 * Formats used for displayed dates.
	 *
	 * @param integer $format an integer format id.
	 *
	 * @see setDateForamt()
	 */
	protected $date_format = SwatDate::DF_DATE_LONG;

	/**
	 * Length of bodytext in visible characters to be considered a microblog
	 * post
	 *
	 * Default length of 140 borrowed from a popular microblogging service.
	 *
	 * @var integer
	 */
	protected $microblog_length = 140;

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->definePart('title');
		$this->definePart('author');
		$this->definePart('permalink');
		$this->definePart('comment_count');
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
	// {{{ public function setDateFormat()

	/**
	 * Sets the date format used for displayed dates.
	 *
	 * @param integer $format the format to use for display.
	 */
	public function setDateForamt($format)
	{
		$this->date_format = $format;
	}

	// }}}

	// general display methods
	// {{{ public function display()

	/**
	 * Displays this view for a post
	 *
	 * @param BlorgPost $post
	 */
	public function display($post)
	{
		if (!($post instanceof BlorgPost)) {
			throw new InvalidArgumentException(sprintf('The view "%s" can '.
				'only display BlorgPost objects.',
				get_class($this)));
		}

		if ($this->isVisible($post)) {
			$div_tag = new SwatHtmlTag('div');
			$div_tag->id = $this->getXhtmlId($post);
			$div_tag->class = 'entry hentry entry-author-'.
				$post->author->shortname;

			// special class for title-less posts.
			if ($post->title == '')
				$div_tag->class.= ' entry-no-title';

			$div_tag->open();

			if ($post->title == '' &&
				$this->getMode('title') !== SiteView::MODE_SUMMARY) {
				// When displaying a full view of a title-less post, put the
				// sub-header at the bottom.
				$this->displayBody($post);
				$this->displayFooter($post);
				$this->displaySubHeader($post);
			} else {
				// Normal view
				$this->displayHeader($post);
				$this->displayBody($post);
				$this->displayFooter($post);
			}

			$div_tag->close();
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
		$this->displayCommentCount($post);
		$comment_count = ob_get_clean();

		ob_start();
		$this->displayTags($post);
		$tags = ob_get_clean();

		echo '<div class="entry-subtitle">';

		/*
		 * Comment count is shown if and only if comment_count element is shown
		 * AND the following:
		 * - comments are locked AND there is one or more visible comment OR
		 * - comments are open OR
		 * - comments are moderated.
		 */
		$show_comment_count =
			($comment_count != '' &&
				(($post->comment_status == SiteCommentStatus::LOCKED &&
					$post->getVisibleCommentCount() > 0) ||
				$post->comment_status == SiteCommentStatus::OPEN ||
				$post->comment_status == SiteCommentStatus::MODERATED));

		if (      $author != '' && $tags != '' &&  $show_comment_count) {
			printf(Blorg::_('Posted by %s on %s in %s - %s'),
				$author, $permalink, $tags, $comment_count);

		} elseif ($author != '' && $tags != '' && !$show_comment_count) {
			printf(Blorg::_('Posted by %s on %s in %s'),
				$author, $permalink, $tags);

		} elseif ($author != '' && $tags == '' &&  $show_comment_count) {
			printf(Blorg::_('Posted by %s on %s - %s'),
				$author, $permalink, $comment_count);

		} elseif ($author != '' && $tags == '' && !$show_comment_count) {
			printf(Blorg::_('Posted by %s on %s'),
				$author, $permalink);

		} elseif ($author == '' && $tags != '' &&  $show_comment_count) {
			printf(Blorg::_('%s in %s - %s'),
				$permalink, $tags, $comment_count);

		} elseif ($author == '' && $tags != '' && !$show_comment_count) {
			printf(Blorg::_('%s in %s'),
				$permalink, $tags);

		} elseif ($author == '' && $tags == '' &&  $show_comment_count) {
			printf(Blorg::_('%s - %s'),
				$permalink, $comment_count);

		} else {
			echo $permalink;
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
	// {{{ protected function getRelativeUri()

	protected function getRelativeUri(BlorgPost $post)
	{
		return Blorg::getPostRelativeUri($this->app, $post);
	}

	// }}}
	// {{{ protected function getTagRelativeUri()

	protected function getTagRelativeUri(BlorgTag $tag)
	{
		return Blorg::getTagRelativeUri($this->app, $tag);
	}

	// }}}
	// {{{ protected function getAuthorRelativeUri()

	protected function getAuthorRelativeUri(BlorgAuthor $author)
	{
		return Blorg::getAuthorRelativeUri($this->app, $author);
	}

	// }}}

	// part display methods
	// {{{ protected function displayTitle()

	protected function displayTitle(BlorgPost $post)
	{
		$title = '';

		switch ($this->getMode('title')) {
		case SiteView::MODE_ALL:
			$title = $post->title;
			break;
		case SiteView::MODE_SUMMARY:
			$title = $post->getTitle();
			break;
		}

		if ($title != '') {
			$link = $this->getLink('title');

			$header_tag = new SwatHtmlTag('h3');
			$header_tag->class = 'entry-title';

			if ($link === false) {
				$header_tag->setContent($title);
				$header_tag->display();
			} else {
				$header_tag->open();

				$anchor_tag = new SwatHtmlTag('a');
				if (is_string($link)) {
					$anchor_tag->href = $link;
				} else {
					$anchor_tag->href = $this->getRelativeUri($post);
				}
				$anchor_tag->setContent($title);
				$anchor_tag->display();

				$header_tag->close();
			}
		}
	}

	// }}}
	// {{{ protected function displayAuthor()

	protected function displayAuthor(BlorgPost $post)
	{
		if ($this->getMode('author') > SiteView::MODE_NONE) {
			$link = $this->getLink('author');

			// Don't link to non-visible authors but still show their name on
			// the post.
			if ($post->author->visible && $link !== false) {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'vcard author';
				$span_tag->open();

				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->class = 'fn url';
				$anchor_tag->title = sprintf(Blorg::_('View details for %s'),
					$post->author->name);

				if (is_string($link)) {
					$anchor_tag->href = $link;
				} else {
					$anchor_tag->href = $this->getAuthorRelativeUri(
						$post->author);
				}

				$anchor_tag->setContent($post->author->name);
				$anchor_tag->display();

				$span_tag->close();
			} else {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'vcard author';
				$span_tag->setContent($post->author->name);
				$span_tag->display();
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
		if ($this->getMode('permalink') > SiteView::MODE_NONE) {
			$link = $this->getLink('permalink');
			if ($link === false) {
				$permalink_tag = new SwatHtmlTag('span');
			} else {
				$permalink_tag = new SwatHtmlTag('a');
				if ($link === true) {
					$permalink_tag->href = $this->getRelativeUri($post);
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
			$abbr_tag->setContent($date->format($this->getDateFormat($post)));
			$abbr_tag->display();

			$permalink_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayCommentCount()

	/**
	 * Displays the number of comments for a weblog post
	 */
	protected function displayCommentCount(BlorgPost $post)
	{
		if ($this->getMode('comment_count') > SiteView::MODE_NONE) {
			$link = $this->getLink('comment_count');
			$count = $post->getVisibleCommentCount();

			if ($link === false) {
				$comment_count_tag = new SwatHtmlTag('span');
			} else {
				$comment_count_tag = new SwatHtmlTag('a');
				if (is_string($link)) {
					$comment_count_tag->href = $link;
				} else {
					$comment_count_tag->href =
						$this->getRelativeUri($post).'#comments';
				}

				switch ($post->comment_status) {
				case SiteCommentStatus::LOCKED:
					$comment_count_tag->title = sprintf(
						Blorg::_('View comments for %s'),
						$post->getTitle());

					break;
				case SiteCommentStatus::OPEN:
				case SiteCommentStatus::MODERATED:
					$comment_count_tag->title = sprintf(
						Blorg::_('Comment on %s'),
						$post->getTitle());

					break;
				}
			}

			$comment_count_tag->class = 'comment-count';

			if ($count == 0) {
				$comment_count_tag->setContent(Blorg::_('leave a comment'));
			} else {
				$locale = SwatI18NLocale::get();
				$comment_count_tag->setContent(sprintf(
					Blorg::ngettext('%s comment', '%s comments', $count),
					$locale->formatNumber($count)));
			}

			$comment_count_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayBodytext()

	protected function displayBodytext(BlorgPost $post)
	{
		switch ($this->getMode('bodytext')) {
		case SiteView::MODE_ALL:
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'entry-content';
			$div_tag->setContent($post->bodytext, 'text/xml');
			$div_tag->display();

			if ($this->getMode('extended_bodytext') == SiteView::MODE_ALL && (
				$post->extended_bodytext != '' ||
				$this->app->config->blorg->ad_post_comments == ''))
				Blorg::displayAd($this->app, 'post_content');

			break;

		case SiteView::MODE_SUMMARY:
			$bodytext = null;
			$link = $this->getLink('bodytext');

			// don't summarize microblogs
			if ($post->title == '') {
				$stripped_bodytext = strip_tags($post->bodytext);
				$stripped_bodytext = html_entity_decode($stripped_bodytext,
					ENT_COMPAT, 'UTF-8');

				if (strlen($stripped_bodytext) <= $this->microblog_length) {
					$bodytext = $post->bodytext;
				}
			}

			if ($bodytext === null) {
				$flag = false;
				$bodytext = SwatString::ellipsizeRight(SwatString::condense(
					$post->bodytext), $this->bodytext_summary_length, ' …',
					$flag);

				if ($flag && $link !== false) {
					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($link)) {
						$anchor_tag->href = $link;
					} else {
						$anchor_tag->href = $this->getRelativeUri($post);
					}
					$anchor_tag->setContent(Blorg::_('read more »'));
					$bodytext.= ' '.$anchor_tag;
				}
			}

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
		if ($post->extended_bodytext != '') {
			switch ($this->getMode('extended_bodytext')) {
			case SiteView::MODE_ALL:
				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'entry-content entry-content-extended';
				$div_tag->setContent($post->extended_bodytext, 'text/xml');
				$div_tag->display();
				break;

			case SiteView::MODE_SUMMARY:
				$link = $this->getLink('extended_bodytext');

				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'entry-read-more';
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
						$anchor_tag->href = $this->getRelativeUri($post);
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
	// {{{ protected function displayTags()

	protected function displayTags(BlorgPost $post)
	{
		if ($this->getMode('tags') > SiteView::MODE_NONE) {
			$tags = $post->getTags();
			$num_of_tags = count($tags);
			$counter = 1;
			if ($num_of_tags > 0) {
				$link = $this->getLink('tags');
				$tags_span_tag = new SwatHtmlTag('span');
				$tags_span_tag->class = 'entry-tags';
				$tags_span_tag->open();

				foreach ($tags as $tag) {
					if ($link === false) {
						$span_tag = new SwatHtmlTag('span');
						$span_tag->setContent($tag->title);
						$span_tag->display();
					} else {
						$a_tag = new SwatHtmlTag('a');
						$a_tag->rel = 'tag';
						$a_tag->href = $this->getTagRelativeUri($tag);

						$a_tag->setContent($tag->title);
						$a_tag->display();
					}

					if ($counter < $num_of_tags) {
						echo ', ';
					}

					$counter++;
				}

				$tags_span_tag->close();
			}
		}
	}

	// }}}
	// {{{ protected function displayFiles()

	protected function displayFiles(BlorgPost $post)
	{
		if ($this->getMode('files') > SiteView::MODE_NONE) {
			$files = $post->getVisibleFiles();
			if (count($files) > 0) {
				$link = $this->getLink('files');
				echo '<ul class="attachments">';
				foreach ($files as $file) {
					$li_tag = new SwatHtmlTag('li');
					$li_tag->class = 'type-'.$file->mime_type;
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
	// {{{ protected function getXhtmlId()

	protected function getXhtmlId(BlorgPost $post)
	{
		$date = clone $post->publish_date;
		$date->convertTZ($this->app->default_time_zone);
		$date = $date->format('%Y%m%d');
		return sprintf('post_%s_%s',
			$post->shortname,
			$date);
	}

	// }}}
	// {{{ protected function getDateFormat()

	/**
	 * Get a date format for displays the date part of the permalink
	 *
	 * @param BlorgPost $post
	 */
	protected function getDateFormat(BlorgPost $post)
	{
		return SwatDate::DF_DATE_LONG;
	}

	// }}}
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
		$keys = array('comment_count', 'permalink', 'author', 'tags');
		$content_properties = array('title', 'bodytext', 'extended_bodytext');
		foreach ($content_properties as $property) {
			if ($post->$property != '') {
				$keys[] = $property;
			}
		}

		$visible = false;
		foreach ($this->getParts() as $part) {
			if (in_array($part, $keys) &&
				$this->getMode($part) > SiteView::MODE_NONE) {
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
		$keys = array('comment_count', 'permalink', 'author');
		$content_properties = array('title');
		foreach ($content_properties as $property) {
			if ($post->$property != '') {
				$keys[] = $property;
			}
		}

		// make sure fields that have content are visible
		$visible = false;
		foreach ($this->getParts() as $part) {
			if (in_array($part, $keys) &&
				$this->getMode($part) > SiteView::MODE_NONE) {
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
			if ($post->$property != '') {
				$keys[] = $property;
			}
		}

		// make sure fields that have content are visible
		$visible = false;
		foreach ($this->getParts() as $part) {
			if (in_array($part, $keys) &&
				$this->getMode($part) > SiteView::MODE_NONE) {
				$visible = true;
				break;
			}
		}

		return $visible;
	}

	// }}}
}

?>
