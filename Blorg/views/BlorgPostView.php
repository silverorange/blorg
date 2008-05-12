<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatString.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/dataobjects/BlorgAuthor.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/BlorgPageFactory.php';

/**
 * Base class for Blörg post views
 *
 * Usage pattern is as follows:
 * 1. instantiate a view object,
 * 2. set what you want to be shown and how you want it to be shown using the
 *    show*() methods on the new view
 * 3. display one or more posts using the view by calling the display() method.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostView
{
	// {{{ class constants

	/**
	 * Do not show the element. All elements should support this mode.
	 */
	const SHOW_NONE    = 1;

	/**
	 * Show a shortened or summarized version of the element. Not all elements
	 * support this mode. If it is unsupported, it is treated like
	 * {@link BlorgPostView::SHOW_ALL}.
	 */
	const SHOW_SUMMARY = 2;

	/**
	 * Show all of the element. This is the default mode for most elements.
	 */
	const SHOW_ALL     = 3;

	// }}}
	// {{{ protected properties

	/**
	 * The application to which this view belongs
	 *
	 * @var SiteApplication
	 *
	 * @see BlorgPostView::__construct()
	 */
	protected $app;

	/**
	 * Path prefix for relatively referenced paths
	 *
	 * @var string
	 */
	protected $path_prefix = '';

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
	 * Data structure containing the display mode and link mode for all post
	 * view elements
	 *
	 * The array keys are element names and the array values are two-element
	 * arrays containing a 'mode' element and a 'link' element for the display
	 * mode and link mode respectively.
	 *
	 * @var array
	 *
	 * @see BlorgPostView::show()
	 */
	protected $show = array(
		'title'             => array('mode' => self::SHOW_ALL,     'link' => true),
		'author'            => array('mode' => self::SHOW_ALL,     'link' => true),
		'permalink'         => array('mode' => self::SHOW_ALL,     'link' => true),
		'reply_count'       => array('mode' => self::SHOW_ALL,     'link' => true),
		'tags'              => array('mode' => self::SHOW_ALL,     'link' => true),
		'files'             => array('mode' => self::SHOW_ALL,     'link' => true),
		'bodytext'          => array('mode' => self::SHOW_ALL,     'link' => true),
		'extended_bodytext' => array('mode' => self::SHOW_SUMMARY, 'link' => true),
	);

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new post view
	 *
	 * By default, this post view's element modes are:
	 *
	 * - title:             show all and link
	 * - author:            show all and link
	 * - permalink:         show all and link
	 * - reply_count:       show all and link
	 * - tags:              show all and link
	 * - bodytext:          show all
	 * - extended_bodytext: show summary and link
	 *
	 * @param SiteApplication $app the application to which this view belongs.
	 *
	 * @see BlorgPostView::show()
	 */
	public function __construct(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function setPathPrefix()

	/**
	 * Sets the prefix to use for relatively referenced paths such as file
	 * download links.
	 *
	 * @param string A relative path prefix to access the web root such as
	 *               "../"
	 */
	public function setPathPrefix($path_prefix)
	{
		$this->path_prefix = $path_prefix;
	}

	// }}}

	// show methods
	// {{{ public function show()

	/**
	 * Sets the display mode of one or more elements of this post view
	 *
	 * This is a convenience method that can be used when extensively
	 * customizing a view. This method can also be used to display saved
	 * view settings.
	 *
	 * @param array $show a data structure containing the display mode and
	 *                     link mode for one or more elements of this post view.
	 *                     The array keys are element names and the array values
	 *                     are two-element arrays containing a 'mode' element
	 *                     and a 'link' element for the display mode and link
	 *                     mode respectively. All keys in the arrays are
	 *                     optional.
	 */
	public function show(array $show)
	{
		foreach ($show as $key => $value) {

			if (is_array($value)) {
				$mode = (array_key_exists('mode', $value)) ?
					$value['mode'] : self::SHOW_NONE;

				$link = (array_key_exists('link', $value)) ?
					$value['link'] : false;
			} else {
				$mode = self::SHOW_NONE;
				$link = false;
			}

			switch ($key) {
			case 'title':
				$this->showTitle($mode, $link);
				break;
			case 'author':
				$this->showAuthor($mode, $link);
				break;
			case 'permalink':
				$this->showPermalink($mode, $link);
				break;
			case 'reply_count':
				$this->showReplyCount($mode, $link);
				break;
			case 'tags':
				$this->showTags($mode, $link);
				break;
			case 'files':
				$this->showFiles($mode, $link);
				break;
			case 'bodytext':
				$this->showBodytext($mode, $link);
				break;
			case 'extended_bodytext':
				$this->showExtendedBodytext($mode, $link);
				break;
			}

		}
	}

	// }}}
	// {{{ public function showTitle()

	/**
	 * Sets the display mode of the title element of this post view
	 *
	 * @param integer $mode the display mode of the title element of this post
	 *                       view. The title element supports
	 *                       {@link BlorgPostView::SHOW_NONE},
	 *                       {@link BlorgPostView::SHOW_SUMMARY} and
	 *                       {@link BlorgPostView::SHOW_ALL}. If an invalid
	 *                       mode is specified, BlorgPostView::SHOW_NONE is
	 *                       used.
	 * @param boolean|string $link if false, the title will not be linked. If
	 *                              true, the title will be linked to the
	 *                              permalink of the post. If a string, the
	 *                              title will be linked to the specified
	 *                              string.
	 */
	public function showTitle($mode = self::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['title'] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function showAuthor()

	/**
	 * Sets the display mode of the author element of this post view
	 *
	 * @param integer $mode the display mode of the author element of this post
	 *                       view. The author element supports
	 *                       {@link BlorgPostView::SHOW_NONE} and
	 *                       {@link BlorgPostView::SHOW_ALL}. If an invalid
	 *                       mode is specified, BlorgPostView::SHOW_NONE is
	 *                       used.
	 * @param boolean|string $link if false, the author will not be linked. If
	 *                              true, the author will be linked to the
	 *                              author bio page (if such a page exists). If
	 *                              a string, the author will be linked to the
	 *                              specified string.
	 */
	public function showAuthor($mode = self::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['author'] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function showPermalink()

	/**
	 * Sets the display mode of the permalink element of this post view
	 *
	 * @param integer $mode the display mode of the permalink element of this
	 *                       post view. The permalink element supports
	 *                       {@link BlorgPostView::SHOW_NONE} and
	 *                       {@link BlorgPostView::SHOW_ALL}. If an invalid
	 *                       mode is specified, BlorgPostView::SHOW_NONE is
	 *                       used.
	 * @param boolean|string $link if false, the permalink will not be linked.
	 *                              If true, the permalink will be linked to
	 *                              the permalink of the post. If a string, the
	 *                              permalink will be linked to the specified
	 *                              string.
	 */
	public function showPermalink($mode = self::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['permalink'] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function showReplyCount()

	/**
	 * Sets the display mode of the reply_count element of this post view
	 *
	 * @param integer $mode the display mode of the reply_count element of this
	 *                       post view. The reply_count element supports
	 *                       {@link BlorgPostView::SHOW_NONE} and
	 *                       {@link BlorgPostView::SHOW_ALL}. If an invalid
	 *                       mode is specified, BlorgPostView::SHOW_NONE is
	 *                       used.
	 * @param boolean|string $link if false, the reply_count will not be linked.
	 *                              If true, the reply_count will be linked to
	 *                              the permalink of the post with a URI
	 *                              fragment of '#replies'. If a string, the
	 *                              reply_count will be linked to the specified
	 *                              string.
	 */
	public function showReplyCount($mode = self::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['reply_count'] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function showTags()

	/**
	 * Sets the display mode of the tags element of this post view
	 *
	 * @param integer $mode the display mode of the tags element of this
	 *                       post view. The tags element supports
	 *                       {@link BlorgPostView::SHOW_NONE},
	 *                       {@link BlorgPostView::SHOW_SUMMARY} and
	 *                       {@link BlorgPostView::SHOW_ALL}. If an invalid
	 *                       mode is specified, BlorgPostView::SHOW_NONE is
	 *                       used. The summary mode presents an inline list
	 *                       delimited by commas. The all mode presents an
	 *                       unordered XHTML list.
	 * @param boolean $link if true, the tags are linked to tag pages. If false,
	 *                       the tags are not linked.
	 */
	public function showTags($mode = self::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['tags'] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function showFiles()

	/**
	 * Sets the display mode of the files element of this post view
	 *
	 * @param integer $mode the display mode of the files element of this
	 *                       post view. The files element supports
	 *                       {@link BlorgPostView::SHOW_NONE},
	 *                       {@link BlorgPostView::SHOW_SUMMARY} and
	 *                       {@link BlorgPostView::SHOW_ALL}. If an invalid
	 *                       mode is specified, BlorgPostView::SHOW_NONE is
	 *                       used.
	 * @param boolean|string $link the bodytext is never linked. Setting a
	 *                              value here has no effect. This parameter is
	 *                              here to match the API of the other show
	 *                              methods.
	 */
	public function showFiles($mode = self::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['files'] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function showBodytext()

	/**
	 * Sets the display mode of the bodytext element of this post view
	 *
	 * @param integer $mode the display mode of the bodytext element of this
	 *                       post view. The bodytext element supports
	 *                       {@link BlorgPostView::SHOW_NONE},
	 *                       {@link BlorgPostView::SHOW_SUMMARY} and
	 *                       {@link BlorgPostView::SHOW_ALL}. If an invalid
	 *                       mode is specified, BlorgPostView::SHOW_NONE is
	 *                       used. The summary mode presents a condensed,
	 *                       ellipsized version of the post bodytext that is
	 *                       no more than 300 characters long.
	 * @param boolean|string $link the bodytext is never linked. Setting a
	 *                              value here has no effect. This parameter is
	 *                              here to match the API of the other show
	 *                              methods.
	 */
	public function showBodytext($mode = self::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['bodytext'] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function showExtendedBodytext()

	/**
	 * Sets the display mode of the extended_bodytext element of this post view
	 *
	 * @param integer $mode the display mode of the extended_bodytext element of
	 *                       this post view. The extended_bodytext element
	 *                       supports {@link BlorgPostView::SHOW_NONE},
	 *                       {@link BlorgPostView::SHOW_SUMMARY} and
	 *                       {@link BlorgPostView::SHOW_ALL}. If an invalid
	 *                       mode is specified, BlorgPostView::SHOW_NONE is
	 *                       used. The summary mode presents a 'Read More ...'
	 *                       link if the post has extended bodytext.
	 * @param boolean|string $link the extended_bodytext is only linked when
	 *                              using BlorgPostView::SHOW_SUMMARY.
	 *                              If false, the extended_bodytext will not be
	 *                              linked. If true, the extended_bodytext will
	 *                              be linked to the permalink of the post. If
	 *                              a string, the extended_bodytext will be
	 *                              linked to the specified string.
	 */
	public function showExtendedBodytext($mode = self::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['extended_bodytext'] = array(
			'mode' => $mode,
			'link' => $link);
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

	// element display methods
	// {{{ protected function displayTitle()

	protected function displayTitle(BlorgPost $post)
	{
		$show = $this->show['title'];

		switch ($show['mode']) {
		case self::SHOW_ALL:
			if (strlen($post->title) > 0) {
				$header_tag = new SwatHtmlTag('h3');
				$header_tag->class = 'entry-title';
				$header_tag->id = sprintf('post_%s', $post->shortname);

				if ($show['link'] === false) {
					$header_tag->setContent($post->title);
					$header_tag->display();
				} else {
					$header_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($show['link'])) {
						$anchor_tag->href = $show['link'];
					} else {
						$anchor_tag->href = $this->getPostRelativeUri($post);
					}
					$anchor_tag->setContent($post->title);
					$anchor_tag->display();

					$header_tag->close();
				}
			}
			break;
		case self::SHOW_SUMMARY:
			$title = $post->getTitle();
			if (strlen($title) > 0) {
				$header_tag = new SwatHtmlTag('h3');
				$header_tag->class = 'entry-title';
				$header_tag->id = sprintf('post_%s', $post->shortname);

				if ($show['link'] === false) {
					$header_tag->setContent($title);
					$header_tag->display();
				} else {
					$header_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($show['link'])) {
						$anchor_tag->href = $show['link'];
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
		$show = $this->show['author'];
		if ($show['mode'] > self::SHOW_NONE) {
			if ($post->author->show) {
				$span_tag = new SwatHtmlTag('span');
				$span_tag->class = 'vcard author';
				$span_tag->open();

				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->class = 'fn url';
				$anchor_tag->href =
					$this->getAuthorRelativeUri($post->author);

				$anchor_tag->setContent($post->author->name);
				$anchor_tag->display();

				$span_tag->close();
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
		$show = $this->show['permalink'];
		if ($show['mode'] > self::SHOW_NONE) {
			if ($show['link'] === false) {
				$permalink_tag = new SwatHtmlTag('span');
			} else {
				$permalink_tag = new SwatHtmlTag('a');
				if ($show['link'] === true) {
					$permalink_tag->href = $this->getPostRelativeUri($post);
				} else {
					$permalink_tag->href = $show['link'];
				}
			}
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
		$show = $this->show['reply_count'];
		if ($show['mode'] > self::SHOW_NONE) {
			$count = count($post->getVisibleReplies());

			if ($show['link'] === false) {
				$reply_count_tag = new SwatHtmlTag('span');
			} else {
				$reply_count_tag = new SwatHtmlTag('a');
				if (is_string($show['link'])) {
					$reply_count_tag->href = $show['link'];
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
		$show = $this->show['bodytext'];
		switch ($show['mode']) {
		case self::SHOW_ALL:
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'entry-content';
			$div_tag->setContent($post->bodytext, 'text/xml');
			$div_tag->display();
			break;

		case self::SHOW_SUMMARY:
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
		$show = $this->show['extended_bodytext'];
		if (strlen($post->extended_bodytext) > 0) {
			switch ($show['mode']) {
			case self::SHOW_ALL:
				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'entry-content entry-content-extended';
				$div_tag->setContent($post->extended_bodytext, 'text/xml');
				$div_tag->display();
				break;

			case self::SHOW_SUMMARY:
				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'entry-content entry-content-extended';
				$div_tag->open();

				if ($show['link'] === false) {
					$span_tag = new SwatHtmlTag('span');
					$span_tag->setContent(Blorg::_('Read more …'));
					$span_tag->display();
				} else {
					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($show['link'])) {
						$anchor_tag->href = $$show['link'];
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
		$show = $this->show['files'];
		if ($show['mode'] > self::SHOW_NONE) {
			$files = $post->getVisibleFiles();
			if (count($files) > 0) {
				echo '<ul class="attachments">';
				foreach ($files as $file) {
					$li_tag = new SwatHtmlTag('li');
					$li_tag->open();
					$a_tag = new SwatHtmlTag('a');
					$a_tag->href = $file->getRelativeUri($this->path_prefix);
					$a_tag->setContent($file->getDescription());
					$a_tag->display();
					echo ' '.SwatString::byteFormat($file->filesize);
					$li_tag->close();
				}
				echo '</ul>';
			}
		}
	}

	// }}}

	// helper methods
	// {{{ protected function getPostRelativeUri()

	protected function getPostRelativeUri(BlorgPost $post)
	{
		$path = $this->app->config->blorg->path.'archive';

		$date = clone $post->publish_date;
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

	protected function getAuthorRelativeUri(BlorgAuthor $author)
	{
		$path = $this->app->config->blorg->path.'author';
		return sprintf('%s/%s',
			$path,
			$author->shortname);
	}

	// }}}
	// {{{ protected function getMode()

	/**
	 * Ensures the specified mode is a valid mode and makes it valid if
	 * invalid
	 *
	 * If an invalid mode is specified, {@link BlorgPostView::SHOW_NONE} is
	 * returned. Otherwise, the specified mode is returned.
	 *
	 * @param integer $mode the mode.
	 *
	 * @return integer a valid display mode.
	 */
	protected function getMode($mode)
	{
		$valid_modes = array(
			self::SHOW_ALL,
			self::SHOW_SUMMARY,
			self::SHOW_NONE,
		);

		if (!in_array($mode, $valid_modes)) {
			$mode = self::SHOW_NONE;
		}

		return $mode;
	}

	// }}}
	// {{{ protected function getLink()

	/**
	 * Ensures the specified link is valid for the link parameter of a show
	 * method and makes it valid if invalid
	 *
	 * If an invalid link is specified, false is returned. Otherwise the
	 * specified link is returned.
	 *
	 * @param boolean|string $link the link.
	 *
	 * @return boolean|string a valid link.
	 */
	protected function getLink($link)
	{
		if (!is_bool($link) && !is_string($link)) {
			$link = false;
		}
		return $link;
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
		$keys = array('reply_count', 'permalink', 'author', 'tags');
		$content_properties = array('title', 'bodytext', 'extended_bodytext');
		foreach ($content_properties as $property) {
			if (strlen($post->$property) > 0) {
				$keys[] = $property;
			}
		}

		$visible = false;
		foreach ($this->show as $key => $show) {
			if (in_array($key, $keys) && $show['mode'] > self::SHOW_NONE) {
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
		foreach ($this->show as $key => $show) {
			if (in_array($key, $keys) && $show['mode'] > self::SHOW_NONE) {
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
		foreach ($this->show as $key => $show) {
			if (in_array($key, $keys) && $show['mode'] > self::SHOW_NONE) {
				$visible = true;
				break;
			}
		}

		return $visible;
	}

	// }}}
}

?>
