<?php

require_once 'Swat/SwatOptionControl.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/SwatYUI.php';

/**
 * TODO: document me
 */
class BlorgReplyStatusSlider extends SwatOptionControl
{
	/**
	 * Slider value
	 *
	 * The index value of the selected option. Defaults to the first option
	 * being selected if set to null.
	 *
	 * @var string
	 */
	public $value = null;

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

	public function process()
	{
		$form = $this->getForm();
		$data = &$form->getFormData();
		print_r($data);
		if (isset($data[$this->id.'_value'])) {
			$this->value = (integer)$data[$this->id.'_value'];
		}
	}

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

	/**
	 * Gets the inline JavaScript required by this control
	 *
	 * @return string the inline JavaScript required by this control.
	 */
	protected function getInlineJavaScript()
	{
		$options = array();
		foreach ($this->options as $option) {
			$options[] = SwatString::quoteJavaScriptString($option->title);
		}

		$options = implode(', ', $options);
		return sprintf("var %s_obj = new BlorgReplyStatusSlider('%s', [%s]);",
			$this->id, $this->id, $options);
	}
}
