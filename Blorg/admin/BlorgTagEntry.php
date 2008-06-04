<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Site/SiteTagEntry.php';
require_once 'Blorg/dataobjects/BlorgTagWrapper.php';
require_once 'Blorg/dataobjects/BlorgTag.php';

/**
 * Control for selecting multiple tags from a list of tags
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgTagEntry extends SiteTagEntry
{
	// {{{ private properties

	/**
	 * Application used by this tag entry control
	 *
	 * @var SiteWebApplication
	 */
	private $app;

	// }}}
	// {{{ public function setApplication()

	/**
	 * Sets the application used by this tag entry control
	 *
	 * @param SiteWebApplication $app the application to use.
	 */
	public function setApplication(SiteWebApplication $app)
	{
		$this->app = $app;
	}

	// }}}
	// {{{ public function setAllTags()

	public function setAllTags()
	{
		$instance_id = $this->app->getInstanceId();
		$tag_array = array();

		$sql = sprintf('select * from BlorgTag
			where instance %s %s
			order by title',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$tags = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgTagWrapper'));

		foreach ($tags as $tag)
			$tag_array[$tag->shortname] = $tag->title;

		$this->setTagArray($tag_array);
	}

	// }}}
	// {{{ protected function insertTag()

	/**
	 * Creates a new tag
	 *
	 * @throws SwatException if no database connection is set on this tag
	 *                        entry control.
	 */
	protected function insertTag($title)
	{
		if ($this->app === null)
			throw new SwatException(
				'An application must be set on the tag entry control during '.
				'the widget init phase.');

		// check to see if the tag already exists
		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('select * from
			BlorgTag where title = %s and instance %s %s',
			$this->app->db->quote($title, 'text'),
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$tags = SwatDB::query($this->app->db, $sql,
			SwatDBClassMap::get('BlorgTagWrapper'));

		// only insert if no tag already exists (prevents creating two tags on
		// reloading)
		if (count($tags) > 0) {
			$tag = $tags->getFirst();
		} else {
			$tag = new BlorgTag();
			$tag->setDatabase($this->app->db);
			$tag->instance = $instance_id;
			$tag->title = $title;
			$tag->save();
			$message = new SwatMessage(
				sprintf(Blorg::_('“%s” tag has been added'),
					$tag->title));

			$this->app->messages->add($message);
		}

		$this->selected_tag_array[] = $tag->shortname;
	}

	// }}}
}

?>
