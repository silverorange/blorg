<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatYUI.php';
require_once 'Blorg/dataobjects/BlorgFile.php';
require_once 'XML/RPCAjax.php';

/**
 * Control for displaying and deleting header images in the Blörg admin
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgHeaderImageDisplay extends SwatControl
{
	// {{{ protected properties

	/**
	 * @var BlorgFile
	 */
	protected $file;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);
		$this->html_head_entry_set->addEntrySet(
			XML_RPCAjax::getHtmlHeadEntrySet());

		$yui = new SwatYUI(array('dom', 'event'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript(
			'packages/blorg/admin/javascript/blorg-header-image-display.js',
			Blorg::PACKAGE_ID);

		$this->addStyleSheet(
			'packages/blorg/admin/styles/blorg-header-image-display.css',
			Blorg::PACKAGE_ID);

		$this->addStyleSheet(
			'packages/swat/styles/swat-tool-link.css',
			Swat::PACKAGE_ID);

		$this->requires_id = true;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->file === null)
			return;

		parent::display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->class = 'blorg-header-image-display';
		$div_tag->open();

		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $this->file->getRelativeUri('../');
		$img_tag->alt = Blorg::_('Header Image');
		$img_tag->display();

		$div_tag->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ public function setFile()

	public function setFile(BlorgFile $file)
	{
		$this->file = $file;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript required for this control
	 *
	 * @return stirng the inline JavaScript required for this control.
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

		$javascript.= sprintf(
			"var %s_obj = new BlorgHeaderImageDisplay('%s', %s);",
			$this->id, $this->id, $this->file->id);

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
		$delete_text  = SwatString::quoteJavaScriptString(
			Blorg::_('Delete Image'));

		$confirm_text = SwatString::quoteJavaScriptString(
			Blorg::_('Delete header image?'));

		return
			"BlorgHeaderImageDisplay.delete_text  = {$delete_text};\n".
			"BlorgHeaderImageDisplay.confirm_text = {$confirm_text};\n";
	}

	// }}}
}
?>
