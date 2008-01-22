<?php

require_once 'Swat/SwatDate.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Blorg/dataobjects/BlorgReplyWrapper.php';
require_once 'Blorg/dataobjects/BlorgTagWrapper.php';

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
	 * Replies are allowed, and automatically show on the site
	 */
	const REPLY_STATUS_OPEN      = 0;

	/**
	 * No New Replies are allowed, but exisiting replies are shown
	 */
	const REPLY_STATUS_LOCKED    = 1;

	/**
	 * Replies are allowed, but must be approved by an admin user before being
	 * shown
	 */
	const REPLY_STATUS_MODERATED = 2;

	/**
	 * No Replies are allowed, and existing replies are no longer shown
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
	 * Date of post - used for display and ordering by date.
	 *
	 * @var Date
	 */
	public $post_date;

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
	 * @param SwatDate $date the date the createdate of the post. Only the year
	 *                        and month are used for comparison.
	 * @param string $shortname the shortname of the post to load.
	 * @param SiteInstance $instance optional. The instance to load the post in.
	 *                               If the site does not use instances, this
	 *                               should be null.
	 *
	 * @return boolean true if this post was loaded from the given createdate
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
						convertTZ(createdate, %s)) =
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
			self::REPLY_STATUS_LOCKED =>
				self::getReplyStatusTitle(self::REPLY_STATUS_LOCKED),
			self::REPLY_STATUS_MODERATED =>
				self::getReplyStatusTitle(self::REPLY_STATUS_MODERATED),
			self::REPLY_STATUS_CLOSED =>
				self::getReplyStatusTitle(self::REPLY_STATUS_CLOSED),
		);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateProperty('createdate');
		$this->registerDateProperty('modified_date');
		$this->registerDateProperty('post_date');

		$this->registerInternalProperty('author',
			SwatDBClassMap::get('AdminUser'));

		$this->registerInternalProperty('instance',
			SwatDBClassMap::get('SiteInstance'));

		$this->table = 'BlorgPost';
		$this->id_field = 'integer:id';
	}

	// }}}
	// {{{ protected function loadReplies()

	/**
	 * Loads replies for this post
	 *
	 * @return BlorgReplyWrapper
	 */
	protected function loadReplies()
	{
		$sql = 'select BlorgReply.*
			from BlorgReply
			where BlorgReply.post = %s
			order by createdate';

		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));

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
}

?>
