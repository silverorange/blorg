<?php

require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A reply to a Blörg Post
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 */
class BlorgReply extends SwatDBDataObject
{
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
	 * Whether or not to show the reply.
	 *
	 * @var boolean
	 */
	public $show;

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
