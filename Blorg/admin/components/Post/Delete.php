<?php

require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminDependencyEntry.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';
require_once 'Blorg/dataobjects/BlorgFileWrapper.php';

/**
 * Delete confirmation page for Posts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostDelete extends AdminDBDelete
{
	// process phaes
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('integer');
		$instance_id = $this->app->getInstanceId();

		// delete attached files using their dataobjects to remove the actual
		// files
		$sql = sprintf('select * from BlorgFile
			inner join BlorgPost on BlorgPost.id = BlorgFile.post
			where BlorgPost.instance %s %s and BlorgFile.post in (%s)',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$item_list);

		$files = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgFileWrapper'));

		foreach ($files as $file) {
			$file->setFileBase('../');
			$file->delete();
		}

		// delete the posts
		$sql = sprintf('delete from BlorgPost
			where instance %s %s and id in (%s)',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$item_list);

		$num = SwatDB::exec($this->app->db, $sql);

		$message = new SwatMessage(sprintf(Blorg::ngettext(
			'One post has been deleted.',
			'%d posts have been deleted.', $num),
			SwatString::numberFormat($num)),
			SwatMessage::NOTIFICATION);

		$this->app->messages->add($message);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();

		$item_list = $this->getItemList('integer');
		$instance_id = $this->app->getInstanceId();

		$dep = new AdminListDependency();
		$dep->setTitle(Blorg::_('post'), Blorg::_('posts'));

		$sql = sprintf(
			'select id, title, bodytext from BlorgPost
			where instance %s %s and id in (%s)
			order by publish_date desc, title',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$item_list);

		$posts = SwatDB::query($this->app->db, $sql, 'BlorgPostWrapper');
		$entries = array();

		foreach ($posts as $post) {
			$entry = new AdminDependencyEntry();

			$entry->id           = $post->id;
			$entry->title        = $post->getTitle();
			$entry->status_level = AdminDependency::DELETE;
			$entry->parent       = null;

			$entries[] = $entry;
		}

		$dep->entries = $entries;

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
}

?>
