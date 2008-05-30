<?php

require_once 'Swat/SwatDate.php';
require_once 'Swat/SwatString.php';
require_once 'SwatI18N/SwatI18NLocale.php';
require_once 'Blorg/views/BlorgView.php';
require_once 'Blorg/dataobjects/BlorgAuthor.php';
require_once 'Blorg/Blorg.php';
require_once 'Blorg/BlorgPageFactory.php';

/**
 * View for Blörg author objects
 *
 * By default, this author view's parts are:
 *
 * - name        - The name of the author. Supports MODE_ALL, MODE_SUMMARY and
 *                 MODE_NONE. If summary mode is used, the author name is
 *                 displayed in a h4 instead of a h3. Links to the author
 *                 details page by default.
 * - email       - The email of the author. Supports MODE_ALL and MODE_NONE.
 *                 Links to the author email by default.
 * - description - The description of the author. Supports MODE_ALL and
 *                 MODE_NONE. Links to the author details page by default.
 * - bodytext    - The bodytext of the author. Supports MODE_ALL, MODE_SUMMARY
 *                 and MODE_NONE. Does not link anywhere.
 * - post_count  - The number of visible posts by the author. Supports MODE_ALL,
 *                 MODE_SUMMARY and MODE_NONE. If summary mode is used, the
 *                 post count is only displayed if greater than zero and is
 *                 displayed in parenthesis. Links to the author details page
 *                 with a URI fragment of '#posts' appended.
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

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->definePart('name');
		$this->definePart('email');
		$this->definePart('description');
		$this->definePart('bodytext');
		$this->definePart('post_count');
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
		$parts = array();

		ob_start();
		$this->displayEmail($author);
		$email = ob_get_clean();

		if (strlen($email) > 0) {
			$parts[] = $email;
		}

		ob_start();
		$this->displayPostCount($author);
		$post_count = ob_get_clean();

		if (strlen($post_count) > 0) {
			$parts[] = $post_count;
		}

		echo '<div class="author-subtitle">';
		echo implode(' - ', $parts);
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

	// part display methods
	// {{{ protected function displayName()

	protected function displayName(BlorgAuthor $author)
	{
		if ($this->getMode('name') > BlorgView::MODE_NONE) {
			$link = $this->getLink('name');
			if (strlen($author->name) > 0) {
				if ($this->getMode('name') > BlorgView::MODE_SUMMARY)
					$header_tag = new SwatHtmlTag('h3');
				else
					$header_tag = new SwatHtmlTag('h4');

				$header_tag->class = 'author-name';
				if (strlen($author->shortname) > 0) {
					$header_tag->id = sprintf('author_%s', $author->shortname);
				}

				if ($link === false) {
					$header_tag->setContent($author->name);
					$header_tag->display();
				} else {
					$header_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($link)) {
						$anchor_tag->href = $link;
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
		if ($this->getMode('email') > BlorgView::MODE_NONE) {
			$link = $this->getLink('email');
			if (strlen($author->email) > 0) {
				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'author-email';

				if ($link === false) {
					$div_tag->setContent($author->email);
					$div_tag->display();
				} else {
					$div_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($link)) {
						$anchor_tag->href = $link;
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
	// {{{ protected function displayPostCount()

	/**
	 * Displays the number of posts for a weblog author
	 */
	protected function displayPostCount(BlorgAuthor$author)
	{
		switch ($this->getMode('post_count')) {
		case BlorgView::MODE_ALL:
			$link = $this->getLink('post_count');
			$count = count($author->getVisiblePosts());

			if ($link === false) {
				$post_count_tag = new SwatHtmlTag('span');
			} else {
				$post_count_tag = new SwatHtmlTag('a');
				if (is_string($link)) {
					$post_count_tag->href = $link;
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
			break;

		case BlorgView::MODE_SUMMARY:
			$count = count($author->getVisiblePosts());
			if ($count > 0) {
				$link = $this->getLink('post_count');

				if ($link === false) {
					$post_count_tag = new SwatHtmlTag('span');
				} else {
					$post_count_tag = new SwatHtmlTag('a');
					if (is_string($link)) {
						$post_count_tag->href = $link;
					} else {
						$post_count_tag->href =
							$this->getAuthorRelativeUri($author).'#posts';
					}
				}

				$post_count_tag->class = 'author-post-count';

				$locale = SwatI18NLocale::get();
				$post_count_tag->setContent(sprintf(
					Blorg::ngettext('(%s post)', '(%s posts)', $count),
					$locale->formatNumber($count)));

				$post_count_tag->display();
			}
			break;
		}
	}

	// }}}
	// {{{ protected function displayBodytext()

	protected function displayBodytext(BlorgAuthor $author)
	{
		switch ($this->getMode('bodytext')) {
		case BlorgView::MODE_ALL:
			$div_tag = new SwatHtmlTag('div');
			$div_tag->class = 'author-content';
			$div_tag->setContent($author->bodytext, 'text/xml');
			$div_tag->display();
			break;

		case BlorgView::MODE_SUMMARY:
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
		if ($this->getMode('description') > BlorgView::MODE_NONE) {
			$link = $this->getLink('description');
			if (strlen($author->description) > 0) {
				$div_tag = new SwatHtmlTag('div');
				$div_tag->class = 'author-description';
				$div_tag->setContent($author->description, 'text/xml');
				$div_tag->display();

				if ($link !== false) {
					$div_tag = new SwatHtmlTag('div');
					$div_tag->class = 'author-description-link';
					$div_tag->open();

					$anchor_tag = new SwatHtmlTag('a');
					if (is_string($link)) {
						$anchor_tag->href = $link;
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
	 * This takes into account the display modes of this view's parts and the
	 * available content in the specified author object.
	 *
	 * @param BlorgAuthor $author the author to check visibility against.
	 *
	 * @return boolean true if this view is visible and false if this view is
	 *                 not visible (nothing will be displayed if display() is
	 *                 called).
	 */
	protected function isVisible(BlorgAuthor $author)
	{
		// parts where visibility is not dependent on dataobject content
		$keys = array('post_count');

		// parts that are visible depending on dataobject content
		$content_properties = array('name', 'email', 'description', 'bodytext');
		foreach ($content_properties as $property) {
			if (strlen($author->$property) > 0) {
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
	 * Gets whether or not the header of this view is visible for a given
	 * author
	 *
	 * This takes into account the display modes of this view's parts and the
	 * available content in the specified author object.
	 *
	 * @param BlorgAuthor $author the author to check visibility against.
	 *
	 * @return boolean true if the header of this view is visible and false if
	 *                 the header of this view is not visible (nothing will be
	 *                 displayed if displayHeader() is called).
	 */
	protected function isHeaderVisible(BlorgAuthor $post)
	{
		// parts where visibility is not dependent on dataobject content
		$keys = array('post_count');

		// parts that are visible depending on dataobject content
		$content_properties = array('name', 'email');
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
	 * Gets whether or not the body of this view is visible for a given author
	 *
	 * This takes into account the display modes of this view's parts and the
	 * available content in the specified author object.
	 *
	 * @param BlorgAuthor $author the author to check visibility against.
	 *
	 * @return boolean true if the body of this view is visible and false if
	 *                 the body of this view is not visible (nothing will be
	 *                 displayed if displayBody() is called).
	 */
	protected function isBodyVisible(BlorgAuthor $author)
	{
		// parts where visibility is not dependent on dataobject content
		$keys = array();

		// parts that are visible depending on dataobject content
		$content_properties = array('bodytext', 'description');
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
