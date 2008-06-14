<?php

require_once 'Swat/SwatControl.php';
require_once 'Swat/SwatString.php';
require_once 'Blorg/Blorg.php';
require_once 'Site/SiteTheme.php';

/**
 * Displays a theme with a button to select the theme
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgThemeDisplay extends SwatControl
{
	// {{{ public properties

	/**
	 * @var boolean
	 */
	public $selected = false;

	// }}}
	// {{{ protected properties

	/**
	 * @var SiteTheme
	 */
	protected $theme;

	// }}}
	// {{{ public function __construct()

	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet(
			'packages/blorg/admin/styles/blorg-theme-display.css',
			Blorg::PACKAGE_ID);
	}

	// }}}
	// {{{ public function setTheme()

	public function setTheme(SiteTheme $theme)
	{
		$this->theme = $theme;
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();

		if (!$this->visible)
			return;

		if (!($this->theme instanceof SiteTheme))
			return;

		$button = $this->getCompositeWidget('button');
		$button->sensitive = $this->isSensitive();

		$container_div = new SwatHtmlTag('div');
		$container_div->class = $this->getCSSClassString();
		$container_div->id = $this->id;
		$container_div->open();

		if ($this->theme->fileExists('thumbnail.png')) {
			$img_tag = new SwatHtmlTag('img');
			$img_tag->class = 'blorg-theme-display-thumbnail';
			// TODO: This part is not portable to Site
			$img_tag->src = sprintf('Theme/ImageLoader?theme=%s',
				$this->theme->getShortname());

			$img_tag->alt = Blorg::_('Theme thumbnail image');
			$img_tag->width = 150;
			$img_tag->height = 150;
			$img_tag->display();
		}

		if (!$this->selected) {
			$controls_div = new SwatHtmlTag('div');
			$controls_div->class = 'blorg-theme-display-controls';
			$controls_div->open();
			$button->display();
			$controls_div->close();
		}

		$content_div = new SwatHtmlTag('div');
		$content_div->class = 'blorg-theme-display-content';
		$content_div->open();

		$header_tag = new SwatHtmlTag('h3');
		$header_tag->class = 'blorg-theme-display-title';
		$header_tag->setContent($this->theme->getTitle());
		$header_tag->display();

		$description_div = new SwatHtmlTag('div');
		$description_div->class = 'blorg-theme-display-description';
		$description_div->setContent($this->getDescription(), 'text/xml');
		$description_div->display();

		$author_div = new SwatHtmlTag('div');
		$author_div->class = 'blorg-theme-display-author';
		$author_div->open();

		echo SwatString::minimizeEntities($this->theme->getAuthor());

		if ($this->theme->getEmail() !== null) {
			echo ' - ';
			$a_tag = new SwatHtmlTag('a');
			$a_tag->href = sprintf('mailto:%s', $this->theme->getEmail());
			$a_tag->setContent($this->theme->getEmail());
			$a_tag->display();
		}

		$author_div->close();

		$license_div = new SwatHtmlTag('div');
		$license_div->class = 'blorg-theme-display-license';

		$content_div->close();
		$container_div->close();
	}

	// }}}
	// {{{ public function hasBeenClicked()

	/**
	 * Returns whether this theme display has been clicked
	 *
	 * @return boolean whether this theme display has been clicked.
	 */
	public function hasBeenClicked()
	{
		$button = $this->getCompositeWidget('button');
		return $button->hasBeenClicked();
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this theme display
	 *
	 * @return array the array of CSS classes that are applied to this theme
	 *                display.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('blorg-theme-display');

		if ($this->selected) {
			$classes[] = 'blorg-theme-display-selected';
		}

		$classes = array_merge($classes, parent::getCSSClassNames());
		return $classes;
	}

	// }}}
	// {{{ protected function getDescription()

	protected function getDescription()
	{
		$description = trim($this->theme->getDescription());

		$description = SwatString::minimizeEntities(
			$this->theme->getDescription());

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
		$button = new SwatButton();
		$button->id = $this->id.'_button';
		$button->title = Blorg::_('Select Theme');
		$button->classes[] = 'blorg-theme-display-button';
		$this->addCompositeWidget($button, 'button');
	}

	// }}}
}

?>
