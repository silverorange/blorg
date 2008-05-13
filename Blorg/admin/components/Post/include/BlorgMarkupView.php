<?php

require_once 'Swat/SwatOptionControl.php';
require_once 'Swat/SwatTextarea.php';

/**
 * Control for displaying embed markup for files
 *
 * Auto-selects textarea on focus.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgMarkupView extends SwatOptionControl
{
	// {{{ public function __construct()

	/**
	 * Creates a new embed markup view control
	 *
	 * @param string $id a non-visible unique id for this control.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addJavaScript(
			'packages/blorg/admin/javascript/blorg-markup-view.js',
			Blorg::PACKAGE_ID);

		$this->addStyleSheet(
			'packages/blorg/admin/styles/blorg-markup-view.css',
			Blorg::PACKAGE_ID);

		$this->requires_id = true;
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this embed markup view
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		parent::display();

		$div_tag = new SwatHtmlTag('div');
		$div_tag->id = $this->id;
		$div_tag->open();

		$label_tag = new SwatHtmlTag('label');
		$label_tag->for = $this->id.'_textarea';
		$label_tag->class = 'blorg-markup-textarea-label';
		$label_tag->open();

		$span_tag = new SwatHtmlTag('span');
		$span_tag->setContent(Blorg::_('Embed:'));
		$span_tag->display();

		$label_tag->close();

		$first_option = reset($this->getOptions());
		$textarea = $this->getCompositeWidget('textarea');
		$textarea->value = $first_option->value;

		$textarea->display();

		$div_tag->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript for this embed markup view
	 *
	 * @return string the inline JavaScript for this embed markup view.
	 */
	protected function getInlineJavaScript()
	{
		$options = $this->getOptions();

		$values = array();
		$titles = array();
		foreach ($options as $option) {
			$values[] = SwatString::quoteJavaScriptString($option->value);
			$titles[] = SwatString::quoteJavaScriptString($option->title);
		}

		$values = implode(', ', $values);
		$titles = implode(', ', $titles);

		return sprintf("var %s_obj = new BlorgMarkupView('%s', [%s], [%s]);",
			$this->id, $this->id, $values, $titles);
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	/**
	 * Creates and adds composite widgets of this widget
	 *
	 * Created composite widgets should be added in this method using
	 * {@link SwatWidget::addCompositeWidget()}.
	 */
	protected function createCompositeWidgets()
	{
		$textarea = new SwatTextarea($this->id.'_textarea');
		$textarea->rows = 2;
		$textarea->cols = 40;
		$textarea->read_only = true;
		$textarea->resizeable = false;
		$this->addCompositeWidget($textarea, 'textarea');
	}

	// }}}
}

?>
