<?php

/**
 * Pings weblogs.com
 *
 * Usage:
 * <code>
 * <?php
 * $pinger = new BlorgWeblogsDotComPinger($app, $post, $base_href);
 * $pinger->ping();
 * ?>
 * </code>
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgWeblogsDotComPinger
{
	// {{{ class constants

	const WEBLOGS_DOT_COM_SERVER = 'http://rpc.weblogs.com/RPC2';

	// }}}
	// {{{ protected properties

	/**
	 * @var XML_RPC2_Client
	 */
	protected $client;

	/**
	 * @var SiteApplication
	 */
	protected $app;

	/**
	 * @var BlorgPost
	 */
	protected $post;

	/**
	 * @var string
	 */
	protected $base_href;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new weblogs.com pinger
	 *
	 * @param SiteApplication $app
	 * @param BlorgPost $post
	 * @param string $base_href optional. If not specified, the base href is
	 *                           build from the application.
	 */
	public function __construct(
		SiteApplication $app,
		BlorgPost $post,
		$base_href = null
	) {
		$this->app = $app;
		$this->post = $post;

		if ($base_href === null) {
			$this->base_href = $app->getBaseHref().$app->config->blorg->path;
		} else {
			$this->base_href = strval($base_href);
		}

		$this->client = XML_RPC2_Client::create(
			self::WEBLOGS_DOT_COM_SERVER,
				array('prefix' => 'weblogUpdates.', 'encoding' => 'utf-8'));
	}

	// }}}
	// {{{ public function ping()

	public function ping()
	{
		$site_title = $this->app->config->site->title;
		$site_uri   = $this->getSiteUri();
		$post_uri   = $this->getPostUri();
		$atom_uri   = $this->getAtomUri();
		$post_tags  = $this->getPostTags();

		$this->client->extendedPing($site_title, $site_uri, $post_uri,
			$atom_uri, $post_tags);
	}

	// }}}
	// {{{ protected function getSiteUri()

	protected function getSiteUri()
	{
		return $this->base_href;
	}

	// }}}
	// {{{ protected function getPostUri()

	protected function getPostUri()
	{
		$date = clone $this->post->publish_date;
		$date->convertTZ($this->app->default_time_zone);
		$year = $date->getYear();
		$month_name = BlorgPageFactory::$month_names[$date->getMonth()];

		return sprintf('%sarchive/%s/%s/%s',
			$this->base_href,
			$year,
			$month_name,
			$this->post->shortname);
	}

	// }}}
	// {{{ protected function getAtomUri()

	protected function getAtomUri()
	{
		return $this->base_href.'feed/atom';
	}

	// }}}
	// {{{ protected function getPostTags()

	/**
	 * Gets tags for the post delimited by '|' characters
	 *
	 * The total length of the returned string must be less than or equal to
	 * 1024 characters. All tags are included according to the tag display
	 * order unless adding a tag to the list pushes the list over 1024
	 * characters. In this situation, the next tag is tried until there are no
	 * more tags.
	 *
	 * @see http://www.weblogs.com/api.html#3
	 */
	protected function getPostTags()
	{
		$tags = array();

		$length = 0;
		foreach ($this->post->tags as $tag) {
			$tag_length = mb_strlen($tag->title);

			// account for delimiter character in total string length unless
			// this is the first tag
			if (count($tags) > 0) {
				$tag_length++;
			}

			if ($length + $tag_length <= 1024) {
				$tags[] = str_replace('|', '-', $tag->title);
				$length += $tag_length;
			}
		}

		return implode('|', $tags);
	}

	// }}}
}

?>
