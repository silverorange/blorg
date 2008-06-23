<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBRange.php';
require_once 'Site/dataobjects/SiteInstance.php';
require_once 'Blorg/dataobjects/BlorgAuthorWrapper.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Efficient post loader
 *
 * Efficiently loads visible comment count and author subdataobjects for a
 * recordset of posts.
 *
 * Example usage:
 * <code>
 * <?php
 * $loader = new BlorgPostLoader($app->db, $app->getInstance());
 *
 * // set select fields
 * $loader->addSelectField('title');
 * $loader->addSelectField('shortname');
 * // ... etc ...
 *
 * $loader->setWhereClause('enabled = true');
 * $loader->setOrderByClause('publish_date desc');
 *
 * $posts = $loader->getPosts();
 * ?>
 * </code>
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostLoader
{
	// {{{ protected properties

	/**
	 * @var MDB2_Driver_Common
	 */
	protected $db;

	/**
	 * @var SiteInstance
	 */
	protected $instance;

	/**
	 * @var SwatDBRange
	 */
	protected $range;

	/**
	 * @var string
	 */
	protected $order_by_clause = 'BlorgPost.publish_date desc';

	/**
	 * @var string
	 */
	protected $where_clause;

	/**
	 * @var array
	 */
	protected $fields = array('shortname');

	// }}}
	// {{{ public function __construct()

	public function __construct(MDB2_Driver_Common $db,
		SiteInstance $instance = null)
	{
		$this->db = $db;
		$this->instance = $instance;
	}

	// }}}

	// get posts
	// {{{ public function getPosts()

	public function getPosts()
	{
		$sql = $this->getSelectClause();
		$sql.= $this->getWhereClause();
		$sql.= $this->getOrderByClause();

		if ($this->range !== null) {
			$this->db->setLimit($this->range->getLimit(),
				$this->range->getOffset());
		}

		$post_wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$posts = SwatDB::query($this->db, $sql, $post_wrapper);


		if (in_array('author', $this->fields)) {
			$this->loadPostAuthors($posts);
		}

		return $posts;
	}

	// }}}
	// {{{ public function getPostCount()

	public function getPostCount()
	{
		$sql = 'select count(id) from BlorgPost';
		$sql.= $this->getWhereClause();
		return SwatDB::queryOne($this->db, $sql);
	}

	// }}}
	// {{{ public function getPost()

	public function getPost($id)
	{
		$sql = $this->getSelectClause();
		$sql.= $this->getWhereClause();
		$sql.= sprintf(' and BlorgPost.id = %s',
			$this->db->quote($id, 'integer'));

		$post_wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$posts = SwatDB::query($this->db, $sql, $post_wrapper);

		if (in_array('author', $this->fields)) {
			$this->loadPostAuthors($posts);
		}

		return $posts->getFirst();
	}

	// }}}

	// setup methods
	// {{{ public function addSelectField()

	public function addSelectField($field)
	{
		if (!in_array($field, $this->fields)) {
			$this->fields[] = $field;
		}
	}

	// }}}
	// {{{ public function removeSelectField()

	public function removeSelectField($field)
	{
		if (in_array($field, $this->fields)) {
			$this->fields = array_diff($this->fields, array($field));
		}
	}

	// }}}
	// {{{ public setOrderByClause()

	public function setOrderByClause($order_by_clause)
	{
		$this->order_by_clause = $order_by_clause;
	}

	// }}}
	// {{{ public function setWhereClause()

	public function setWhereClause($where_clause)
	{
		$this->where_clause = $where_clause;
	}

	// }}}
	// {{{ setRange()

	public function setRange($range = null, $offset = null)
	{
		if ($range instanceof SwatDBRange || $range === null) {
			$this->range = $range;
		} else {
			$this->range = new SwatDBRange($range, $offset);
		}
	}

	// }}}

	// helper methods
	// {{{ protected function getSelectClause()

	protected function getSelectClause()
	{
		$sql = 'select '.$this->getSelectFields().' from BlorgPost';

		if (in_array('visible_comment_count', $this->fields)) {
			$sql.= ' inner join BlorgPostVisibleCommentCountView on
				BlorgPost.id = BlorgPostVisibleCommentCountView.post and
				BlorgPost.instance =
					BlorgPostVisibleCommentCountView.instance';
		}

		return $sql;
	}

	// }}}
	// {{{ protected function getSelectFields()

	protected function getSelectFields()
	{
		$sql = implode(', ', $this->fields);
		return $sql;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$instance_id = ($this->instance === null) ? null : $this->instance->id;
		$sql = sprintf(' where BlorgPost.instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->db->quote($instance_id, 'integer'));

		if ($this->where_clause != '') {
			$sql.= ' and '.$this->where_clause;
		}

		return $sql;
	}

	// }}}
	// {{{ protected function getOrderByClause()

	protected function getOrderByClause()
	{
		$sql = '';

		if ($this->order_by_clause != '') {
			$sql.= ' order by '.$this->order_by_clause;
		}

		return $sql;
	}

	// }}}
	// {{{ protected function loadPostAuthors()

	protected function loadPostAuthors(BlorgPostWrapper $posts)
	{
		$instance_id = ($this->instance === null) ? null : $this->instance->id;

		$author_wrapper = SwatDBClassMap::get('BlorgAuthorWrapper');
		$author_sql = sprintf('select id, name, shortname, visible
			from BlorgAuthor
			where instance %s %s and id in (%%s)',
			SwatDB::equalityOperator($instance_id),
			$this->db->quote($instance_id, 'integer'));

		$posts->loadAllSubDataObjects('author', $this->db, $author_sql,
			$author_wrapper);
	}

	// }}}
}

?>