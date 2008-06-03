<?php

require_once 'Swat/SwatDate.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'NateGoSearch/NateGoSearch.php';
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

		if ($this->id === null || $this->reply->author !== null) {
			$this->ui->getWidget('fullname_field')->visible = false;
			$this->ui->getWidget('link_field')->visible     = false;
			$this->ui->getWidget('email_field')->visible    = false;
			$this->ui->getWidget('status')->visible         = false;
		}
	}

	// }}}
	// {{{ protected function initBlorgReply()

	protected function initBlorgReply()
	{
		$class_name = SwatDBClassMap::get('BlorgReply');
		$this->reply = new $class_name();
		$this->reply->setDatabase($this->app->db);

		if ($this->id === null) {
			$post_id = $this->app->initVar('post');
			$class_name = SwatDBClassMap::get('BlorgPost');
			$post = new $class_name();
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
			'status',
		));

		$this->reply->fullname = $values['fullname'];
		$this->reply->link     = $values['link'];
		$this->reply->email    = $values['email'];
		$this->reply->bodytext = $values['bodytext'];
		$this->reply->status   = $values['status'];

		if ($this->reply->id === null) {
			$now = new SwatDate();
			$now->toUTC();
			$this->reply->createdate = $now;
			$this->reply->author     = $this->app->session->getUserID();
		}

		$this->reply->save();
		$this->addToSearchQueue();

		$message = new SwatMessage(Blorg::_('Reply has been saved.'));

		$this->app->messages->add($message);
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue()
	{
		// this is automatically wrapped in a transaction because it is
		// called in saveDBData()
		$type = NateGoSearch::getDocumentType($this->app->db, 'post');

		if ($type === null)
			return;

		$sql = sprintf('delete from NateGoSearchQueue
			where document_id = %s and document_type = %s',
			$this->app->db->quote($this->reply->post->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->reply->post->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->id === null || $this->reply->author !== null) {
			$this->ui->getWidget('edit_form')->action = sprintf('%s?post=%d',
				$this->source, $this->reply->post->id);

			$this->ui->getWidget('author_field')->visible = true;
			$this->ui->getWidget('author')->content =
				$this->app->session->getName();
		}

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
