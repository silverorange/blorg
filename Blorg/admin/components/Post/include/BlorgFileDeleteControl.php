<?php

/**
 * Control for deleting a file
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFileDeleteControl extends SwatControl
{
	// {{{ public properties

	/**
	 * @var BlorgFile
	 */
	public $file;

	/**
	 * @var string
	 */
	public $file_title;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);
		$this->html_head_entry_set->addEntrySet(
			XML_RPCAjax::getHtmlHeadEntrySet());

		$yui = new SwatYUI(array('dom', 'event', 'animation'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript(
			'packages/blorg/admin/javascript/blorg-file-delete-control.js',
			Blorg::PACKAGE_ID);

		$this->addStyleSheet(
			'packages/blorg/admin/styles/blorg-file-delete-control.css',
			Blorg::PACKAGE_ID);

		$this->requires_id = true;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();

		$span_tag = new SwatHtmlTag('span');
		$span_tag->id = $this->id;
		$span_tag->class = 'blorg-file-delete-control';
		$span_tag->setContent('');
		$span_tag->display();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
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

		if ($this->file_title === null) {
			$file_title = ($this->file->description === null) ?
				$this->file->filename :
				$this->file->description.' ('.$this->file->filename.')';
		} else {
			$file_title = $this->file_title;
		}

		$file_title = SwatString::quoteJavaScriptString($file_title);

		$javascript.= sprintf(
			"var %s_obj = new BlorgFileDeleteControl('%s', %s, %s);",
			$this->id, $this->id, $this->file->id, $file_title);

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
		$confirm_text = SwatString::quoteJavaScriptString(
			Blorg::_('Delete the file “%s”?'));

		$delete_text = SwatString::quoteJavaScriptString(
			Blorg::_('Delete'));

		return "BlorgFileDeleteControl.confirm_text = {$confirm_text};\n".
			"BlorgFileDeleteControl.delete_text = {$delete_text};\n";
	}

	// }}}
}

?>
