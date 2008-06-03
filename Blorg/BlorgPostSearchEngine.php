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
		$clause = 'select BlorgPost.*';
		return $clause;
	}

	// }}}
	// {{{ protected function getFromClause()

	protected function getFromClause()
	{
		$clause = 'from BlorgPost';

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

		$clause = sprintf('where instance %s %s and enabled = %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		return $clause;
	}

	// }}}
}

?>
