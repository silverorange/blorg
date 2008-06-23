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
	// {{{ public properties

	/**
	 * @var BlorgFile
	 */
	public $file;

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

		$this->requires_id = true;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->class = 'blorg-header-image-display';
		$div_tag->open();

		$img_tag = new SwatHtmlTag('img');
		$img_tag->src = $this->file->getRelativeUri('../');
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
		$javascript = sprintf(
			"var %s_obj = new BlorgHeaderImageDisplay('%s', %s);",
			$this->id, $this->id, $this->file->id);

		return $javascript;
	}

	// }}}
}
?>
