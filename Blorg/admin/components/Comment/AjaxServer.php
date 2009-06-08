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
		return $this->app->getFrontendBaseHref().
			Blorg::getPostRelativeUri($this->app, $comment->post);
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
