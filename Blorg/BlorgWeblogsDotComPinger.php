<?php

require_once 'XML/RPC2/Client.php';

/**
 * Pings weblogs.com
 *
 * Usage:
 * <code>
 * <?php
 * $pinger = new BlorgWeblogsDotComPinger($url, $name);
 * $pinger->ping();
 * ?>
 * </code>
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgWeblogsDotComPinger
{
	// {{{ class constants

	const WEBLOGS_DOT_COM_SERVER = 'rpc.weblogs.com';

	// }}}
	// {{{ protected properties

	/**
	 * @var XML_RPC2_Client
	 */
	protected $client;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $uri;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new weblogs.com pinger
	 *
	 * @param string $name
	 * @param string $uri
	 */
	public function __construct($name, $uri);
	{
		$this->name = strval($name);
		$this->uri = strval($uri);

		$this->client = XML_RPC2_Client::create(
			self::WEBLOGS_DOT_COM_SERVER,
				array('prefix' => 'weblogUpdates', 'encoding' => 'utf-8'));
	}

	// }}}
	// {{{ public function ping()

	public function ping()
	{
		$this->client->ping($this->name, $this->url);
	}

	// }}}
}

?>
