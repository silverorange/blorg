<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Swat/SwatUI.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/BlorgViewFactory.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/dataobjects/BlorgReply.php';
require_once 'Services/Akismet.php';

/**
 * Post page for Blörg
 *
 * Loads and displays a post and handles adding replies to a post.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostPage extends SitePage
{
	// {{{ protected properties

	/**
	 * @var BlorgPost
	 */
	protected $post;

	/**
	 * @var BlorgReply
	 */
	protected $reply;

	/**
	 * @var SwatUI
	 */
	protected $reply_ui;

	/**
	 * @var string
	 */
	protected $reply_ui_xml = 'Blorg/pages/reply-edit.xml';

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new post page
	 *
	 * @param SiteWebApplication $app the application.
	 * @param SiteLayout $layout
	 * @param integer $year
	 * @param string $month_name
	 * @param string $shortname
	 */
	public function __construct(SiteWebApplication $app, SiteLayout $layout,
		$year, $month_name, $shortname)
	{
		parent::__construct($app, $layout);
		$this->initPost($year, $month_name, $shortname);
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
			$this->app->instance->getInstance())) {
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
		$this->initReplyUi();
	}

	// }}}
	// {{{ protected function initReplyUi()

	protected function initReplyUi()
	{
		$this->reply_ui = new SwatUI();
		$this->reply_ui->loadFromXml($this->reply_ui_xml);
	}

	// }}}

	// process phase
	// {{{ public function process()

	public function process()
	{
		parent::process();
		$this->processReplyUi();
	}

	// }}}
	// {{{ protected function processReplyUi()

	protected function processReplyUi()
	{
		$form = $this->reply_ui->getWidget('reply_edit_form');
		$form->process();

		if (($this->post->reply_status == BlorgPost::REPLY_STATUS_OPEN ||
			$this->post->reply_status == BlorgPost::REPLY_STATUS_MODERATED) &&
			$form->isProcessed() && !$form->hasMessage()) {

			$this->processReply();

			if ($this->reply_ui->getWidget('post_button')->hasBeenClicked()) {
				$this->saveReply();
			}
		}
	}

	// }}}
	// {{{ protected function processReply()

	protected function processReply()
	{
		$now = new SwatDate();
		$now->toUTC();

		$fullname   = $this->reply_ui->getWidget('fullname');
		$link       = $this->reply_ui->getWidget('link');
		$email      = $this->reply_ui->getWidget('email');
		$bodytext   = $this->reply_ui->getWidget('bodytext');
		// TODO: http clients may not include these headers
		$ip_address = substr($_SERVER['REMOTE_ADDR'], 0, 15);
		$user_agent = substr($_SERVER['HTTP_USER_AGENT'], 0, 255);

		$class_name = SwatDBClassMap::get('BlorgReply');
		$this->reply = new $class_name();

		$this->reply->fullname   = $fullname->value;
		$this->reply->link       = $link->value;
		$this->reply->email      = $email->value;
		$this->reply->bodytext   = $bodytext->value;
		$this->reply->createdate = $now;
		$this->reply->ip_address = $ip_address;
		$this->reply->user_agent = $user_agent;
		$this->reply->spam       = $this->isReplySpam($this->reply);

		switch ($this->post->reply_status) {
		case BlorgPost::REPLY_STATUS_OPEN:
			$this->reply->status = BlorgReply::STATUS_PUBLISHED;
			break;

		case BlorgPost::REPLY_STATUS_MODERATED:
			$this->reply->status = BlorgReply::STATUS_PENDING;
			break;
		}

		$this->reply->post = $this->post;
	}

	// }}}
	// {{{ protected function saveReply()

	protected function saveReply()
	{
		if ($this->reply_ui->getWidget('remember_me')->value) {
			$this->saveReplyCookie();
		} else {
			$this->deleteReplyCookie();
		}

		switch ($this->post->reply_status) {
		case BlorgPost::REPLY_STATUS_OPEN:
			$message = new SwatMessage(
				Blorg::_('Your reply has been added.'));

			$this->reply_ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);

			break;

		case BlorgPost::REPLY_STATUS_MODERATED:
			$message = new SwatMessage(
				Blorg::_('Your reply has been submitted.'));

			$message->secondary_content =
				Blorg::_('Your reply will be added to this post after being '.
					'approved by the site moderators.');

			$this->reply_ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);

			break;
		}

		$this->clearReplyUi();
		$this->post->replies->add($this->reply);
		$this->post->save();
	}

	// }}}
	// {{{ protected function saveReplyCookie()

	protected function saveReplyCookie()
	{
		$fullname = $this->reply_ui->getWidget('fullname')->value;
		$link     = $this->reply_ui->getWidget('link')->value;
		$email    = $this->reply_ui->getWidget('email')->value;

		$value = array(
			'fullname' => $fullname,
			'link'     => $link,
			'email'    => $email,
		);

		$this->app->cookie->setCookie('reply_credentials', $value);
	}

	// }}}
	// {{{ protected function deleteReplyCookie()

	protected function deleteReplyCookie()
	{
		$this->app->cookie->removeCookie('reply_credentials');
	}

	// }}}
	// {{{ protected function clearReplyUi()

	protected function clearReplyUi()
	{
		$this->reply_ui->getWidget('fullname')->value = null;
		$this->reply_ui->getWidget('link')->value     = null;
		$this->reply_ui->getWidget('email')->value    = null;
		$this->reply_ui->getWidget('bodytext')->value = null;
	}

	// }}}
	// {{{ protected function isReplySpam()

	protected function isReplySpam(BlorgReply $reply)
	{
		$is_spam = false;

		if ($this->app->config->blorg->akismet_key !== null) {
			$uri = $this->app->getBaseHref().$this->app->config->blorg->path;

			$date = clone $this->post->post_date;
			$date->convertTZ($this->app->default_time_zone);
			$permalink = sprintf('%sarchive/%s/%s/%s',
				$uri,
				$date->getYear(),
				BlorgPageFactory::$month_names[$date->getMonth()],
				$this->post->shortname);

			try {
				$akismet = new Services_Akismet($uri,
					$this->app->config->blorg->akismet_key);

				$comment = new Services_Akismet_Comment();
				$comment->setAuthor($reply->fullname);
				$comment->setAuthorEmail($reply->email);
				$comment->setAuthorUri($reply->link);
				$comment->setContent($reply->bodytext);
				$comment->setPostPermalink($permalink);

				$is_spam = $akismet->isSpam($comment);
			} catch (Exception $e) {
			}
		}

		return $is_spam;
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$this->buildNavBar();
		$this->buildReplyUi();

		$this->layout->startCapture('content');
		$this->displayPost();
		$this->displayReplies();
		$this->displayReplyUi();
		$this->layout->endCapture();

		$this->layout->startCapture('html_head_entries');
		$this->displayAtomLinks();
		$this->layout->endCapture();

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

		$date = clone $this->post->post_date;
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
	// {{{ protected function buildReplyUi()

	protected function buildReplyUi()
	{
		$ui              = $this->reply_ui;
		$form            = $ui->getWidget('reply_edit_form');
		$frame           = $ui->getWidget('reply_edit_frame');
		$frame->subtitle = $this->post->getTitle();

		switch ($this->post->reply_status) {
		case BlorgPost::REPLY_STATUS_OPEN:
		case BlorgPost::REPLY_STATUS_MODERATED:
			$form->action = $this->source.'#submit_reply';
			if (isset($this->app->cookie->reply_credentials)) {
				$values = $this->app->cookie->reply_credentials;
				$ui->getWidget('fullname')->value    = $values['fullname'];
				$ui->getWidget('link')->value        = $values['link'];
				$ui->getWidget('email')->value       = $values['email'];
				$ui->getWidget('remember_me')->value = true;
			}
			break;

		case BlorgPost::REPLY_STATUS_LOCKED:
			$form->visible = false;
			$message = new SwatMessage(Blorg::_('Replies are Locked'));
			$message->secondary_content =
				Blorg::_('No new replies may be posted for this article.');

			$ui->getWidget('message_display')->add($message,
				SwatMessageDisplay::DISMISS_OFF);

			break;

		case BlorgPost::REPLY_STATUS_CLOSED:
			$ui->getRoot()->visible = false;
			break;
		}

		$this->buildReplyPreview();
	}

	// }}}
	// {{{ protected function buildReplyPreview()

	protected function buildReplyPreview()
	{
		if ($this->reply instanceof BlorgReply &&
			$this->reply_ui->getWidget('preview_button')->hasBeenClicked()) {

			$button_tag = new SwatHtmlTag('input');
			$button_tag->type = 'submit';
			$button_tag->name = 'post_button';
			$button_tag->value = Blorg::_('Post Now');

			$message = new SwatMessage(Blorg::_('Preview'));
			$message->secondary_content = sprintf(Blorg::_('Your reply has '.
				'not been published. When you are happy with your reply, '.
				'click the Post button to submit your reply. REWRITE %s'),
				$button_tag);

			$message->content_type = 'text/xml';

			$message_display =
				$this->reply_ui->getWidget('preview_message_display');

			$message_display->add($message, SwatMessageDisplay::DISMISS_OFF);

			ob_start();

			$view = BlorgViewFactory::build('reply', $this->app);
			$view->display($this->reply);

			$reply_preview = $this->reply_ui->getWidget('reply_preview');
			$reply_preview->content = ob_get_clean();
			$reply_preview->content_type = 'text/xml';

			$container = $this->reply_ui->getWidget('reply_preview_container');
			$container->visible = true;
		}
	}

	// }}}
	// {{{ protected function displayAtomLinks()

	protected function displayAtomLinks()
	{
		$link = new SwatHtmlTag('link');
		$link->rel = 'alternate';
		$link->href = $this->app->getBaseHref().
			$this->app->config->blorg->path.'atom';

		$link->type = 'application/atom+xml';
		$link->title = sprintf(Blorg::_('%s - Recent Posts'),
			$this->app->config->site->title);

		$link->display();

		echo "\n\t";

		$link = new SwatHtmlTag('link');
		$link->rel = 'alternate';
		$link->href = $this->app->getBaseHref().
			$this->app->config->blorg->path.'atom/replies';

		$link->type = 'application/atom+xml';
		$link->title = sprintf(Blorg::_('%s - Recent Replies'),
			$this->app->config->site->title);

		$link->display();

		echo "\n";

		$link = new SwatHtmlTag('link');
		$link->rel = 'alternate';
		$link->href = $this->app->getBaseHref().$this->source.'/atom';
		$link->type = 'application/atom+xml';
		$link->title = sprintf(Blorg::_('Replies to “%s”'),
			$this->post->title);

		$link->display();

		echo "\n";
	}

	// }}}
	// {{{ protected function displayPost()

	protected function displayPost()
	{
		$view = BlorgViewFactory::build('post', $this->app);
		$view->showTitle(BlorgPostView::SHOW_ALL, false);
		$view->showExtendedBodytext(BlorgPostView::SHOW_ALL);
		$view->display($this->post);
	}

	// }}}
	// {{{ protected function displayReplies()

	protected function displayReplies()
	{
		if ($this->post->reply_status != BlorgPost::REPLY_STATUS_CLOSED) {

			$div_tag = new SwatHtmlTag('div');
			$div_tag->id = 'replies'.$this->post->id;
			$div_tag->class = 'entry-replies';
			$div_tag->open();

			$replies = $this->post->getVisibleReplies();

			$view = BlorgViewFactory::build('reply', $this->app);
			$count = count($replies);
			foreach ($replies as $i => $reply) {
				if ($i == $count - 1) {
					// Reply form submits to the top of the new reply if a new
					// reply was just submitted and it is immediately visible.
					// Otherwise the form submites to the reply preview or to
					// the top of the form.
					$form = $this->reply_ui->getWidget('reply_edit_form');
					$post_button = $this->reply_ui->getWidget('post_button');
					if (!$form->hasMessage() && $this->post->reply_status !=
						BlorgPost::REPLY_STATUS_MODERATED &&
						$post_button->hasBeenClicked()) {
						$this->displaySubmitReply();
					}
					$div_tag = new SwatHtmlTag('div');
					$div_tag->id = 'last_reply';
					$div_tag->open();
					$view->display($reply);
					$div_tag->close();
				} else {
					$view->display($reply);
				}
			}

			$div_tag->close();
		}
	}

	// }}}
	// {{{ protected function displayReplyUi()

	protected function displayReplyUi()
	{
		// Reply form submits to the top of the reply form if there are error
		// messages or if the new post is not immediately visible. Otherwise
		// the reply form submits to the new reply.
		if ($this->reply_ui->getWidget('reply_edit_form')->hasMessage() ||
			$this->post->reply_status == BlorgPost::REPLY_STATUS_MODERATED ||
			$this->post->reply_status == BlorgPost::REPLY_STATUS_LOCKED ||
			$this->reply_ui->getWidget('preview_button')->hasBeenClicked()) {
			$this->displaySubmitReply();
		}

		$this->reply_ui->display();
	}

	// }}}
	// {{{ protected function displaySubmitReply()

	protected function displaySubmitReply()
	{
		echo '<div id="submit_reply"></div>';
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntrySet(
			$this->reply_ui->getRoot()->getHtmlHeadEntrySet());
	}

	// }}}
}

?>
