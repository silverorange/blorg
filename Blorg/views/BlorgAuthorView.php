<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatString.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Blorg/views/BlorgView.php';
require_once 'Blorg/dataobjects/BlorgAuthor.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/BlorgPageFactory.php';

/**
 * Base class for Blörg author views
 *
 * By default, this author view's element modes are:
 *
 * - name:        show all and link
 * - email:       show all and link
 * - description: show all and link
 * - bodytext:    show none
 * - post_count:  show all
 *
 * Usage pattern is as follows:
 * 1. instantiate a view object,
 * 2. set what you want to be shown and how you want it to be shown using the
 *    show*() methods on the new view
 * 3. display one or more authors using the view by calling the display()
 *    method.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAuthorView extends BlorgView
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
	 * Data structure containing the display mode and link mode for all author
	 * view elements
	 *
	 * The array keys are element names and the array values are two-element
	 * arrays containing a 'mode' element and a 'link' element for the display
	 * mode and link mode respectively.
	 *
	 * @var array
	 *
	 * @see BlorgAuthorView::show()
	 */
	protected $show = array(
		'name'        => array('mode' => BlorgView::SHOW_ALL,  'link' => true),
		'email'       => array('mode' => BlorgView::SHOW_ALL,  'link' => true),
		'description' => array('mode' => BlorgView::SHOW_ALL,  'link' => true),
		'bodytext'    => array('mode' => BlorgView::SHOW_NONE, 'link' => true),
		'post_count'  => array('mode' => BlorgView::SHOW_ALL,  'link' => true),
	);

	// }}}

	// show methods
	// {{{ public function show()

	/**
	 * Sets the display mode of one or more elements of this author view
	 *
	 * This is a convenience method that can be used when extensively
	 * customizing a view. This method can also be used to display saved
	 * view settings.
	 *
	 * @param array $show a data structure containing the display mode and
	 *                     link mode for one or more elements of this author
	 *                     view. The array keys are element names and the array
	 *                     values are two-element arrays containing a 'mode'
	 *                     element and a 'link' element for the display mode
	 *                     and link mode respectively. All keys in the arrays
	 *                     are optional.
	 */
	public function show(array $show)
	{
		foreach ($show as $key => $value) {

			if (is_array($value)) {
				$mode = (array_key_exists('mode', $value)) ?
					$value['mode'] : BlorgView::SHOW_NONE;

				$link = (array_key_exists('link', $value)) ?
					$value['link'] : false;
			} else {
				$mode = BlorgView::SHOW_NONE;
				$link = false;
			}

			switch ($key) {
			case 'name':
				$this->showName($mode, $link);
				break;
			case 'email':
				$this->showEmail($mode, $link);
				break;
			case 'description':
				$this->showDescription($mode, $link);
				break;
			case 'bodytext':
				$this->showBodytext($mode, $link);
				break;
			case 'post_count':
				$this->showPostCount($mode, $link);
				break;
			}

		}
	}

	// }}}
	// {{{ public function showName()

	/**
	 * Sets the display mode of the name element of this author view
	 *
	 * @param integer $mode the display mode of the name element of this author
	 *                       view. The name element supports
	 *                       {@link BlorgView::SHOW_NONE} and
	 *                       {@link BlorgView::SHOW_ALL}. If an invalid mode is
	 *                       specified, BlorgView::SHOW_NONE is used.
	 * @param boolean|string $link if false, the name will not be linked. If
	 *                              true, the name will be linked to the
	 *                              corresponding author page. If a string,
	 *                              the name will be linked to the specified
	 *                              string.
	 */
	public function showName($mode = BlorgView::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['name'] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function showEmail()

	/**
	 * Sets the display mode of the email element of this author view
	 *
	 * @param integer $mode the display mode of the email element of this
	 *                       author view. The email element supports
	 *                       {@link BlorgView::SHOW_NONE} and
	 *                       {@link BlorgView::SHOW_ALL}. If an invalid mode is
	 *                       specified, BlorgView::SHOW_NONE is used.
	 */
	public function showEmail($mode = BlorgView::SHOW_ALL)
	{
		$mode = $this->getMode($mode);
		$this->show['email'] = array('mode' => $mode);
	}

	// }}}
	// {{{ public function showBodytext()

	/**
	 * Sets the display mode of the bodytext element of this author view
	 *
	 * @param integer $mode the display mode of the bodytext element of this
	 *                       author view. The bodytext element supports
	 *                       {@link BlorgView::SHOW_NONE},
	 *                       {@link BlorgView::SHOW_SUMMARY} and
	 *                       {@link BlorgView::SHOW_ALL}. If an invalid mode is
	 *                       specified, BlorgView::SHOW_NONE is used. The
	 *                       summary mode presents a condensed, ellipsized
	 *                       version of the author bodytext that is no more
	 *                       than 300 characters long.
	 * @param boolean|string $link the bodytext is never linked. Setting a
	 *                              value here has no effect. This parameter is
	 *                              here to match the API of the other show
	 *                              methods.
	 */
	public function showBodytext($mode = BlorgView::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['bodytext'] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function showDescription()

	/**
	 * Sets the display mode of the description element of this author view
	 *
	 * @param integer $mode the display mode of the description element of this
	 *                       author view. The description element supports
	 *                       {@link BlorgView::SHOW_NONE} and
	 *                       {@link BlorgView::SHOW_ALL}. If an invalid mode is
	 *                       specified, BlorgView::SHOW_NONE is used.
	 * @param boolean|string $link if false, the description will not be linked.
	 *                              If true, the description will be linked to
	 *                              the author's details page. If a string, the
	 *                              description will be linked to the specified
	 *                              string.
	 */
	public function showDescription($mode = BlorgView::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['description'] = array('mode' => $mode, 'link' => $link);
	}

	// }}}
	// {{{ public function showPostCount()

	/**
	 * Sets the display mode of the post_count element of this author view
	 *
	 * @param integer $mode the display mode of the post_count element of this
	 *                       author view. The post_count element supports
	 *                       {@link BlorgView::SHOW_NONE} and
	 *                       {@link BlorgView::SHOW_ALL}. If an invalid mode
	 *                       is specified, BlorgView::SHOW_NONE is used.
	 * @param boolean|string $link if false, the post_count will not be linked.
	 *                              If true, the post_count will be linked to
	 *                              the details page of the author with a URI
	 *                              fragment of '#posts'. If a string, the
	 *                              post_count will be linked to the specified
	 *                              string.
	 */
	public function showPostCount($mode = BlorgView::SHOW_ALL, $link = true)
	{
		$mode = $this->getMode($mode);
		$link = $this->getLink($link);
		$this->show['post_count'] = array('mode' => $mode, 'link' => $link);
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
	 * Displays this view for an author
	 *
	 * @param BlorgAuthor $author
	 */
	public function display(BlorgAuthor $author)
	{
		if ($this->isVisible($author)) {
			echo '<div class="author">';

			$this->displayHeader($author);
			$this->displayBody($author);

			echo '</div>';
		}
	}

	// }}}
	// {{{ protected function displayHeader()

	/**
	 * Displays the name and meta information for an author
	 *
	 * @param BlorgAuthor $author
	 */
	protected function displayHeader(BlorgAuthor $author)
	{
		if ($this->isHeaderVisible($author)) {
			$this->displayName($author);
			$this->displaySubHeader($author);
		}
	}

	// }}}
	// {{{ protected function displaySubHeader()

	/**
	 * Displays the meta information for a weblog author
	 *
	 * @param BlorgAuthor $author
	 */
	protected function displaySubHeader(BlorgAuthor $author)
	{
		$elements = array();

		ob_start();
		$this->displayEmail($author);
		$email = ob_get_clean();

		if (strlen($email) > 0) {
			$elements[] = $email;
		}

		ob_start();
		$this->displayPostCount($author);
		$post_count = ob_get_clean();

		if (strlen($post_count) > 0) {
			$elements[] = $post_count;
		}

		echo '<div class="author-subtitle">';
		echo implode(' - ', $elements);
		echo '</div>';
	}

	// }}}
	// {{{ protected function displayBody()

	protected function displayBody(BlorgAuthor $author)
	{
		$this->displayDescription($author);
		$this->displayBodytext($author);
	}

	// }}}

	// element display methods
	// {{{ protected function displayName()

	protected function displayName(BlorgAuthor $author)
	{
		$show = $this->show['name'];
		if ($show['mode'] > BlorgView::SHOW_NONE) {
			if (strlen($author->name) > 0) {
				$header_tag = new SwatHtmlTag('h3');
				$header_tag->class = 'author-name';
				if (strlen($author->shortname) > 0) {
					$header_tag->id = sprintf('author_%s', $author->shortname);
				}

				if ($show['link'] === false) {
					$header_tag->setContent($author->name);
					$header_tag->display();
				} else {
					$header_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($show['link'])) {
						$anchor_tag->href = $show['link'];
					} else {
						$anchor_tag->href =
							$this->getAuthorRelativeUri($author);
					}
					$anchor_tag->setContent($author->name);
					$anchor_tag->display();

					$header_tag->close();
				}
			}
		}
	}

	// }}}
	// {{{ protected function displayEmail()

	protected function displayEmail(BlorgAuthor $author)
	{
		$show = $this->show['email'];
		if ($show['mode'] > BlorgView::SHOW_NONE) {
			if (strlen($author->email) > 0) {
				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'author-email';

				if ($show['link'] === false) {
					$div_tag->setContent($author->email);
					$div_tag->display();
				} else {
					$div_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($show['link'])) {
						$anchor_tag->href = $show['link'];
					} else {
						$anchor_tag->href = 'mailto:'.$author->email;
					}
					$anchor_tag->setContent($author->email);
					$anchor_tag->display();

					$div_tag->close();
				}
			}
		}
	}

	// }}}
	// {{{ protected function displayReplyCount()

	/**
	 * Displays the number of posts for a weblog author
	 */
	protected function displayPostCount(BlorgAuthor$author)
	{
		$show = $this->show['post_count'];
		if ($show['mode'] > BlorgView::SHOW_NONE) {
			$count = count($author->getVisiblePosts());

			if ($show['link'] === false) {
				$post_count_tag = new SwatHtmlTag('span');
			} else {
				$post_count_tag = new SwatHtmlTag('a');
				if (is_string($show['link'])) {
					$post_count_tag->href = $show['link'];
				} else {
					$post_count_tag->href =
						$this->getAuthorRelativeUri($author).'#posts';
				}
			}

			$post_count_tag->class = 'post-count';

			if ($count == 0) {
				$post_count_tag->setContent(Blorg::_('no posts'));
			} else {
				$locale = SwatI18NLocale::get();
				$post_count_tag->setContent(sprintf(
					Blorg::ngettext('%s post', '%s posts', $count),
					$locale->formatNumber($count)));
			}

			$post_count_tag->display();
		}
	}

	// }}}
	// {{{ protected function displayBodytext()

	protected function displayBodytext(BlorgAuthor $author)
	{
		$show = $this->show['bodytext'];
		switch ($show['mode']) {
		case BlorgView::SHOW_ALL:
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'author-content';
			$div_tag->setContent($author->bodytext, 'text/xml');
			$div_tag->display();
			break;

		case BlorgView::SHOW_SUMMARY:
			$bodytext = SwatString::ellipsizeRight(SwatString::condense(
				$author->bodytext), $this->bodytext_summary_length);

			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'author-content';
			$div_tag->setContent($bodytext, 'text/xml');
			$div_tag->display();
			break;
		}
	}

	// }}}
	// {{{ protected function displayDescription()

	protected function displayDescription(BlorgAuthor $author)
	{
		$show = $this->show['description'];
		if ($show['mode'] > BlorgView::SHOW_NONE) {
			if (strlen($author->description) > 0) {
				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'author-description';
				$div_tag->setContent($author->description, 'text/xml');
				$div_tag->display();

				if ($show['link'] !== false) {
					$div_tag = new SwatHtmlTag('div');
					$div_tag->class = 'author-description-link';
					$div_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($show['link'])) {
						$anchor_tag->href = $show['link'];
					} else {
						$anchor_tag->href =
							$this->getAuthorRelativeUri($author);
					}
					$anchor_tag->setContent(Blorg::_('Read more …'));
					$anchor_tag->display();

					$div_tag->close();
				}
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
	 * the available content in the specified author object.
	 *
	 * @param BlorgAuthor $author the author to check visibility against.
	 *
	 * @return boolean true if this view is visible and false if this view is
	 *                 not visible (nothing will be displayed if display() is
	 *                 called).
	 */
	protected function isVisible(BlorgAuthor $author)
	{
		// always visible elements
		$keys = array('post_count');

		// elements that are visible depending on dataobject content
		$content_properties = array('name', 'email', 'description', 'bodytext');
		foreach ($content_properties as $property) {
			if (strlen($author->$property) > 0) {
				$keys[] = $property;
			}
		}

		$visible = false;
		foreach ($this->show as $key => $show) {
			if (in_array($key, $keys) && $show['mode'] > BlorgView::SHOW_NONE) {
				$visible = true;
				break;
			}
		}
		return $visible;
	}

	// }}}
	// {{{ protected function isHeaderVisible()

	/**
	 * Gets whether or not the header of this view is visible for a given
	 * author
	 *
	 * This takes into account the display modes of this view's elements and
	 * the available content in the specified authir object.
	 *
	 * @param BlorgAuthor $author the author to check visibility against.
	 *
	 * @return boolean true if the header of this view is visible and false if
	 *                 the header of this view is not visible (nothing will be
	 *                 displayed if displayHeader() is called).
	 */
	protected function isHeaderVisible(BlorgAuthor $post)
	{
		// always visible elements
		$keys = array('post_count');

		// elements that are visible depending on dataobject content
		$content_properties = array('name', 'email');
		foreach ($content_properties as $property) {
			if (strlen($post->$property) > 0) {
				$keys[] = $property;
			}
		}

		// make sure fields that have content are visible
		$visible = false;
		foreach ($this->show as $key => $show) {
			if (in_array($key, $keys) && $show['mode'] > BlorgView::SHOW_NONE) {
				$visible = true;
				break;
			}
		}

		return $visible;
	}

	// }}}
	// {{{ protected function isBodyVisible()

	/**
	 * Gets whether or not the body of this view is visible for a given author
	 *
	 * This takes into account the display modes of this view's elements and
	 * the available content in the specified author object.
	 *
	 * @param BlorgAuthor $author the author to check visibility against.
	 *
	 * @return boolean true if the body of this view is visible and false if
	 *                 the body of this view is not visible (nothing will be
	 *                 displayed if displayBody() is called).
	 */
	protected function isBodyVisible(BlorgAuthor $author)
	{
		// always visible elements
		$keys = array();

		// elements that are visible depending on dataobject content
		$content_properties = array('bodytext', 'description');
		foreach ($content_properties as $property) {
			if (strlen($post->$property) > 0) {
				$keys[] = $property;
			}
		}

		// make sure fields that have content are visible
		$visible = false;
		foreach ($this->show as $key => $show) {
			if (in_array($key, $keys) && $show['mode'] > BlorgView::SHOW_NONE) {
				$visible = true;
				break;
			}
		}

		return $visible;
	}

	// }}}
}

?>
