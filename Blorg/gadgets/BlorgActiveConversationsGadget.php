<?php

require_once 'Blorg/BlorgGadget.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'SwatI18N/SwatI18NLocale.php';

/**
 * Displays active convestaions (a.k.a. posts that have recent reply activity)
 *
 * Available settings are:
 *
 * - integer limit controls how many active conversations can be displayed.
 *                 Ten conversations are displayed by default.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgActiveConversationsGadget extends BlorgGadget
{
	// {{{ public function display()

	public function display()
	{
		parent::display();

		$conversations = $this->getActiveConversations();

		if (count($conversations) > 0) {
			echo '<ul>';

			$locale = SwatI18NLocale::get();
			$class_name = SwatDBClassMap::get('BlorgPost');
			foreach ($conversations as $conversation) {
				$post = new $class_name($conversation);

				$li_tag = new SwatHtmlTag('li');
				$li_tag->open();
				$anchor_tag = new SwatHtmlTag('a');
				$anchor_tag->href = $this->getPostRelativeUri($post);
				$anchor_tag->open();

				echo SwatString::minimizeEntities($post->getTitle()), ' ';

				$span_tag =  new SwatHtmlTag('span');
				$span_tag->setContent(sprintf(Blorg::ngettext(
					'(%s reply)', '(%s replies)', $conversation->reply_count),
					$locale->formatNumber($conversation->reply_count)));

				$span_tag->display();

				$anchor_tag->close();
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
		$this->defineSetting('limit', Blorg::_('Limit'), 'integer', 10);
	}

	// }}}
	// {{{ protected function getActiveConversations()

	protected function getActiveConversations()
	{
		$sql = sprintf('select title, bodytext, publish_date, shortname,
				reply_count
			from BlorgPost
				inner join BlorgPostReplyCountView
					on BlorgPost.id = BlorgPostReplyCountView.post
			where enabled = %s and reply_status != %s and instance %s %s
			order by last_reply_date desc',
			$this->app->db->quote(true, 'boolean'),
			$this->app->db->quote(BlorgPost::REPLY_STATUS_CLOSED, 'integer'),
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
