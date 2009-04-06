<?php

require_once 'Services/Twitter.php';
require_once 'Swat/SwatDate.php';
require_once 'Site/gadgets/SiteGadget.php';
require_once 'Site/exceptions/SiteException.php';

/**
 * Displays recent twitter updates
 *
 * Available settings are:
 *
 * - string username the twitter username for which to display updates
 * - integer max_updates the number of updates to display
 *
 * @package   Blörg
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTwitterGadget extends SiteGadget
{
	// {{{ constants

	/**
	 * The amount of time in minutes we wait before we update the cache
	 *
	 * @var integet the amount of time in minutes
	 */
	const UPDATE_THRESHOLD = 5;

	// }}}
	// {{{ protected properties

	/**
	 * Whether or not there was an error requesting the users timerline.
	 *
	 * @var mixed false if no error occured else an integer error code
	 */
	protected $has_error = false;

	/**
	 * An object used to access the Twitter API
	 *
	 * @var Services_Twitter the API object
	 */
	protected $twitter;

	/**
	 * The current date and time
	 *
	 * @var SwatDate the current date and time
	 */
	protected $now;

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->twitter = new Services_Twitter(null, null);

		$this->now = new SwatDate();
		$this->now->toUTC();
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		if ($this->hasCache()) {
			$last_update = $this->getCacheLastUpdateDate();
			$last_update->addMinutes(self::UPDATE_THRESHOLD);

			if ($this->now->after($last_update)) {
				$timeline = $this->getUserTimeline();
				if (!$this->has_error)
					$this->updateCacheValue($timeline->asXML());
			} else {
				$timeline = simplexml_load_string($this->getCacheValue());
			}
		} else {
			$timeline = $this->getUserTimeline();
			if (!$this->has_error)
				$this->updateCacheValue($timeline->asXML());
		}

		if ($this->has_error)
			$this->displayErrorMessage();
		else
			$this->displayTimeline($timeline);

	}

	// }}}
	// {{{ protected function displayTimeline()

	protected function displayTimeline($timeline)
	{
		$span_tag = new SwatHtmlTag('span');
		$a_tag = new SwatHtmlTag('a');

		echo '<ul>';

		for ($i = 0; $i < $this->getValue('max_updates')
				&& count($timeline->status) > $i; $i++) {
			$status = $timeline->status[$i];
			$create_date = new SwatDate(strtotime($status->created_at),
				DATE_FORMAT_UNIXTIME);

			echo '<li>';
			$a_tag->href = sprintf('%s/%s/status/%s', Services_Twitter::$uri,
				$this->getValue('username'), $status->id);

			$a_tag->setContent($status->text);
			$span_tag->setContent(sprintf('(%s)',
				$this->getDateString($create_date)));

			$a_tag->display();
			echo ' ';
			$span_tag->display();
			echo '</li>';
		}

		echo '</ul>';

		printf('Follow <a href="%1$s/%2$s">%2$s</a> on Twitter',
			Services_Twitter::$uri, $this->getValue('username'));
	}

	// }}}
	// {{{ protected function displayError()

	protected function displayErrorMessage()
	{
		switch ($this->has_error) {
			case Services_Twitter::ERROR_DOWN:
			case Services_Twitter::ERROR_UNAVAILABLE:
				echo 'Looks like twitter is unavailable right now.';
				break;
			default:
				echo 'Something went wrong. Now we know about it so we will'.
					'fix it.';
		}
	}

	// }}}
	// {{{ protected function getDateString()

	protected function getDateString(SwatDate $post_date)
	{
		$difference = $this->now->dateDiff($post_date);
		$hours = ceil($difference * 24);
		$date_string = sprintf(Blorg::ngettext(
			'around one hour ago',
			'around %s hours ago', $hours),
			SwatString::numberFormat($hours));

		return $date_string;
	}

	// }}}
	// {{{ protected function getUserTimeline()

	protected function getUserTimeline()
	{
		$timeline = null;

		try {
			$params = array('id' => $this->getValue('username'));
			$timeline = $this->twitter->statuses->user_timeline($params);
		} catch (Services_Twitter_Exception $e) {
			$this->has_error = $e->getCode();
			$exception = new SiteException($e->getMessage(), $e->getCode());
			$exception->log();
		}

		return $timeline;
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Twitter Updates'));
		$this->defineSetting('username', Blorg::_('User Name'), 'string');
		$this->defineSetting('max_updates',
			Blorg::_('Number of updates to Display'), 'integer', 5);

		$this->defineDescription(Blorg::_('Lists recent updates from Twitter'));
	}

	// }}}
}

?>
