<?php

require_once 'Site/admin/components/Comment/Index.php';
require_once 'Blorg/admin/BlorgCommentDisplay.php';

/**
 * Page to manage pending comments on posts
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentIndex extends SiteCommentIndex
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		$this->table = 'BlorgComment';

		parent::initInternal();

		$visibility = $this->ui->getWidget('search_visibility');

		// if default comment status is moderated, only show pending comments
		// by default.
		if ($this->app->config->blorg->default_comment_status === 'moderated') {
			$visibility->value = self::SHOW_UNAPPROVED;
		} else {
			$visibility->value = self::SHOW_ALL;
		}
	}

	// }}}
	// {{{ protected function getCommentDisplayWidget()

	protected function getCommentDisplayWidget()
	{
		return new BlorgCommentDisplay('comment');
	}

	// }}}
	// {{{ protected function getCommentCount()

	protected function getCommentCount()
	{
		$sql = 'select count(1) from BlorgComment
			left outer join BlorgAuthor on BlorgComment.author = BlorgAuthor.id
			where '.$this->getWhereClause();

		return SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}
	// {{{ protected function getComments()

	protected function getComments($limit = null, $offset = null)
	{
		$sql = sprintf(
			'select BlorgComment.* from BlorgComment
			left outer join BlorgAuthor on BlorgComment.author = BlorgAuthor.id
			where %s
			order by createdate desc',
			$this->getWhereClause());

		$this->app->db->setLimit($limit, $offset);

		$wrapper = SwatDBClassMap::get('SiteCommentWrapper');
		$comments = SwatDB::query($this->app->db, $sql, $wrapper);

		// efficiently load posts for all comments
		$instance_id = $this->app->getInstanceId();
		$post_sql = sprintf('select id, title, bodytext
			from BlorgPost
			where instance %s %s and id in (%%s)',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$post_wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$comments->loadAllSubDataObjects('post', $this->app->db, $post_sql,
			$post_wrapper);

		// efficiently load authors for all comments
		$instance_id = $this->app->getInstanceId();
		$author_sql = sprintf('select id, name
			from BlorgAuthor
			where instance %s %s and id in (%%s)',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$author_wrapper = SwatDBClassMap::get('BlorgAuthorWrapper');
		$comments->loadAllSubDataObjects('author', $this->app->db, $author_sql,
			$author_wrapper);

		return $comments;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		if ($this->where_clause === null) {
			$where = parent::getWhereClause();

			$instance_id = $this->app->getInstanceId();

			$where.= sprintf(
				' and post in (select id from BlorgPost where instance %s %s)',
				SwatDB::equalityOperator($instance_id),
				$this->app->db->quote($instance_id, 'integer'));

			$this->where_clause = $where;
		}
		return $this->where_clause;
	}

	// }}}
	// {{{ protected function getAuthorWhereClause()

	protected function getAuthorWhereClause()
	{
		$where = '';

		$author = $this->ui->getWidget('search_author')->value;
		if (trim($author) != '') {
			$fullname_clause = new AdminSearchClause('fullname', $author);
			$fullname_clause->table = 'BlorgComment';
			$fullname_clause->operator = AdminSearchClause::OP_CONTAINS;

			$author_clause = new AdminSearchClause('name', $author);
			$author_clause->table = 'BlorgAuthor';
			$author_clause->operator = AdminSearchClause::OP_CONTAINS;

			$where.= ' and (';
			$where.= $fullname_clause->getClause($this->app->db, '');
			$where.= $author_clause->getClause($this->app->db, 'or');
			$where.= ')';
		}

		return $where;
	}

	// }}}
}

?>
