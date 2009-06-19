<?php

require_once 'Site/SiteSearchEngine.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * A post search engine
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostSearchEngine extends SiteSearchEngine
{
	// {{{ protected function getResultWrapperClass()

	protected function getResultWrapperClass()
	{
		$wrapper_class = SwatDBClassMap::get('BlorgPostWrapper');
		return $wrapper_class;
	}

	// }}}
	// {{{ protected function getSelectClause()

	protected function getSelectClause()
	{
		$clause = 'select BlorgPost.*, visible_comment_count ';

		return $clause;
	}

	// }}}
	// {{{ protected function getFromClause()

	protected function getFromClause()
	{
		$clause = 'from BlorgPost
			inner join BlorgPostVisibleCommentCountView as v on
				(v.instance = BlorgPost.instance or
					v.instance is null and BlorgPost.instance is null) and
				BlorgPost.id = v.post';

		if ($this->fulltext_result !== null)
			$clause.= ' '.
				$this->fulltext_result->getJoinClause(
					'BlorgPost.id', 'post');

		return $clause;
	}

	// }}}
	// {{{ protected function getWhereClause()

	protected function getWhereClause()
	{
		$instance_id = $this->app->getInstanceId();

		$clause = sprintf('where BlorgPost.instance %s %s and enabled = %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		return $clause;
	}

	// }}}
	// {{{ protected function getOrderByClause()

	protected function getOrderByClause()
	{
		if ($this->fulltext_result === null) {
			$clause = parent::getOrderByClause();
		} else {
			$default_order_by = implode(', ', $this->order_by_fields);
			$clause = $this->fulltext_result->getOrderByClause(
				$default_order_by);
		}

		return $clause;
	}

	// }}}
	// {{{ protected function getMemcacheNs()

	protected function getMemcacheNs()
	{
		return 'posts';
	}

	// }}}
}

?>
