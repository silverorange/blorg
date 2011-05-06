<?php

require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDB.php';
require_once 'Admin/pages/AdminDBDelete.php';
require_once 'Admin/AdminListDependency.php';
require_once 'Admin/AdminSummaryDependency.php';

/**
 * Delete confirmation page for Authors
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAuthorDelete extends AdminDBDelete
{
	// process phaes
	// {{{ protected function processDBData()

	protected function processDBData()
	{
		parent::processDBData();

		$item_list = $this->getItemList('integer');

		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('delete from BlorgAuthor where id in (%s)
			and instance %s %s',
			$item_list,
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$num = SwatDB::exec($this->app->db, $sql);

		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs('authors');
			$this->app->memcache->flushNs('posts');
		}

		$message = new SwatMessage(sprintf(Blorg::ngettext(
			'One author has been deleted.',
			'%s authors have been deleted.', $num),
			SwatString::numberFormat($num)));

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
		$where_clause = sprintf('id in (%s) and instance %s %s',
			$item_list,
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$dep = new AdminListDependency();
		$dep->setTitle('author', 'authors');
		$dep->entries = AdminListDependency::queryEntries($this->app->db,
			'BlorgAuthor', 'integer:id', null, 'text:name', 'id',
			$where_clause, AdminDependency::DELETE);

		$dep_posts = new AdminSummaryDependency();
		$dep_posts->setTitle(
			Blorg::_('post'), Blorg::_('posts'));

		$dep_posts->summaries = AdminSummaryDependency::querySummaries(
			$this->app->db, 'BlorgPost', 'integer:id',
			'integer:author', 'author in ('.$item_list.')',
			AdminDependency::NODELETE);

		$dep->addDependency($dep_posts);

		$message = $this->ui->getWidget('confirmation_message');
		$message->content = $dep->getMessage();
		$message->content_type = 'text/xml';

		if ($dep->getStatusLevelCount(AdminDependency::DELETE) == 0)
			$this->switchToCancelButton();
	}

	// }}}
}

?>
