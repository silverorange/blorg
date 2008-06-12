<?php

require_once 'Swat/SwatDate.php';
require_once 'Admin/exceptions/AdminNotFoundException.php';
require_once 'Admin/pages/AdminDBEdit.php';
require_once 'NateGoSearch/NateGoSearch.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/dataobjects/BlorgComment.php';
require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';

/**
 * Page for editing comments
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostCommentEdit extends AdminDBEdit
{
	// {{{ protected properties

	/**
	 * @var BlorgComment
	 */
	protected $comment;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->ui->loadFromXML(dirname(__FILE__).'/comment-edit.xml');
		$this->initBlorgComment();

		if ($this->id === null || $this->comment->author !== null) {
			$this->ui->getWidget('fullname_field')->visible = false;
			$this->ui->getWidget('link_field')->visible     = false;
			$this->ui->getWidget('email_field')->visible    = false;
			$this->ui->getWidget('status_field')->visible   = false;
		}
	}

	// }}}
	// {{{ protected function initBlorgComment()

	protected function initBlorgComment()
	{
		$class_name = SwatDBClassMap::get('BlorgComment');
		$this->comment = new $class_name();
		$this->comment->setDatabase($this->app->db);

		if ($this->id === null) {
			$post_id = $this->app->initVar('post');
			$class_name = SwatDBClassMap::get('BlorgPost');
			$post = new $class_name();
			$post->setDatabase($this->app->db);

			if ($post_id === null) {
				throw new AdminNotFoundException(
					'Post must be specified when creating a new comment.');
			}

			if (!$post->load($post_id, $this->app->getInstance())) {
				throw new AdminNotFoundException(
					sprintf('Post with id ‘%s’ not found.', $post_id));
			}

			$this->comment->post = $post;

		} elseif (!$this->comment->load($this->id, $this->app->getInstance())) {
			throw new AdminNotFoundException(
				sprintf('Comment with id ‘%s’ not found.', $this->id));
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
			'author',
		));

		if ($this->comment->id === null) {
			$now = new SwatDate();
			$now->toUTC();
			$this->comment->createdate = $now;
			$this->comment->author     = $values['author'];

			// update user's default author to selected author when creating a
			// new comment
			$this->updateDefaultAuthor($values['author']);
		} else {
			if ($this->comment->getInternalValue('author') === null) {
				$this->comment->fullname = $values['fullname'];
				$this->comment->link     = $values['link'];
				$this->comment->email    = $values['email'];
				$this->comment->status   = $values['status'];
			} else {
				$author_id  = $values['author'];
				$class_name = SwatDBClassMap::get('BlorgAuthor');
				$author     = new $class_name();
				$author->setDatabase($this->app->db);
				if ($author->load($author_id, $this->app->getInstance())) {
					$this->comment->author = $author;
				}
			}
		}

		if ($this->comment->status === null) {
			$this->comment->status = BlorgComment::STATUS_PUBLISHED;
		}

		$this->comment->bodytext = $values['bodytext'];

		$this->comment->save();

		$this->addToSearchQueue();

		$message = new SwatMessage(Blorg::_('Comment has been saved.'));

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
			$this->app->db->quote($this->comment->post->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);

		$sql = sprintf('insert into NateGoSearchQueue
			(document_id, document_type) values (%s, %s)',
			$this->app->db->quote($this->comment->post->id, 'integer'),
			$this->app->db->quote($type, 'integer'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function updateDefaultAuthor()

	protected function updateDefaultAuthor($author_id)
	{
		$instance_id = $this->app->getInstanceId();
		$user_id     = $this->app->session->getUserId();

		if ($instance_id !== null) {
			$sql = sprintf('update AdminUserInstanceBinding
				set default_author = %s
				where usernum = %s and instance = %s',
				$this->app->db->quote($author_id, 'integer'),
				$this->app->db->quote($user_id, 'integer'),
				$this->app->db->quote($instance_id, 'integer'));

			SwatDB::exec($this->app->db, $sql);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		if ($this->id === null || $this->comment->author !== null) {
			$this->ui->getWidget('edit_form')->action = sprintf('%s?post=%d',
				$this->source, $this->comment->post->id);

			$this->ui->getWidget('author_field')->visible = true;

			$instance_id = $this->app->getInstanceId();
			$sql = sprintf('select BlorgAuthor.*,
					AdminUserInstanceBinding.usernum
				from BlorgAuthor
				left outer join AdminUserInstanceBinding on
					AdminUserInstanceBinding.default_author = BlorgAuthor.id
				where BlorgAuthor.instance %s %s and BlorgAuthor.show = %s
				order by displayorder',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'),
				$this->app->db->quote(true, 'boolean'));

			$rs = SwatDB::query($this->app->db, $sql);

			$default_author = null;
			$authors = array();
			foreach ($rs as $row) {
				$authors[$row->id] = $row->name;

				if ($this->id === null &&
					$row->usernum == $this->app->session->user->id)
					$this->ui->getWidget('author')->value = $row->id;
			}

			$this->ui->getWidget('author')->addOptionsByArray($authors);
		}

		$statuses = array(
			BlorgComment::STATUS_PUBLISHED =>
				BlorgComment::getStatusTitle(BlorgComment::STATUS_PUBLISHED),
			BlorgComment::STATUS_PENDING =>
				BlorgComment::getStatusTitle(BlorgComment::STATUS_PENDING),
			BlorgComment::STATUS_UNPUBLISHED =>
				BlorgComment::getStatusTitle(BlorgComment::STATUS_UNPUBLISHED),
		);

		$this->ui->getWidget('status')->addOptionsByArray($statuses);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		$this->ui->setValues(get_object_vars($this->comment));

		if ($this->comment->author !== null)
			$this->ui->getWidget('author')->value = $this->comment->author->id;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->navbar->addEntry(new SwatNavBarEntry(
			$this->comment->post->getTitle(),
			sprintf('Post/Details?id=%s', $this->comment->post->id)));

		if ($this->id === null)
			$this->navbar->addEntry(new SwatNavBarEntry(
				Blorg::_('New Comment')));
		else
			$this->navbar->addEntry(new SwatNavBarEntry(
				Blorg::_('Edit Comment')));
	}

	// }}}
}

?>
