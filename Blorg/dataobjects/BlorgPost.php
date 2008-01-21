<?php

require_once 'SwatDB/SwatDBDataObject.php';

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
	 * The status of replies on this post.
	 *
	 * @var integer
	 */
	public $reply_status;

	/**
	 * Whether or not the post is shown on the site.
	 *
	 * @var boolean
	 */
	public $show;

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
