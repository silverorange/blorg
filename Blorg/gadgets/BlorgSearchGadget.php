<?php

require_once 'Blorg/BlorgGadget.php';
require_once 'Swat/SwatForm.php';
require_once 'Swat/SwatFormField.php';
require_once 'Swat/SwatSearchEntry.php';

/**
 * Displays a search box
 *
 * Available settings are:
 *
 * - string label the label to use on the search box. If unspecified, the label
 *                'keywords' is used.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgSearchGadget extends BlorgGadget
{
	// {{{ protected properties

	/**
	 * @var SwatForm
	 */
	protected $form;

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->createForm();
		$this->form->init();
	}

	// }}}
	// {{{ public function display()

	public function display()
	{
		parent::display();
		$this->createForm();
		$this->form->display();
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	public function getHtmlHeadEntrySet()
	{
		$this->createForm();
		$set = parent::getHtmlHeadEntrySet();
		$set->addEntrySet($this->form->getHtmlHeadEntrySet());
		return $set;
	}

	// }}}
	// {{{ protected function define()

	protected function define()
	{
		$this->defineDefaultTitle(Blorg::_('Search'));
		$this->defineSetting('label', Blorg::_('Label'), 'string',
			Blorg::_('Keywords …'));

		$this->defineDescription(Blorg::_(
			'Displays a search form, allowing searching from any page that '.
			'displays the sidebar.'));
	}

	// }}}
	// {{{ protected function createForm()

	protected function createForm()
	{
		if ($this->form === null) {
			$base = $this->app->config->blorg->path;

			$keywords = new SwatSearchEntry();
			$keywords->id = 'keywords';

			$field = new SwatFormField();
			$field->title = $this->getValue('label');
			$field->add($keywords);

			$this->form = new SwatForm();
			$this->form->action = $base.'search';
			$this->form->setMethod(SwatForm::METHOD_GET);
			$this->form->add($field);
		}
	}

	// }}}
}

?>
