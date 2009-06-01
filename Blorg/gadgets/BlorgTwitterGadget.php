<?php

require_once 'Services/Twitter.php';
require_once 'Swat/SwatDate.php';
require_once 'Site/gadgets/SiteGadget.php';

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
	 * @var integer the amount of time in minutes
	 */
	const UPDATE_THRESHOLD = 5;

	/**
	 * The name of the cache that stors the timeline's xml
	 *
	 * @var string the name of the cache
	 */
	const CACHE_NAME = 'timeline';

	// }}}
	// {{{ protected properties

	/**
	 * The exception generated if there was an error requesting the user
	 *  timeline
	 *
	 * @var Services_Twitter_Exception null if no exception occured else an
	 *                                  exception object
	 */
	protected $timeline_exception;

	/**
	 * An object used to access the Twitter API
	 *
	 * @var Services_Twitter the API object
	 */
	protected $twitter;

	/**
	 * A SimpleXMLElement object that contains the current twitter timeline
	 *
	 * @var SimpleXMLElement the current user timeline
	 */
	protected $timeline;

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

		$this->timeline = $this->getTimeline();
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();

		if  ($this->hasTimeline())
			$this->displayFooter();
	}

	// }}}
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		if ($this->hasTimeline())
			$this->displayTimeline();
		else
			$this->displayErrorMessage();
	}

	// }}}
	// {{{ protected function displayTimeline()

	protected function displayTimeline()
	{
		$span_tag = new SwatHtmlTag('span');
		$a_tag = new SwatHtmlTag('a');

		echo '<ul>';

		for ($i = 0; $i < $this->getValue('max_updates')
				&& count($this->timeline->status) > $i; $i++) {
			$status = $this->timeline->status[$i];
			$create_date = new SwatDate(strtotime($status->created_at),
				DATE_FORMAT_UNIXTIME);

			echo '<li>';
			$a_tag->href = sprintf('%s/%s/status/%s', Services_Twitter::$uri,
				$this->getValue('username'), $status->id);

			$a_tag->setContent($status->text);
			$span_tag->setContent(sprintf('(around %s ago)',
				$create_date->getHumanReadableDateDiff()));

			$a_tag->display();
			echo ' ';
			$span_tag->display();
			echo '</li>';
		}

		echo '</ul>';
	}

	// }}}
	// {{{ protected function displayErrorMessage()

	protected function displayErrorMessage()
	{
		switch ($this->timeline_exception->getCode()) {
			case Services_Twitter::ERROR_DOWN:
			case Services_Twitter::ERROR_UNAVAILABLE:
				$message = Blorg::_(
					'Looks like Twitter is unavailable right now.');
				break;
			default:
				$message = Blorg::_('Something went wrong. Now that we know'.
					' about it we will fix it.');
		}

		echo $message;
	}

	// }}}
	// {{{ protected function displayFooter()

	protected function displayFooter()
	{
		$real_name = $this->timeline->status[0]->user->name;

		$footer = new SwatHtmlTag('div');
		$footer->class = 'site-gadget-footer';

		$a_tag = new SwatHtmlTag('a');
		$a_tag->href = Services_Twitter::$uri.'/'.$this->getValue('username');
		$a_tag->setContent($real_name);

		$footer->setContent(sprintf(Blorg::_('Follow %s on Twitter'), $a_tag),
			'text/xml');

		if ($this->hasTimeline())
			$footer->display();
	}

	// }}}
	// {{{ protected function hasTimeline()

	protected function hasTimeline()
	{
		return ($this->timeline !== null);
	}

	// }}}
	// {{{ protected function getTimeline()

	/**
	 * Gets the user timeline
	 *
	 * First checks if there is an unexpired timeline in the cache. If no
	 *  unexpired timeline is found query Twitter for a new timeline. Finally
	 *  if Twitter is unable to provide a new timeline return either the
	 *  expired timeline from the cache or null if an expired update does not
	 *  exist.
	 *
	 * @return SimepleXMLElement the user timeline or null if no timeline is
	 *                            available.
	 */
	protected function getTimeline()
	{
		$timeline = null;

		if ($this->hasCache(self::CACHE_NAME)) {
			$xml_string = $this->getCacheValue(self::CACHE_NAME);
			$timeline = simplexml_load_string($xml_string);
			$last_update = $this->getCacheLastUpdateDate(self::CACHE_NAME);
			$last_update->addMinutes(self::UPDATE_THRESHOLD);

			if ($this->now->before($last_update))
				return $timeline;
		}

		try {
			$params = array('id' => $this->getValue('username'));
			$timeline = $this->twitter->statuses->user_timeline($params);
		} catch (Services_Twitter_Exception $e) {
			$this->timeline_exception = $e;
		}

		if ($timeline !== null)
			$this->updateCacheValue(self::CACHE_NAME, $timeline->asXML());

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
