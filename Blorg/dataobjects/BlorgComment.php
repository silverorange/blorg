<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Swat/SwatString.php';
require_once 'Site/dataobjects/SiteComment.php';
require_once 'Blorg/dataobjects/BlorgAuthor.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

/**
 * A comment on a Blörg Post
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgComment extends SiteComment
{
	// {{{ public function load()

	/**
	 * Loads this comment
	 *
	 * @param integer $id the database id of this comment.
	 * @param SiteInstance $instance optional. The instance to load the comment
	 *                                in. If the application does not use
	 *                                instances, this should be null. If
	 *                                unspecified, the instance is not checked.
	 *
	 * @return boolean true if this comment and false if it was not.
	 */
	public function load($id, SiteInstance $instance = null)
	{
		$this->checkDB();

		$loaded = false;
		$row = null;
		if ($this->table !== null && $this->id_field !== null) {
			$id_field = new SwatDBField($this->id_field, 'integer');

			$sql = sprintf('select %1$s.* from %1$s
				inner join BlorgPost on %1$s.post = BlorgPost.id
				where %1$s.%2$s = %3$s',
				$this->table,
				$id_field->name,
				$this->db->quote($id, $id_field->type));

			$instance_id  = ($instance === null) ? null : $instance->id;
			if ($instance_id !== null) {
				$sql.=sprintf(' and instance %s %s',
					SwatDB::equalityOperator($instance_id),
					$this->db->quote($instance_id, 'integer'));
			}

			$rs = SwatDB::query($this->db, $sql, null);
			$row = $rs->fetchRow(MDB2_FETCHMODE_ASSOC);
		}

		if ($row !== null) {
			$this->initFromRow($row);
			$this->generatePropertyHashes();
			$loaded = true;
		}

		return $loaded;
	}

	// }}}
	// {{{ public function clearCache()

	public function clearCache(SiteApplication $app)
	{
		if (isset($app->memcache)) {
			$app->memcache->flushNs('posts');
		}
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->registerInternalProperty('post',
			SwatDBClassMap::get('BlorgPost'));

		$this->registerInternalProperty('author',
			SwatDBClassMap::get('BlorgAuthor'));

		$this->table = 'BlorgComment';
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array(
			'post',
			'author',
		);
	}

	// }}}
	// {{{ protected function addToSearchQueue()

	protected function addToSearchQueue(SiteApplication $app)
	{
		$app->addToSearchQueue('post', $this->post->id);
		$app->addToSearchQueue('comment', $this->id);
	}

	// }}}
}

?>
