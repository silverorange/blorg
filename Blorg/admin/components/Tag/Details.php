<?php

/**
 * Details page for tags
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagDetails extends AdminPage
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Tag/details.xml';
	protected $id;

	// }}}
	// {{{ private properties

	/**
	 * @var BlorgTag
	 */
	private $tag;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML($this->ui_xml);
		$this->id = SiteApplication::initVar('id');

		$this->initTag();
	}

	// }}}
	// {{{ private function initTag()

	private function initTag()
	{
		$tag_class = SwatDBClassMap::get('BlorgTag');
		$this->tag = new $tag_class();
		$this->tag->setDatabase($this->app->db);

		if (!$this->tag->load($this->id))
			throw new AdminNotFoundException(
				sprintf(Blorg::_('Tag with id “%s” not found.'), $this->id));
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$this->buildMessages();

		$ds = new SwatDetailsStore($this->tag);
		$ds->post_count = $this->tag->getPostCount();

		$details_view = $this->ui->getWidget('details_view');
		$details_view->data = $ds;

		$details_frame = $this->ui->getWidget('details_frame');
		$details_frame->title = Blorg::_('Tag');
		$details_frame->subtitle = $this->tag->title;

		$this->buildToolbar();
	}

	// }}}
	// {{{ protected function buildToolbar()

	protected function buildToolbar()
	{
		$this->ui->getWidget('edit_tool_link')->link =
			$this->getComponentName().'/Edit?id=%s';

		$this->ui->getWidget('delete_tool_link')->link =
			$this->getComponentName().'/Delete?id=%s';

		$toolbar = $this->ui->getWidget('details_toolbar');
		$toolbar->setToolLinkValues(array($this->id));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$this->navbar->popEntry();
		$this->navbar->addEntry(new SwatNavBarEntry($this->getComponentTitle(),
			$this->getComponentName()));

		$this->navbar->addEntry(new SwatNavBarEntry($this->tag->title));
	}

	// }}}
}

?>
