<?php

require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Swat/SwatString.php';
require_once 'Blorg/dataobjects/BlorgAuthor.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

/**
 * A comment on a Blörg Post
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgComment extends SwatDBDataObject
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
	 * Fullname of person commenting
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * Link to display with the comment
	 *
	 * @var string
	 */
	public $link;

	/**
	 * Email address of the person commenting
	 *
	 * @var string
	 */
	public $email;

	/**
	 * The body of this comment
	 *
	 * @var string
	 */
	public $bodytext;

	/**
	 * Visibility status
	 *
	 * Set using class contstants:
	 * STATUS_PENDING - waiting on moderation
	 * STATUS_PUBLISHED - comment published on site
	 * STATUS_UNPUBLISHED - not shown on the site
	 *
	 * @var integer
	 */
	public $status;

	/**
	 * Whether or not this comment is spam
	 *
	 * @var boolean
	 */
	public $spam = false;

	/**
	 * IP Address of the person commenting
	 *
	 * @var string
	 */
	public $ip_address;

	/**
	 * User agent of the HTTP client used to comment
	 *
	 * @var string
	 */
	public $user_agent;

	/**
	 * Date this comment was created
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
			SwatDBClassMap::get('BlorgAuthor'));

		$this->table = 'BlorgComment';
		$this->id_field = 'integer:id';
	}

	// }}}
}

?>
