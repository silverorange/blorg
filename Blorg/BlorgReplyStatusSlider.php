<?php

require_once 'Swat/SwatOptionControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatYUI.php';

/**
 * Slider widget to select between different reply statuses for Blörg posts
 *
 * This is a SwatOptionControl with each option being the reply status value
 * and the title of the value. While the underlying code is quite flexible, the
 * display uses a background image that requires the number of options to be
 * four.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgReplyStatusSlider extends SwatOptionControl
{
	// {{{ public proeprties

	/**
	 * Slider value
	 *
	 * The value of the selected option. Defaults to the first option if set
	 * to null.
	 *
	 * @var mixed
	 */
	public $value = null;

	// }}}
	// {{{ public function__construct()

	/**
	 * Creates a new reply status slider
	 *
	 * @param string $id a non-visible unique id for this widget.
	 *
	 * @see SwatWidget::__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);
		$this->requires_id = true;

		$yui = new SwatYUI(array('slider'));
		$this->html_head_entry_set->addEntrySet($yui->getHtmlHeadEntrySet());

		$this->addJavaScript(
			'packages/blorg/javascript/blorg-reply-status-slider.js',
			Blorg::PACKAGE_ID);

		$this->addStyleSheet(
			'packages/blorg/styles/blorg-reply-status-slider.css',
			Blorg::PACKAGE_ID);
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this reply status slider
	 */
	public function process()
	{
		parent::process();

		$form = $this->getForm();
		$data = &$form->getFormData();

		$key = $this->id.'_value';
		if (isset($data[$key])) {
			if ($this->serialize_values) {
				$salt = $form->getSalt();
				$this->value = SwatString::signedUnserialize(
					$data[$key], $salt);
			} else {
				$this->value = (string)$data[$key];
			}
		}
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this reply status slider
	 */
	public function display()
	{
		parent::display();

		$container_div = new SwatHtmlTag('div');
		$container_div->id = $this->id;
		$container_div->class = 'blorg-reply-status-slider';
		$container_div->open();

		$thumb_div = new SwatHtmlTag('div');
		$thumb_div->id = $this->id.'_thumb';
		$thumb_div->class = 'blorg-reply-status-slider-thumb';
		$thumb_div->open();

		$img_tag = new SwatHtmlTag('img');
		$img_tag->class = 'blorg-reply-status-slider-image';
		$img_tag->src = 'packages/blorg/images/thumb-s.gif';
		$img_tag->alt = '';
		$img_tag->display();

		$thumb_div->close();

		$input_tag = new SwatHtmlTag('input');
		$input_tag->id = $this->id.'_value';
		$input_tag->name = $this->id.'_value';
		$input_tag->type = 'hidden';
		$input_tag->value = $this->value;
		$input_tag->display();

		$container_div->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript required by this control
	 *
	 * @return string the inline JavaScript required by this control.
	 */
	protected function getInlineJavaScript()
	{
		$salt = $this->getForm()->getSalt();
		$options = array();
		foreach ($this->options as $option) {
			if ($this->serialize_values) {
				$value = SwatString::signedSerialize($option->value, $salt);
			} else {
				$value = (string)$option->value;
			}

			$options[] = sprintf('[%s, %s]',
				SwatString::quoteJavaScriptString($value),
				SwatString::quoteJavaScriptString($option->title));
		}
		$options = implode(', ', $options);

		return sprintf(
			"var %s_obj = new BlorgReplyStatusSlider('%s', [%s]);",
			$this->id,
			$this->id,
			$options);
	}

	// }}}
}

?>
