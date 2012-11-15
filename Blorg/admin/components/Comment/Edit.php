<?php

require_once 'Site/admin/components/Comment/Edit.php';
require_once 'Blorg/dataobjects/BlorgPost.php';
require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';

/**
 * Page for editing comments
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentEdit extends SiteCommentEdit
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->ui_xml = 'Blorg/admin/components/Comment/edit.xml';

		parent::initInternal();

		if ($this->id === null || $this->comment->author !== null) {
			$this->ui->getWidget('fullname_field')->visible = false;
			$this->ui->getWidget('link_field')->visible     = false;
			$this->ui->getWidget('email_field')->visible    = false;
			$this->ui->getWidget('status_field')->visible   = false;
		}
	}

	// }}}
	// {{{ protected function initComment()

	protected function initComment()
	{
		parent::initComment();

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
		}
	}

	// }}}
	// {{{ protected function getComment()

	protected function getComment()
	{
		$class_name = SwatDBClassMap::get('BlorgComment');
		return new $class_name();
	}

	// }}}

	// process phase
	// {{{ protected function saveDBData()

	protected function saveDBData()
	{
		$author_id = $this->ui->getWidget('author')->value;

		if ($this->comment->id === null) {
			$this->comment->author     = $author_id;

			// update user's default author to selected author when creating a
			// new comment
			$this->updateDefaultAuthor($author_id);
		} else {
			if ($this->comment->getInternalValue('author') !== null) {
				$class_name = SwatDBClassMap::get('BlorgAuthor');
				$author     = new $class_name();
				$author->setDatabase($this->app->db);
				if ($author->load($author_id, $this->app->getInstance())) {
					$this->comment->author = $author;
				}
			}
		}

		parent::saveDBData();
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
				where BlorgAuthor.instance %s %s and BlorgAuthor.visible = %s
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

		$statuses = SiteComment::getStatusArray();
		$this->ui->getWidget('status')->addOptionsByArray($statuses);
	}

	// }}}
	// {{{ protected function loadDBData()

	protected function loadDBData()
	{
		parent::loadDBData();

		if ($this->comment->author !== null)
			$this->ui->getWidget('author')->value = $this->comment->author->id;
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		parent::buildNavBar();

		$post_id = $this->app->initVar('post');
		if ($post_id !== null) {
			$this->navbar->popEntry();
			$this->navbar->popEntry();

			$this->navbar->addEntry(new SwatNavBarEntry(
				Blorg::_('Posts'), 'Post'));

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
	}

	// }}}
}

?>
