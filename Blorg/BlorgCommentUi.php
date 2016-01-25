<?php

require_once 'Site/SiteCommentUi.php';
require_once 'Blorg/BlorgPageFactory.php';

/**
 * Blorg comment UI
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentUi extends SiteCommentUi
{
	// {{{ protected function getView()

	protected function getView()
	{
		return SiteViewFactory::get($this->app, 'post-comment');
	}

	// }}}
	// {{{ protected function getComment()

	protected function getComment()
	{
		$class_name = SwatDBClassMap::get('BlorgComment');
		return new $class_name();
	}

	// }}}
	// {{{ protected function setCommentPost()

	protected function setCommentPost(SiteComment $comment,
		SiteCommentStatus $post)
	{
		$comment->post = $post;
	}

	// }}}
	// {{{ protected function getPermalink()

	protected function getPermalink(SiteComment $comment)
	{
		return $this->app->getBaseHref().
			Blorg::getPostRelativeUri($this->app, $comment->post);

	}

	// }}}
}

?>
