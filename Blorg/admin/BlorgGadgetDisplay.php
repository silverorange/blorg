<?php

/**
 * Displays a gadget with optional buttons to add, edit and remove the gadget
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgGadgetDisplay extends SwatControl
{
	// {{{ public properties

	/**
	 * @var boolean
	 */
	public $show_add = false;

	/**
	 * @var boolean
	 */
	public $show_edit = false;

	/**
	 * @var boolean
	 */
	public $show_delete = false;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $gadget_title;

	/**
	 * @var string
	 */
	protected $gadget_description;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet(
			'packages/blorg/admin/styles/blorg-gadget-display.css',
			Blorg::PACKAGE_ID);
	}

	// }}}
	// {{{ public function setGadget()

	public function setGadget($gadget)
	{
		if ($gadget instanceof BlorgGadget) {
			$this->gadget_title       = $gadget->getTitle();
			$this->gadget_description = $gadget->getDescription();
		} else {
			if (is_object($gadget) && isset($gadget->title) &&
				$gadget->title != '') {
				$this->gadget_title = $gadget->title;
				if (isset($gadget->description)) {
					$this->gadget_description = $gadget->description;
				}
			} else {
				throw new InvalidArgumentException(
					'Specified object could not be interpreted as a gadget.');
			}
		}
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->gadget_title == '')
			return;

		parent::display();

		$edit_button = $this->getCompositeWidget('edit_button');
		$edit_button->sensitive = $this->isSensitive();

		$add_button = $this->getCompositeWidget('add_button');
		$add_button->sensitive = $this->isSensitive();

		$delete_button = $this->getCompositeWidget('delete_button');
		$delete_button->sensitive = $this->isSensitive();

		$container_div = new SwatHtmlTag('div');
		$container_div->class = $this->getCSSClassString();
		$container_div->id = $this->id;
		$container_div->open();

		if ($this->show_edit || $this->show_add || $this->show_delete) {
			$controls_div = new SwatHtmlTag('div');
			$controls_div->class = 'blorg-gadget-display-controls';
			$controls_div->open();

			if ($this->show_add) {
				$add_button->display();
			}

			if ($this->show_edit) {
				$edit_button->display();
			}

			if ($this->show_delete) {
				$delete_button->display();
			}

			$controls_div->close();
		}

		$content_div = new SwatHtmlTag('div');
		$content_div->class = 'blorg-gadget-display-content';
		$content_div->open();

		$header_tag = new SwatHtmlTag('h3');
		$header_tag->class = 'blorg-gadget-display-title';
		$header_tag->setContent($this->gadget_title);
		$header_tag->display();

		$description_div = new SwatHtmlTag('div');
		$description_div->class = 'blorg-gadget-display-description';
		$description_div->setContent($this->getDescription(), 'text/xml');
		$description_div->display();

		$content_div->close();
		$container_div->close();
	}

	// }}}
	// {{{ public function addHasBeenClicked()

	/**
	 * Returns whether the add button for this gadget display has been clicked
	 *
	 * @return boolean true if the button was clicked, false otherwise.
	 */
	public function addHasBeenClicked()
	{
		$button = $this->getCompositeWidget('add_button');
		return $button->hasBeenClicked();
	}

	// }}}
	// {{{ public function editHasBeenClicked()

	/**
	 * Returns whether the edit button for this gadget display has been clicked
	 *
	 * @return boolean true if the button was clicked, false otherwise.
	 */
	public function editHasBeenClicked()
	{
		$button = $this->getCompositeWidget('edit_button');
		return $button->hasBeenClicked();
	}

	// }}}
	// {{{ public function deleteHasBeenClicked()

	/**
	 * Returns whether the delete button for this gadget display has been
	 * clicked
	 *
	 * @return boolean true if the button was clicked, false otherwise.
	 */
	public function deleteHasBeenClicked()
	{
		$button = $this->getCompositeWidget('delete_button');
		return $button->hasBeenClicked();
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this gadget display
	 *
	 * @return array the array of CSS classes that are applied to this gadget
	 *                display.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('blorg-gadget-display');
		$classes = array_merge($classes, parent::getCSSClassNames());
		return $classes;
	}

	// }}}
	// {{{ protected function getDescription()

	protected function getDescription()
	{
		$description = trim($this->gadget_description);
		$description = SwatString::minimizeEntities($description);
		$description = SwatString::linkify($description);

		// normalize whitespace
		$description = str_replace("\r\n", "\n", $description);
		$description = str_replace("\r", "\n", $description);

		// convert double line breaks to paragraphs
		$description = preg_replace('/[\xa0\s]*\n[\xa0\s]*\n[\xa0\s]*/su',
			'</p><p>', $description);

		$description = '<p>'.$description.'</p>';

		return $description;
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	protected function createCompositeWidgets()
	{
		$add_button = new SwatButton();
		$add_button->id = $this->id.'_add_button';
		$add_button->title = Blorg::_('Add Gadget');
		$add_button->classes[] = 'blorg-theme-display-add_button';
		$this->addCompositeWidget($add_button, 'add_button');

		$edit_button = new SwatButton();
		$edit_button->id = $this->id.'_edit_button';
		$edit_button->title = Blorg::_('Edit');
		$edit_button->classes[] = 'blorg-theme-display-edit_button';
		$this->addCompositeWidget($edit_button, 'edit_button');

		$delete_button = new SwatButton();
		$delete_button->id = $this->id.'_delete_button';
		$delete_button->title = Blorg::_('Remove');
		$delete_button->classes[] = 'blorg-theme-display-delete_button';
		$this->addCompositeWidget($delete_button, 'delete_button');
	}

	// }}}
}

?>
