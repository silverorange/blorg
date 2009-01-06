<?php

require_once 'Site/gadgets/SiteGadget.php';
require_once 'Swat/SwatString.php';
require_once 'Swat/SwatYUI.php';

/**
 * Displays recently listened songs from Last.fm
 *
 * Available settings are:
 *
 * - string username the Last.fm username for which to display songs. If not
 *                   specified, nothing is displayed.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgLastFmGadget extends SiteGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->open();
		$div_tag->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		static $shown = false;

		if (!$shown) {
			$javascript = $this->getInlineJavaScriptTranslations();
			$shown = true;
		} else {
			$javascript = '';
		}

		$id     = SwatString::quoteJavaScriptString($this->id);
		$limit  = $this->getValue('limit');
		$invert = ($this->getValue('invert')) ? 'true' : 'false';

		$date_format = SwatString::quoteJavaScriptString(
			$this->getValue('date_format'));

		$username = SwatString::quoteJavaScriptString(
			$this->getValue('username'));

		$javascript.= sprintf("new BlorgLastFmGadget(%s, %s, %s, %s, %s);",
			$id, $username, $limit, $invert, $date_format);

		return $javascript;
	}

	// }}}
	// {{{ protected function getInlineJavaScriptTranslations()

	/**
	 * Gets translatable string resources for the JavaScript object for
	 * this gadget
	 *
	 * @return string translatable JavaScript string resources for this gadget.
	 */
	protected function getInlineJavaScriptTranslations()
	{
		$throbber_text = Blorg::_('loading …');
		$visit_text    = Blorg::_('Visit the Last.fm page for this track');
		$none_text     = Blorg::_('‹none›');

		$months = array();
		$date = new Date('2000-01-01T00:00:00Z');
		while ($date->getMonth() <= 12) {
			$months[] = SwatString::quoteJavaScriptString($date->format('%B'));
			$date->setMonth($date->getMonth() + 1);
		}

		$months = implode(', ', $months);

		return sprintf(
			"BlorgLastFmGadget.throbber_text = '%s';\n".
			"BlorgLastFmGadget.visit_text = '%s';\n".
			"BlorgLastFmGadget.none_text = '%s';\n".
			"BlorgLastFmGadget.months = [%s];\n",
			$throbber_text,
			$visit_text,
			$none_text,
			$months);
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Recently Listened'));

		$this->defineSetting('username', Blorg::_('User Name'), 'string');
		$this->defineSetting('limit',    Blorg::_('Limit'), 'integer', 10);
		$this->defineSetting('invert',   Blorg::_('Invert Loading Image'),
			'boolean', false);

		$this->defineSetting('date_format',
			Blorg::_('Date Format (“short” 2:36pm, Jan 5 — or — '.
				'“long” 2:36 pm, January 5)'), 'string', 'long');

		$this->defineDescription(Blorg::_(
			'Lists recently listened songs for a user on Last.fm.'));

		$this->defineAjaxProxyMapping('^last\.fm/([^/]+)$',
			'http://ws.audioscrobbler.com/1.0/user/\1/recenttracks.xml');

		$yui = new SwatYUI(array('dom', 'connection', 'animation'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript(
			'packages/blorg/javascript/blorg-last-fm-gadget.js',
			Blorg::PACKAGE_ID);

		$this->id = uniqid();
	}

	// }}}
}

?>
