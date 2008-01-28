<?php

require_once 'Swat/SwatDate.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/dataobjects/BlorgReply.php';

/**
 * Page for editing replies
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostReplyEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var BlorgReply
	 */
	protected $reply;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/reply-edit.xml');
		$this->initBlorgReply();

		if ($this->id === null) {
			$this->ui->getWidget('fullname_field')->visible = false;
			$this->ui->getWidget('link_field')->visible     = false;
			$this->ui->getWidget('email_field')->visible    = false;
			$this->ui->getWidget('approved_field')->visible = false;
		}
	}

	// }}}
	// {{{ protected function initBlorgReply()

	protected function initBlorgReply()
	{
		$this->reply = new BlorgReply();
		$this->reply->setDatabase($this->app->db);

		if ($this->id === null) {
			$post_id = $this->app->initVar('post');
			$post = new BlorgPost();
			$post->setDatabase($this->app->db);

			if ($post_id === null || !$post->load($post_id))
				throw new AdminNotFoundException(
					sprintf('Post with id ‘%s’ not found.', $post_id));
			else
				$this->reply->post = $post;

		} elseif (!$this->reply->load($this->id)) {
			throw new AdminNotFoundException(
				sprintf('Reply with id ‘%s’ not found.', $this->id));
		}
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$values = $this->ui->getValues(array(
			'fullname',
			'link',
			'email',
			'bodytext',
			'show',
			'approved',
		));

		$this->reply->fullname = $values['fullname'];
		$this->reply->link     = $values['link'];
		$this->reply->email    = $values['email'];
		$this->reply->bodytext = $values['bodytext'];
		$this->reply->show     = $values['show'];
		$this->reply->approved = $values['approved'];

		if ($this->reply->id === null) {
			$now = new SwatDate();
			$now->toUTC();
			$this->reply->createdate = $now;
			$this->reply->author     = $this->app->session->getUserID();
		}

		$this->reply->save();

		$message = new SwatMessage(Blorg::_('Reply has been saved.'));

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->id === null)
			$this->ui->getWidget('edit_form')->action = sprintf('%s?post=%d',
				$this->source, $this->reply->post->id);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->reply));
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->addEntry(new SwatNavBarEntry(
			$this->reply->post->getTitle(),
			sprintf('Post/Details?id=%s', $this->reply->post->id)));

		if ($this->id === null)
			$this->navbar->addEntry(new SwatNavBarEntry(Blorg::_('New Reply')));
		else
			$this->navbar->addEntry(new SwatNavBarEntry(
				Blorg::_('Edit Reply')));
	}

	// }}}
}

?>
