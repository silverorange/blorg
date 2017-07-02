<?php

/**
 * Displays photos from a Flickr JSON source
 *
 * Available settings are:
 *
 * - <kbd>string uri</kbd> - the uri to the Flickr JSON source. This is exactly
 *                           the same as an RSS/Atom link but with format=json.
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFlickrJsonGadget extends SiteGadget
{
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->open();
		$div_tag->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());

		printf('<script src="%s" type="text/javascript"></script>',
			$this->getValue('uri'));
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	protected function getInlineJavaScript()
	{
		$id    = SwatString::quoteJavaScriptString($this->id);
		$size  = SwatString::quoteJavaScriptString($this->getValue('size'));
		$limit = $this->getValue('limit');

		return sprintf(
			"BlorgFlickrJsonGadget.div = %s
			BlorgFlickrJsonGadget.size = %s;
			BlorgFlickrJsonGadget.limit = %s;",
			$id, $size, $limit);
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Flickr Photos'));

		$this->defineSetting('uri', Blorg::_('JSON Photo Feed'), 'string');
		$this->defineSetting('limit', Blorg::_('Limit'), 'integer', 10);
		$this->defineSetting('size', Blorg::_('Display Size'), 'string', 'square');
		$this->defineDescription(Blorg::_('Displays photos from Flickr'));

		$this->addJavaScript(
			'packages/blorg/javascript/blorg-flickr-json-gadget.js',
			Blorg::PACKAGE_ID);

		$this->id = uniqid('flickr');
	}

	// }}}
}

?>
