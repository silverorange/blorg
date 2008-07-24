<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatUI.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/BlorgViewFactory.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/dataobjects/BlorgComment.php';
require_once 'Services/Akismet.php';
require_once 'NateGoSearch/NateGoSearch.php';

/**
 * Post page for Blörg
 *
 * Loads and displays a post and handles adding comments to a post.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostPage extends SitePage
{
	// {{{ class constants

	const THANK_YOU_ID = 'thank-you';

	// }}}
	// {{{ protected properties

	/**
	 * @var BlorgPost
	 */
	protected $post;

	/**
	 * @var BlorgComment
	 */
	protected $comment;

	/**
	 * @var SwatUI
	 */
	protected $comment_ui;

	/**
	 * @var string
	 */
	protected $comment_ui_xml = 'Blorg/pages/comment-edit.xml';

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null,
		array $arguments = array())
	{
		parent::__construct($app, $layout, $arguments);

		$year = $this->getArgument('year');
		$month_name = $this->getArgument('month_name');
		$shortname = $this->getArgument('shortname');

		$this->initPost($year, $month_name, $shortname);
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'year' => array(0, null),
			'month_name' => array(1, null),
			'shortname' => array(2, null),
		);
	}

	// }}}
	// {{{ protected function initPost()

	protected function initPost($year, $month_name, $shortname)
	{
		if (!array_key_exists($month_name, BlorgPageFactory::$months_by_name)) {
			throw new SiteNotFoundException('Post not found.');
		}

		// Date parsed from URL is in locale time.
		$date = new SwatDate();
		$date->setTZ($this->app->default_time_zone);
		$date->setYear($year);
		$date->setMonth(BlorgPageFactory::$months_by_name[$month_name]);
		$date->setDay(1);
		$date->setHour(0);
		$date->setMinute(0);
		$date->setSecond(0);

		$class_name = SwatDBClassMap::get('BlorgPost');
		$this->post = new $class_name();
		$this->post->setDatabase($this->app->db);
		if (!$this->post->loadByDateAndShortname($date, $shortname,
			$this->app->getInstance())) {
			throw new SiteNotFoundException('Post not found.');
		}

		if (!$this->post->enabled) {
			throw new SiteNotFoundException('Post not found.');
		}
	}

	// }}}

	// init phase
	// {{{ public function init()

	public function init()
	{
		parent::init();
		$this->initCommentUi();
	}

	// }}}
	// {{{ protected function initCommentUi()

	protected function initCommentUi()
	{
		$this->comment_ui = new SwatUI();
		$this->comment_ui->loadFromXml($this->comment_ui_xml);
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();
		$this->processCommentUi();
	}

	// }}}
	// {{{ protected function processCommentUi()

	protected function processCommentUi()
	{
		$form = $this->comment_ui->getWidget('comment_edit_form');

		// wrap form processing in try/catch to catch bad input from spambots
		try {
			$form->process();
		} catch (SwatInvalidSerializedDataException $e) {
			$this->app->replacePage('httperror');
			$this->app->getPage()->setStatus(400);
			return;
		}

		$comment_status = $this->post->comment_status;
		if (($comment_status == BlorgPost::COMMENT_STATUS_OPEN ||
			$comment_status == BlorgPost::COMMENT_STATUS_MODERATED) &&
			$form->isProcessed() && !$form->hasMessage()) {

			$this->processComment();

			if ($this->comment_ui->getWidget('post_button')->hasBeenClicked()) {
				$this->saveComment();

				switch ($this->post->comment_status) {
				case BlorgPost::COMMENT_STATUS_OPEN:
					$uri = $this->source.'?'.self::THANK_YOU_ID.
						'#comment'.$this->comment->id;

					break;

				case BlorgPost::COMMENT_STATUS_MODERATED:
					$uri = $this->source.'?comment-thank-you'.
						'#submit_comment';

					break;

				default:
					$uri = $this->source;
					break;
				}

				$this->app->relocate($uri);
			}
		}
	}

	// }}}
	// {{{ protected function processComment()

	protected function processComment()
	{
		$now = new SwatDate();
		$now->toUTC();

		$fullname   = $this->comment_ui->getWidget('fullname');
		$link       = $this->comment_ui->getWidget('link');
		$email      = $this->comment_ui->getWidget('email');
		$bodytext   = $this->comment_ui->getWidget('bodytext');

		if (isset($_SERVER['REMOTE_ADDR'])) {
			$ip_address = substr($_SERVER['REMOTE_ADDR'], 0, 15);
		} else {
			$ip_address = null;
		}

		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);
		} else {
			$user_agent = null;
		}

		$class_name = SwatDBClassMap::get('BlorgComment');
		$this->comment = new $class_name();

		$this->comment->fullname   = $fullname->value;
		$this->comment->link       = $link->value;
		$this->comment->email      = $email->value;
		$this->comment->bodytext   = $bodytext->value;
		$this->comment->createdate = $now;
		$this->comment->ip_address = $ip_address;
		$this->comment->user_agent = $user_agent;

		switch ($this->post->comment_status) {
		case BlorgPost::COMMENT_STATUS_OPEN:
			$this->comment->status = BlorgComment::STATUS_PUBLISHED;
			break;

		case BlorgPost::COMMENT_STATUS_MODERATED:
			$this->comment->status = BlorgComment::STATUS_PENDING;
			break;
		}

		$this->comment->post = $this->post;
	}

	// }}}
	// {{{ protected function saveComment()

	protected function saveComment()
	{
		if ($this->comment_ui->getWidget('remember_me')->value) {
			$this->saveCommentCookie();
		} else {
			$this->deleteCommentCookie();
		}

		$this->comment->spam = $this->isCommentSpam($this->comment);

		$this->post->comments->add($this->comment);
		$this->post->save();
		$this->addToSearchQueue();
	}

	// }}}
	// {{{ protected function saveCommentCookie()

	protected function saveCommentCookie()
	{
		$fullname = $this->comment_ui->getWidget('fullname')->value;
		$link     = $this->comment_ui->getWidget('link')->value;
		$email    = $this->comment_ui->getWidget('email')->value;

		$value = array(
			'fullname' => $fullname,
			'link'     => $link,
			'email'    => $email,
		);

		$this->app->cookie->setCookie('comment_credentials', $value);
	}

	// }}}
	// {{{ protected function deleteCommentCookie()

	protected function deleteCommentCookie()
	{
		$this->app->cookie->removeCookie('comment_credentials');
	}

	// }}}
	// {{{ protected function isCommentSpam()

	protected function isCommentSpam(BlorgComment $comment)
	{
		$is_spam = false;

		if ($this->app->config->blorg->akismet_key !== null) {
			$uri = $this->app->getBaseHref().$this->app->config->blorg->path;

			$date = clone $this->post->publish_date;
			$date->convertTZ($this->app->default_time_zone);
			$permalink = sprintf('%sarchive/%s/%s/%s',
				$uri,
				$date->getYear(),
				BlorgPageFactory::$month_names[$date->getMonth()],
				$this->post->shortname);

			try {
				$akismet = new Services_Akismet($uri,
					$this->app->config->blorg->akismet_key);

				$akismet_comment = new Services_Akismet_Comment();
				$akismet_comment->setAuthor($comment->fullname);
				$akismet_comment->setAuthorEmail($comment->email);
				$akismet_comment->setAuthorUri($comment->link);
				$akismet_comment->setContent($comment->bodytext);
				$akismet_comment->setPostPermalink($permalink);

				$is_spam = $akismet->isSpam($akismet_comment);
			} catch (Exception $e) {
			}
		}

		return $is_spam;
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		$this->addPostToSearchQueue();
		$this->addCommentToSearchQueue();
	}

	// }}}
	// {{{ protected function addPostToSearchQueue()

	protected function addPostToSearchQueue()
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'post');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->post->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->post->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function addCommentToSearchQueue()

	protected function addCommentToSearchQueue()
	{
		$type = NateGoSearch::getDocumentType($this->app->db, 'comment');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->comment->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->comment->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildTitle();
		$this->buildNavBar();
		$this->buildCommentUi();
		$this->buildAtomLinks();

		$this->layout->startCapture('content');
		Blorg::displayAd($this->app, 'top');
		$this->displayPost();
		$this->displayComments();
		$this->displayCommentUi();
		Blorg::displayAd($this->app, 'bottom');
		$this->layout->endCapture();
	}

	// }}}
	// {{{ protected function buildTitle()

	protected function buildTitle()
	{
		$this->layout->data->html_title = $this->post->getTitle();

		$this->layout->data->meta_description = SwatString::minimizeEntities(
			SwatString::ellipsizeRight(
				SwatString::condense($this->post->bodytext), 300));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$path = $this->app->config->blorg->path.'archive';
		$this->layout->navbar->createEntry(Blorg::_('Archive'), $path);

		$date = clone $this->post->publish_date;
		$date->convertTZ($this->app->default_time_zone);

		$path.= '/'.$date->getYear();
		$this->layout->navbar->createEntry($date->getYear(), $path);

		$month_title = $date->getMonthName();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];
		$path.= '/'.$month_name;
		$this->layout->navbar->createEntry($month_title, $path);

		$path.= '/'.$this->post->shortname;
		$this->layout->navbar->createEntry($this->post->title, $path);
	}

	// }}}
	// {{{ protected function buildCommentUi()

	protected function buildCommentUi()
	{
		$ui              = $this->comment_ui;
		$form            = $ui->getWidget('comment_edit_form');
		$frame           = $ui->getWidget('comment_edit_frame');
		$frame->subtitle = $this->post->getTitle();
		$show_thank_you  = array_key_exists(self::THANK_YOU_ID, $_GET);

		switch ($this->post->comment_status) {
		case BlorgPost::COMMENT_STATUS_OPEN:
		case BlorgPost::COMMENT_STATUS_MODERATED:
			$form->action = $this->source.'#submit_comment';
			if (isset($this->app->cookie->comment_credentials)) {
				$values = $this->app->cookie->comment_credentials;
				$ui->getWidget('fullname')->value    = $values['fullname'];
				$ui->getWidget('link')->value        = $values['link'];
				$ui->getWidget('email')->value       = $values['email'];
				$ui->getWidget('remember_me')->value = true;
			}
			break;

		case BlorgPost::COMMENT_STATUS_LOCKED:
			$form->visible = false;
			$message = new SwatMessage(Blorg::_('Comments are Locked'));
			$message->secondary_content =
				Blorg::_('No new comments may be posted for this article.');

			$ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);

			break;

		case BlorgPost::COMMENT_STATUS_CLOSED:
			$ui->getRoot()->visible = false;
			break;
		}

		if ($show_thank_you) {
			switch ($this->post->comment_status) {
			case BlorgPost::COMMENT_STATUS_OPEN:
				$message = new SwatMessage(
					Blorg::_('Your comment has been published.'));

				$this->comment_ui->getWidget('message_display')->add($message,
					SwatMessageDisplay::DISMISS_OFF);

				break;

			case BlorgPost::COMMENT_STATUS_MODERATED:
				$message = new SwatMessage(
					Blorg::_('Your comment has been submitted.'));

				$message->secondary_content =
					Blorg::_('Your comment will be published after being '.
						'approved by the site moderator.');

				$this->comment_ui->getWidget('message_display')->add($message,
					SwatMessageDisplay::DISMISS_OFF);

				break;
			}
		}

		$this->buildCommentPreview();
	}

	// }}}
	// {{{ protected function buildCommentPreview()

	protected function buildCommentPreview()
	{
		if ($this->comment instanceof BlorgComment &&
			$this->comment_ui->getWidget('preview_button')->hasBeenClicked()) {

			$button_tag = new SwatHtmlTag('input');
			$button_tag->type = 'submit';
			$button_tag->name = 'post_button';
			$button_tag->value = Blorg::_('Post');

			$message = new SwatMessage(Blorg::_(
				'Your comment has not yet been published.'));

			$message->secondary_content = sprintf(Blorg::_(
				'Review your comment and press the <em>Post</em> button when '.
				'it’s ready to publish. %s'),
				$button_tag);

			$message->content_type = 'text/xml';

			$message_display =
				$this->comment_ui->getWidget('preview_message_display');

			$message_display->add($message, SwatMessageDisplay::DISMISS_OFF);

			ob_start();

			$view = BlorgViewFactory::get($this->app, 'comment');
			$view->display($this->comment);

			$comment_preview = $this->comment_ui->getWidget('comment_preview');
			$comment_preview->content = ob_get_clean();
			$comment_preview->content_type = 'text/xml';

			$container = $this->comment_ui->getWidget(
				'comment_preview_container');

			$container->visible = true;
		}
	}

	// }}}
	// {{{ protected function buildAtomLinks()

	protected function buildAtomLinks()
	{
		if ($this->post->hasVisibleCommentStatus()) {
			$this->layout->addHtmlHeadEntry(new SwatLinkHtmlHeadEntry(
				$this->source.'/feed', 'alternate', 'application/atom+xml',
				sprintf(Blorg::_('Recent Comments on “%s”'),
					$this->post->title)));
		}
	}

	// }}}
	// {{{ protected function displayPost()

	protected function displayPost()
	{
		$view = BlorgViewFactory::get($this->app, 'post');
		$view->setPartMode('title', BlorgView::MODE_ALL, false);
		$view->display($this->post);
	}

	// }}}
	// {{{ protected function displayComments()

	protected function displayComments()
	{
		if ($this->post->comment_status != BlorgPost::COMMENT_STATUS_CLOSED) {
			Blorg::displayAd($this->app, 'post_comments');

			$div_tag = new SwatHtmlTag('div');
			$div_tag->id = 'comments';
			$div_tag->class = 'entry-comments';
			$div_tag->open();

			$comments = $this->post->getVisibleComments();

			$view = BlorgViewFactory::get($this->app, 'comment');
			$count = count($comments);

			if ($count > 0) {
				echo '<h3 class="comments-title">',
					Blorg::_('Comments'), '</h3>';
			}

			foreach ($comments as $i => $comment) {
				if ($i == $count - 1) {
					$div_tag = new SwatHtmlTag('div');
					$div_tag->id = 'last_comment';
					$div_tag->open();
					$view->display($comment);
					$div_tag->close();
				} else {
					$view->display($comment);
				}
			}

			$div_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayCommentUi()

	protected function displayCommentUi()
	{
		// Comment form submits to the top of the comment form if there are
		// error messages or if the new comment is not immediately visible.
		// Otherwise the comment form submits to the new comment.
		$comment_status = $this->post->comment_status;
		if ($this->comment_ui->getWidget('comment_edit_form')->hasMessage() ||
			$comment_status == BlorgPost::COMMENT_STATUS_MODERATED ||
			$comment_status == BlorgPost::COMMENT_STATUS_LOCKED ||
			$this->comment_ui->getWidget('preview_button')->hasBeenClicked()) {
			$this->displaySubmitComment();
		}

		$this->comment_ui->display();
	}

	// }}}
	// {{{ protected function displaySubmitComment()

	protected function displaySubmitComment()
	{
		echo '<div id="submit_comment"></div>';
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->comment_ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
