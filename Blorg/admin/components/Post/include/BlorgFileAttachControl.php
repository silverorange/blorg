<?php

require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatControl.php';
require_once 'Blorg/dataobjects/BlorgFile.php';
require_once 'XML/RPCAjax.php';

/**
 * Control for changing the attachment status of a file
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFileAttachControl extends SwatControl
{
	// {{{ public properties

	/**
	 * @var boolean
	 */
	public $show = true;

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

		$this->addJavaScript(
			'packages/blorg/admin/javascript/blorg-file-attach-control.js',
			Blorg::PACKAGE_ID);

		$this->addStyleSheet(
			'packages/blorg/admin/styles/blorg-file-attach-control.css',
			Blorg::PACKAGE_ID);

		$this->required_id = true;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();

		$img_tag = new SwatHtmlTag('img');
		$img_tag->id = $this->id;
		$img_tag->class = 'blorg-file-attach-control';
		$img_tag->src = ($this->show) ?
			'packages/blorg/admin/images/file-attach-on.png' :
			'packages/blorg/admin/images/file-attach-off.png';

		$img_tag->alt = 'attachment graphic';
		$img_tag->width  = '16';
		$img_tag->height = '16';

		$img_tag->display();

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

		$file_id = ($this->file instanceof BlorgFile) ?
			$this->file->id : intval($this->file);

		$show = ($this->show) ? 'true' : 'false';

		$javascript .= sprintf(
			"var %s_obj = new BlorgFileAttachControl('%s', %s, %s);",
			$this->id, $this->id, $file_id, $show);

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
		$attach_text = SwatString::quoteJavaScriptString(
			Blorg::_('attach to this post'));

		$detach_text = SwatString::quoteJavaScriptString(
			Blorg::_('detach from this post'));

		return
			"BlorgFileAttachControl.attach_text = {$attach_text};\n".
			"BlorgFileAttachControl.detach_text = {$detach_text};\n";
	}

	// }}}
}

?>
