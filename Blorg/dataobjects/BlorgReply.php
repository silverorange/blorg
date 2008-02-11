<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Swat/SwatString.php';

/**
 * A reply to a Blörg Post
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgReply extends SwatDBDataObject
{
	// {{{ constants

	const STATUS_PENDING     = 0;
	const STATUS_PUBLISHED   = 1;
	const STATUS_UNPUBLISHED = 2;

	// }}}
	// {{{ public properties

	/**
	 * Unique Identifier
	 *
	 * @var integer
	 */
	public $id;

	/**
	 * Fullname of person replying.
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * Link to display with the reply.
	 *
	 * @var string
	 */
	public $link;

	/**
	 * Email address of the person replying.
	 *
	 * @var string
	 */
	public $email;

	/**
	 * The body of the reply.
	 *
	 * @var string
	 */
	public $bodytext;

	/**
	 * Visibility status
	 *
	 * Set using class contstants:
	 * STATUS_PENDING - waiting on moderation
	 * STATUS_PUBLISHED - reply published on site
	 * STATUS_UNPUBLISHED - not shown on the site
	 *
	 * @var integer
	 */
	public $status;

	/**
	 * Whether or not this reply is spam
	 *
	 * @var boolean
	 */
	public $spam = false;

	/**
	 * IP Address of the person replying.
	 *
	 * @var string
	 */
	public $ip_address;

	/**
	 * User Agent of the Browser used to reply.
	 *
	 * @var string
	 */
	public $user_agent;

	/**
	 * Date of the reply
	 *
	 * @var Date
	 */
	public $createdate;

	// }}}
	// {{{ public static function getStatusTitle()

	public static function getStatusTitle($status)
	{
		switch ($status) {
		case self::STATUS_PENDING :
			$title = Blorg::_('Pending Approval');
			break;

		case self::STATUS_PUBLISHED :
			$title = Blorg::_('Shown on Site');
			break;

		case self::STATUS_UNPUBLISHED :
			$title = Blorg::_('Not Approved');
			break;

		default:
			$title = Blorg::_('Unknown Status');
			break;
		}

		return $title;
	}

	// }}}
	// {{{ public static function getBodytextXhtml()

	public static function getBodytextXhtml($bodytext)
	{
		$bodytext = str_replace('%', '%%', $bodytext);

		$allowed_tags = '/(<a href="http[^"]+?">|<\/a>|<\/?strong>|<\/?em>)/ui';
		$matches = array();
		preg_match_all($allowed_tags, $bodytext, $matches);
		$bodytext = preg_replace($allowed_tags, '%s', $bodytext);

		$bodytext = SwatString::minimizeEntities($bodytext);
		$bodytext = vsprintf($bodytext, $matches[0]);
		$bodytext = SwatString::toXHTML($bodytext);

		return $bodytext;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->registerDateProperty('createdate');

		$this->registerInternalProperty('post',
			SwatDBClassMap::get('BlorgPost'));

		$this->registerInternalProperty('author',
			SwatDBClassMap::get('AdminUser'));

		$this->table = 'BlorgReply';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
