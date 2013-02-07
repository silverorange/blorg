<?php

require_once 'Swat/SwatFormField.php';
require_once 'Swat/SwatEntry.php';
require_once 'Swat/SwatDateEntry.php';
require_once 'Swat/SwatRadioTable.php';
require_once 'Swat/SwatYUI.php';

/**
 * A custom radio table for publishing posts
 *
 * @package   Blörg
 * @copyright 2008-2013 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPublishRadioTable extends SwatRadioTable
{
	// {{{ class constants

	const PUBLISH_NOW = 1;
	const PUBLISH_AT  = 2;
	const HIDDEN      = 3;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new radio table
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addJavaScript(
			'packages/blorg/admin/javascript/blorg-publish-radio-table.js',
			Blorg::PACKAGE_ID);

		$yui = new SwatYUI(array('dom', 'event', 'animation'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$statuses = array(
			self::PUBLISH_NOW => Blorg::_('Publish Now'),
			self::PUBLISH_AT  => Blorg::_('Publish Date:'),
			self::HIDDEN      => Blorg::_('Hidden'),
		);

		$this->addOptionsByArray($statuses);

		$this->value = self::PUBLISH_NOW;
	}

	// }}}
	// {{{ public function getPublishDate()

	/**
	 * @return SwatDate the selected date or null if the current date should
	 *                  be used. The current date is not returned because it
	 *                  will be in the server time zone. Dates returned by
	 *                  this method are in the local time zone.
	 *
	 * @see BlorgPublishRadioTable::setPublishDate()
	 */
	public function getPublishDate()
	{
		$date = $this->getCompositeWidget('publish_date')->value;

		if ($date === null || $this->value == self::PUBLISH_NOW) {
			$date = null;
		}

		return $date;
	}

	// }}}
	// {{{ public function setPublishDate()

	/**
	 * @param SwatDate $date the date to set. The date should be in the local
	 *                        time zone.
	 *
	 * @see BlorgPublishRadioTable::getPublishDate()
	 */
	public function setPublishDate(SwatDate $date, $hidden = false)
	{
		if ($date !== null) {
			if ($hidden) {
				$this->value = self::HIDDEN;
			} else {
				$this->removeOptionsByValue(self::PUBLISH_NOW);
				$this->value = self::PUBLISH_AT;
			}
		}

		$this->getCompositeWidget('publish_date')->value = $date;
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this radio table
	 */
	public function display()
	{
		parent::display();
		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function displayOptionLabel()

	/**
	 * Displays an option in the radio table
	 *
	 * @param SwatOption $option
	 */
	protected function displayOptionLabel(SwatOption $option, $index)
	{
		parent::displayOptionLabel($option, $index);

		if ($option->value == self::PUBLISH_AT) {
			$this->getCompositeWidget('publish_date')->display();
		}
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	protected function createCompositeWidgets()
	{
		$entry = new SwatDateEntry('publish_date');
		$entry->display_parts = SwatDateEntry::YEAR | SwatDateEntry::MONTH |
			SwatDateEntry::DAY | SwatDateEntry::TIME | SwatDateEntry::CALENDAR;

		$this->addCompositeWidget($entry, 'publish_date');
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript required for this control
	 *
	 * @return string the inline JavaScript required for this control.
	 */
	protected function getInlineJavaScript()
	{
		$javascript = sprintf("var %s_obj = ".
			"new BlorgPublishRadioTable('%s', %d, %d, %s);\n",
			$this->id, $this->id, self::PUBLISH_NOW, self::PUBLISH_AT,
			SwatString::quoteJavaScriptString(Blorg::_('Edit Publish Date')));

		return $javascript;
	}

	// }}}
}

?>
