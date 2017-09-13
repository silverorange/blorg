<?php

/**
 * Proxy page for AJAX requests
 *
 * This page is used to circumvent the cross-domain restrictions in browser
 * JavaScript. It may also get caching in the future.
 *
 * Only HTTP GET requests are proxied.
 *
 * To add a new URI to be proxies, use the {@link BlorgAjaxProxyPage::map()}
 * method. Proxy mappings may be automatically added by gadgets. See the
 * {@link SiteGadget::defineAjaxProxyMapping()} method for details.
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgAjaxProxyPage extends SitePage
{
	// {{{ protected properties

	/**
	 * Mapping of source string to Web-service destination URIs
	 *
	 * @var array
	 */
	protected static $uri_map = array();

	/**
	 * The proxied URI of this page
	 *
	 * @var string
	 */
	protected $uri;

	// }}}
	// {{{ public function __construct()

	public function __construct(
		SiteApplication $app,
		SiteLayout $layout = null,
		array $arguments = array()
	) {
		$layout = new SiteLayout($app, BlorgAjaxProxyTemplate::class);
		parent::__construct($app, $layout, $arguments);

		$this->initUri($this->getArgument('source'));
	}

	// }}}
	// {{{ protected function getArgumentMap()

	protected function getArgumentMap()
	{
		return array(
			'source' => array(0, null),
		);
	}

	// }}}
	// {{{ public static function map()

	/**
	 * Maps a source string pattern to a Web-service URI
	 *
	 * The <code>$from</code> string is a regular expression sans-delimiters.
	 * It may contain capturing subpatterns and the captured patterns may
	 * be included in the <code>$to</code> string using <code>\1</code> for the
	 * first captured subpattern, <code>\2</code> for the second, etc.
	 *
	 * The following example maps Last.fm sources to the Last.fm recent
	 * tracks Web-service:
	 *
	 * <code>
	 * $from = '^last\.fm/([^/]+)$';
	 * $to   = 'http://ws.audioscrobbler.com/1.0/user/\1/recenttracks.xml';
	 * </code>
	 *
	 * Using this mapping, the URI
	 * <strong>http://myblorg.example.com/ajax/last.fm/joeuser</strong> will
	 * be a proxy for the page at
	 * <strong>http://ws.audioscrobbler.com/1.0/user/joeuser/recenttracks.xml</strong>.
	 *
	 * @param string $from the source string from which the <code>$to</code> is
	 *                      mapped.
	 * @param string $to the URI to which to map the source string. This may
	 *                    contain regular expression replacement markers of
	 *                    the form <code>\1</code>, <code>\2</code>, etc.
	 */
	public static function map($from, $to)
	{
		self::$uri_map[$from] = $to;
	}

	// }}}
	// {{{ protected function initUri()

	protected function initUri($source)
	{
		$map = self::$uri_map;
		$gadgets = SiteGadgetFactory::getAvailable($this->app);
		foreach ($gadgets as $gadget) {
			$map = array_merge($map, $gadget->ajax_proxy_map);
		}

		foreach ($map as $from => $to) {
			// escape delimiters
			$pattern = str_replace('@', '\@', $from);
			$regexp = '@'.$pattern.'@u';
			if (preg_match($regexp, $source) === 1) {
				// replace matches in to string
				$this->uri = preg_replace($regexp, $to, $source);
				break;
			}
		}

		if ($this->uri === null) {
			throw new SiteNotFoundException('Page not found.');
		}
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		$curl = curl_init($this->uri);

		$this->layout->startCapture('content');

		if (curl_exec($curl) !== false) {
			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			if ($code > 199 && $code < 300) {
				$content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
				$content_length = curl_getinfo($curl,
					CURLINFO_CONTENT_LENGTH_DOWNLOAD);

				header('Content-type: '.$content_type);
				header('Content-length: '.$content_length);
			}
		}

		$this->layout->endCapture();

		curl_close($curl);
	}

	// }}}
}

?>
