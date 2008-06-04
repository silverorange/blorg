<?php

require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Blorg/dataobjects/BlorgReplyWrapper.php';
require_once 'Blorg/dataobjects/BlorgTagWrapper.php';
require_once 'Blorg/dataobjects/BlorgFileWrapper.php';
require_once 'Blorg/dataobjects/BlorgAuthor.php';

/**
 * A Blörg Post
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPost extends SwatDBDataObject
{
	// {{{ class constants

	/**
	 * New replies are allowed, and are automatically show on the site as long
	 * as they are not detected as spam.
	 */
	const REPLY_STATUS_OPEN      = 0;

	/**
	 * New replies are allowed, but must be approved by an admin user before
	 * being shown.
	 */
	const REPLY_STATUS_MODERATED = 1;

	/**
	 * No new replies are allowed, but exisiting replies are shown.
	 */
	const REPLY_STATUS_LOCKED    = 2;

	/**
	 * No new replies are allowed, and existing replies are no longer shown.
	 */
	const REPLY_STATUS_CLOSED    = 3;

	// }}}
	// {{{ public properties

	/**
	 * Unique identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Post Title
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Post Shortname
	 *
	 * Auto-magically generated from title if it exists, otherwise generated
	 * from the start of the bodytext.  Can also be manually set.
	 *
	 * @var string
	 */
	public $shortname;

	/**
	 * Main Body of the Blorg Post
	 *
	 * @var string
	 */
	public $bodytext;

	/**
	 * Any Extended Body of the Blorg Post
	 *
	 * This is only displayed on the full post page.
	 *
	 * @var string
	 */
	public $extended_bodytext;

	/**
	 * Date the post was created.
	 *
	 * @var Date
	 */
	public $createdate;

	/**
	 * Last Modified Date of the post.
	 *
	 * @var Date
	 */
	public $modified_date;

	/**
	 * Date the post was published - used for display and ordering by date.
	 *
	 * @var Date
	 */
	public $publish_date;

	/**
	 * The status of replies on this post.
	 *
	 * @var integer
	 */
	public $reply_status;

	/**
	 * Whether or not the post is viewable on the site.
	 *
	 * @var boolean
	 */
	public $enabled;

	// }}}
	// {{{ public function loadByDateAndShortname()

	/**
	 * Loads a post by a date and the post's shortname
	 *
	 * @param SwatDate $date the date the <i>publish_date<i> of the post. Only
	 *                        the year and month fields are used for comparison.
	 * @param string $shortname the shortname of the post to load.
	 * @param SiteInstance $instance optional. The instance to load the post in.
	 *                               If the site does not use instances, this
	 *                               should be null.
	 *
	 * @return boolean true if this post was loaded from the given publish_date
	 *                 and shortname and false if it was not.
	 */
	public function loadByDateAndShortname(SwatDate $date, $shortname,
		SiteInstance $instance = null)
	{

		$this->checkDB();

		$loaded = false;
		$row = null;
		if ($this->table !== null) {
			$instance_id  = ($instance === null) ? null : $instance->id;

			$sql = sprintf('select * from %s
				where shortname = %s and
					date_trunc(\'month\',
						convertTZ(publish_date, %s)) =
					date_trunc(\'month\', timestamp %s) and
					instance %s %s',
				$this->table,
				$this->db->quote($shortname, 'text'),
				$this->db->quote($date->tz->getId(), 'text'),
				$this->db->quote($date->getDate(), 'date'),
				SwatDB::equalityOperator($instance_id),
				$this->db->quote($instance_id, 'integer'));

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
	// {{{ public static function getReplyStatusTitle()

	public static function getReplyStatusTitle($status)
	{
		switch ($status) {
		case self::REPLY_STATUS_OPEN :
			$title = Blorg::_('Open');
			break;

		case self::REPLY_STATUS_LOCKED :
			$title = Blorg::_('Locked');
			break;

		case self::REPLY_STATUS_MODERATED :
			$title = Blorg::_('Moderated');
			break;

		case self::REPLY_STATUS_CLOSED :
			$title = Blorg::_('Closed');
			break;

		default:
			$title = Blorg::_('Unknown Reply Status');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public static function getReplyStatuses()

	public static function getReplyStatuses()
	{
		return array(
			self::REPLY_STATUS_OPEN =>
				self::getReplyStatusTitle(self::REPLY_STATUS_OPEN),
			self::REPLY_STATUS_MODERATED =>
				self::getReplyStatusTitle(self::REPLY_STATUS_MODERATED),
			self::REPLY_STATUS_LOCKED =>
				self::getReplyStatusTitle(self::REPLY_STATUS_LOCKED),
			self::REPLY_STATUS_CLOSED =>
				self::getReplyStatusTitle(self::REPLY_STATUS_CLOSED),
		);
	}

	// }}}
	// {{{ public function getTitle()

	public function getTitle()
	{
		if ($this->title === null)
			return SwatString::ellipsizeRight(SwatString::condense(
				SwatString::toXHTML($this->bodytext)), 50, Blorg::_(' …'));
		else
			return $this->title;
	}

	// }}}
	// {{{ public function getVisibleReplies()

	public function getVisibleReplies()
	{
		$replies = array();

		foreach ($this->replies as $reply) {
			if ($reply->status == BlorgReply::STATUS_PUBLISHED &&
				!$reply->spam) {

				$replies[] = $reply;
			}
		}

		return $replies;
	}

	// }}}
	// {{{ public function hasVisibleReplyStatus()

	public function hasVisibleReplyStatus()
	{
		return ($this->reply_status == self::REPLY_STATUS_OPEN ||
			$this->reply_status == self::REPLY_STATUS_MODERATED ||
			($this->reply_status == self::REPLY_STATUS_LOCKED &&
			count($this->getVisibleReplies()) > 0));
	}

	// }}}
	// {{{ public function getVisibleFiles()

	/**
	 * Gets visible files for this post
	 *
	 * @return BlorgFileWrapper
	 */
	public function getVisibleFiles()
	{
		$sql = 'select BlorgFile.*
			from BlorgFile
			where BlorgFile.post = %s and BlorgFile.show = %s
			order by BlorgFile.createdate';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'),
			$this->db->quote(true, 'boolean'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('BlorgFileWrapper'));
	}

	// }}}
	// {{{ public function addTagsByShortname()

	public function addTagsByShortname(array $tag_names,
		SiteInstance $instance, $clear_existing_tags = false)
	{
		$this->checkDB();

		$instance_id = ($instance === null) ? null : $instance->id;

		$sql = sprintf('delete from BlorgPostTagBinding where post = %s',
			$this->db->quote($this->id, 'integer'));

		if (!$clear_existing_tags)
			$sql.= sprintf(' and tag in (select id from
				BlorgTag where shortname in (%s) and instance %s %s)',
				$this->db->datatype->implodeArray($tag_names, 'text'),
				SwatDB::equalityOperator($instance_id),
				$this->db->quote($instance_id, 'integer'));

		SwatDB::exec($this->db, $sql);

		$sql = sprintf('insert into BlorgPostTagBinding
			(post, tag) select %1$s, id from BlorgTag
			where shortname in (%2$s) and BlorgTag.instance %3$s %4$s',
			$this->db->quote($this->id, 'integer'),
			$this->db->datatype->implodeArray($tag_names, 'text'),
			SwatDB::equalityOperator($instance_id),
			$this->db->quote($instance_id, 'integer'));

		SwatDB::exec($this->db, $sql);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateProperty('createdate');
		$this->registerDateProperty('modified_date');
		$this->registerDateProperty('publish_date');

		$this->registerInternalProperty('author',
			SwatDBClassMap::get('BlorgAuthor'));

		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		$this->table = 'BlorgPost';
		$this->id_field = 'integer:id';
	}

	// }}}

	// loader methods
	// {{{ protected function loadReplies()

	/**
	 * Loads replies for this post, this never includes spam
	 *
	 * @return BlorgReplyWrapper
	 */
	protected function loadReplies()
	{
		$sql = 'select BlorgReply.*
			from BlorgReply
			where BlorgReply.post = %s and spam = %s
			order by createdate';

		$sql = sprintf($sql,
			$this->db->quote($this->id, 'integer'),
			$this->db->quote(false, 'boolean'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('BlorgReplyWrapper'));
	}

	// }}}
	// {{{ protected function loadTags()

	/**
	 * Loads tags for this post
	 *
	 * @return BlorgTagWrapper
	 */
	protected function loadTags()
	{
		$sql = 'select BlorgTag.*
			from BlorgTag
				inner join BlorgPostTagBinding on BlorgTag.id =
					BlorgPostTagBinding.tag
			where BlorgPostTagBinding.post = %s
			order by createdate';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('BlorgTagWrapper'));
	}

	// }}}
	// {{{ protected function loadFiles()

	/**
	 * Loads files for this post
	 *
	 * @return BlorgFileWrapper
	 */
	protected function loadFiles()
	{
		$sql = 'select BlorgFile.*
			from BlorgFile
			where BlorgFile.post = %s
			order by BlorgFile.createdate';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('BlorgFileWrapper'));
	}

	// }}}

	// saver methods
	// {{{ protected function saveReplies()

	/**
	 * Automatically saves replies on this post when this post is saved
	 */
	protected function saveReplies()
	{
		foreach ($this->replies as $reply)
			$reply->post = $this;

		$this->replies->setDatabase($this->db);
		$this->replies->save();
	}

	// }}}
}

?>
