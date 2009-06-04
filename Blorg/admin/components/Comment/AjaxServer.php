<?php

require_once 'Site/admin/components/Comment/AjaxServer.php';
require_once 'Blorg/BlorgPageFactory.php';

/**
 * Performs actions on comments via AJAX
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentAjaxServer extends SiteCommentAjaxServer
{
	// {{{ protected function getPermalink()

	protected function getPermalink(SiteComment $comment)
	{
		$uri = $this->app->getFrontendBaseHref().
			$this->app->config->blorg->path;

		$date = clone $comment->post->publish_date;
		$date->convertTZ($this->app->default_time_zone);

		$permalink = sprintf('%sarchive/%s/%s/%s',
			$uri,
			$date->getYear(),
			BlorgPageFactory::$month_names[$date->getMonth()],
			$comment->post->shortname);

	}

	// }}}
	// {{{ protected function flushCache()

	protected function flushCache()
	{
		if (isset($this->app->memcache)) {
			$this->app->memcache->flushNs('posts');
		}
	}

	// }}}
}

?>
