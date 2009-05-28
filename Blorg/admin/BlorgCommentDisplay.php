<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatYUI.php';
require_once 'Site/SiteViewFactory.php';
require_once 'Blorg/Blorg.php';
require_once 'XML/RPCAjax.php';

/**
 * Displays a comment with optional buttons to edit, set published status
 * delete and mark as spam
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentDisplay extends SwatControl
{
	// {{{ protected properties

	/**
	 * @var BlorgComment
	 *
	 * @see BlorgCommentDisplay::setComment()
	 */
	protected $comment;

	/**
	 * @var SiteApplication
	 *
	 * @see BlorgCommentDisplay::setApplication()
	 */
	protected $app;

	/**
	 * @var BlorgCommentView
	 *
	 * @see BlorgCommentDisplay::getView()
	 */
	protected static $view;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->html_head_entry_set->addEntrySet(
			XML_RPCAjax::getHtmlHeadEntrySet());

		$yui = new SwatYUI(array('dom', 'event', 'animation'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addStyleSheet(
			'packages/blorg/admin/styles/blorg-comment-display.css',
			Blorg::PACKAGE_ID);

		$this->addJavaScript(
			'packages/blorg/admin/javascript/blorg-comment-display.js',
			Blorg::PACKAGE_ID);
	}

	// }}}
	// {{{ public function setComment()

	public function setComment(BlorgComment $comment)
	{
		$this->comment = $comment;
	}

	// }}}
	// {{{ public function setApplication()

	public function setApplication(SiteApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->comment === null)
			return;

		if ($this->app === null)
			return;

		parent::display();

		$container_div = new SwatHtmlTag('div');
		$container_div->class = $this->getCSSClassString();
		$container_div->id = $this->id;
		$container_div->open();

		$animation_container = new SwatHtmlTag('div');
		$animation_container->class = 'blorg-comment-display-content';
		$animation_container->open();

		$this->displayControls();
		$this->displayHeader();

		$view = $this->getView();
		$view->display($this->comment);

		$animation_container->close();
		$container_div->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function displayControls()

	protected function displayControls()
	{
		$controls_div = new SwatHtmlTag('div');
		$controls_div->id = $this->id.'_controls';
		$controls_div->class = 'blorg-comment-display-controls';
		$controls_div->open();
		$controls_div->close();
	}

	// }}}
	// {{{ protected function displayHeader()

	protected function displayHeader()
	{
		$header_div = new SwatHtmlTag('div');
		$header_div->class = 'blorg-comment-display-header';
		$header_div->open();

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = sprintf('Post/Details?id=%s',
			$this->comment->post->id);

		$anchor_tag->setContent($this->comment->post->getTitle());
		echo sprintf(Blorg::_('Comment on %s'), $anchor_tag);

		$this->displayStatusSpan();

		echo ' - ';

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = sprintf('Comment/Edit?id=%s',
			$this->comment->id);

		$anchor_tag->setContent(Blorg::_('Edit'));
		$anchor_tag->display();

		$header_div->close();
	}

	// }}}
	// {{{ protected function displayStatusSpan()

	protected function displayStatusSpan()
	{
		$status_span = new SwatHtmlTag('span');
		$status_span->id = $this->id.'_status';
		$status_spam->class = 'blorg-comment-display-status';
		$status_span->open();

		if ($this->comment->spam) {
			echo ' - ', Blorg::_('Spam');
		} else {
			switch ($this->comment->status) {
			case SiteComment::STATUS_UNPUBLISHED:
				echo ' - ', Blorg::_('Unpublished');
				break;

			case SiteComment::STATUS_PENDING:
				echo ' - ', Blorg::_('Pending');
				break;
			}
		}

		$status_span->close();
	}

	// }}}
	// {{{ protected function getView()

	protected function getView()
	{
		if (self::$view === null && $this->app !== null) {
			self::$view = SiteViewFactory::get($this->app, 'comment');
			self::$view->setPartMode('bodytext',  SiteView::MODE_SUMMARY);
			self::$view->setPartMode('permalink', SiteView::MODE_ALL, false);
			self::$view->setPartMode('author',    SiteView::MODE_ALL, false);
			self::$view->setPartMode('link',      SiteView::MODE_ALL, false);
		}
		return self::$view;
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this comment display
	 *
	 * @return array the array of CSS classes that are applied to this comment
	 *                display.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('blorg-comment-display');
		$classes = array_merge($classes, parent::getCSSClassNames());
		$classes[] = $this->getVisibilityCssClassName();
		return $classes;
	}

	// }}}
	// {{{ protected function getVisibilityCssClassName()

	protected function getVisibilityCssClassName()
	{
		if ($this->comment->spam) {
			$class = 'blorg-comment-red';
		} else {
			switch ($this->comment->status) {
			case SiteComment::STATUS_UNPUBLISHED:
				$class = 'blorg-comment-red';
				break;

			case SiteComment::STATUS_PENDING:
				$class = 'blorg-comment-yellow';
				break;

			case SiteComment::STATUS_PUBLISHED:
			default:
				$class = 'blorg-comment-green';
				break;
			}
		}

		return $class;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript required by this control
	 *
	 * @return string the inline JavaScript required by this control.
	 */
	protected function getInlineJavaScript()
	{
		static $shown = false;

		if (!$shown) {
			$javascript = $this->getInlineJavaScriptTranslations();
			$shown = true;
		} else {
			$javascript = '';
		}

		$spam = ($this->comment->spam) ? 'true' : 'false';
		$status = $this->comment->status;

		$javascript.= sprintf(
			"var %s_obj = new BlorgCommentDisplay('%s', %s, %s);",
			$this->id, $this->id, $status, $spam);

		return $javascript;
	}

	// }}}
	// {{{ protected function getInlineJavaScriptTranslations()

	/**
	 * Gets translatable string resources for the JavaScript object for
	 * this widget
	 *
	 * @return string translatable JavaScript string resources for this widget.
	 */
	protected function getInlineJavaScriptTranslations()
	{
		$approve_text = SwatString::quoteJavaScriptString(Blorg::_('Approve'));
		$deny_text    = SwatString::quoteJavaScriptString(Blorg::_('Deny'));
		$publish_text = SwatString::quoteJavaScriptString(Blorg::_('Publish'));
		$spam_text    = SwatString::quoteJavaScriptString(Blorg::_('Spam'));
		$delete_text  = SwatString::quoteJavaScriptString(Blorg::_('Delete'));
		$cancel_text  = SwatString::quoteJavaScriptString(Blorg::_('Cancel'));

		$not_spam_text = SwatString::quoteJavaScriptString(
			Blorg::_('Not Spam'));

		$unpublish_text = SwatString::quoteJavaScriptString(
			Blorg::_('Unpublish'));

		$status_spam_text = SwatString::quoteJavaScriptString(
			Blorg::_('Spam'));

		$status_pending_text = SwatString::quoteJavaScriptString(
			Blorg::_('Pending'));

		$status_unpublished_text  = SwatString::quoteJavaScriptString(
			Blorg::_('Unpublished'));

		$delete_confirmation_text  = SwatString::quoteJavaScriptString(
			Blorg::_('Delete comment?'));

		return
			"BlorgCommentDisplay.approve_text   = {$approve_text};\n".
			"BlorgCommentDisplay.deny_text      = {$deny_text};\n".
			"BlorgCommentDisplay.publish_text   = {$publish_text};\n".
			"BlorgCommentDisplay.unpublish_text = {$unpublish_text};\n".
			"BlorgCommentDisplay.spam_text      = {$spam_text};\n".
			"BlorgCommentDisplay.not_spam_text  = {$not_spam_text};\n".
			"BlorgCommentDisplay.delete_text    = {$delete_text};\n".
			"BlorgCommentDisplay.cancel_text    = {$cancel_text};\n\n".
			"BlorgCommentDisplay.status_spam_text        = ".
				"{$status_spam_text};\n".
			"BlorgCommentDisplay.status_pending_text     = ".
				"{$status_pending_text};\n".
			"BlorgCommentDisplay.status_unpublished_text = ".
				"{$status_unpublished_text};\n\n".
			"BlorgCommentDisplay.delete_confirmation_text = ".
				"{$delete_confirmation_text};\n\n";
	}

	// }}}
}

?>
