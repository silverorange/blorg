<?php

require_once 'Site/admin/components/Comment/AjaxServer.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Blorg/dataobjects/BlorgComment.php';

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
	// {{{ protected function getComment()

	protected function getComment($comment_id)
	{
		$comment_class = SwatDBClassMap::get('BlorgComment');
		$comment = new $comment_class();
		$comment->setDatabase($this->app->db);
		if ($comment->load($comment_id, $this->app->getInstance())) {
			return $comment;
		} else {
			return null;
		}
	}

	// }}}
}

?>
