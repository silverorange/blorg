<?php

require_once 'Site/SiteGadget.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Displays active convestaions (a.k.a. posts that have recent comment activity)
 *
 * Available settings are:
 *
 * - integer limit controls how many active conversations can be displayed.
 *                 Five conversations are displayed by default.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgActiveConversationsGadget extends SiteGadget
{
	// {{{ public function init()

	public function init()
	{
		parent::init();

		// set a cookie with the last visit date
		if ($this->app->hasModule('SiteCookieModule')) {
			$now = new SwatDate();
			$cookie = $this->app->getModule('SiteCookieModule');
			$cookie->setCookie('last_visit_date',
				$now->getDate(DATE_FORMAT_ISO_EXTENDED));
		}
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$conversations = $this->getActiveConversations();

		if (count($conversations) > 0) {

			// get last visited date based on cookie value
			if ($this->app->hasModule('SiteCookieModule')) {
				$cookie = $this->app->getModule('SiteCookieModule');
				try {
					if (isset($cookie->last_visit_date)) {
						$last_visit_date = new SwatDate(
							$cookie->last_visit_date);
					} else {
						$last_visit_date = new SwatDate();
					}
				} catch (SiteCookieException $e) {
					$last_visit_date = new SwatDate();
				}
			} else {
				$last_visit_date = new SwatDate();
			}

			echo '<ul>';

			$locale = SwatI18NLocale::get();
			$class_name = SwatDBClassMap::get('BlorgPost');
			foreach ($conversations as $conversation) {
				$post = new $class_name($conversation);

				$last_comment_date = new SwatDate(
					$conversation->last_comment_date);

				$last_comment_date->setTZ('UTC');

				$li_tag = new SwatHtmlTag('li');

				// is last comment is later than last visit date, mark as new
				if (Date::compare($last_comment_date, $last_visit_date) > 0) {
					$li_tag->class = 'new';
				}

				$li_tag->open();

				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->href = $this->getPostRelativeUri($post);
				$anchor_tag->setContent($post->getTitle());

				$span_tag =  new SwatHtmlTag('span');
				$span_tag->setContent(sprintf(Blorg::ngettext(
					'(%s comment)', '(%s comments)',
						$post->getVisibleCommentCount()),
					$locale->formatNumber($post->getVisibleCommentCount())));

				$anchor_tag->display();
				echo ' ';
				$span_tag->display();

				$li_tag->close();
			}

			echo '</ul>';
		}
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Active Conversations'));
		$this->defineSetting('limit', Blorg::_('Limit'), 'integer', 5);
		$this->defineDescription(Blorg::_(
			'Displays a list of posts with recent reader comments.'));
	}

	// }}}
	// {{{ protected function getActiveConversations()

	protected function getActiveConversations()
	{
		$sql = sprintf('select title, bodytext, publish_date, shortname,
				visible_comment_count, last_comment_date
			from BlorgPost
				inner join BlorgPostVisibleCommentCountView as v
					on BlorgPost.id = v.post and
						v.visible_comment_count > 0 and
						(v.instance = BlorgPost.instance or
							(v.instance is null and BlorgPost.instance is null))
			where enabled = %s and comment_status != %s and
				BlorgPost.instance %s %s
			order by last_comment_date desc',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote(BlorgPost::COMMENT_STATUS_CLOSED, 'integer'),
			SwatDB::equalityOperator($this->app->getInstanceId()),
			$this->app->db->quote($this->app->getInstanceId(), 'integer'));

		$this->app->db->setLimit($this->getValue('limit'));

		return SwatDB::query($this->app->db, $sql);
	}

	// }}}
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
}

?>
