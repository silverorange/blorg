<?php

require_once 'Site/admin/SiteCommentDisplay.php';

/**
 * Displays a comment with optional buttons to edit, set published status
 * delete and mark as spam
 *
 * @package   Blörg
 * @copyright 2008-2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgCommentDisplay extends SiteCommentDisplay
{
	// {{{ protected function displayHeader()

	protected function displayHeader()
	{
		$header_div = new SwatHtmlTag('div');
		$header_div->class = 'site-comment-display-header';
		$header_div->open();

		$anchor_tag = new SwatHtmlTag('a');
		$anchor_tag->href = sprintf('Post/Details?id=%s',
			$this->comment->post->id);

		$anchor_tag->setContent($this->comment->post->getTitle());
		echo sprintf(Blorg::_('Comment on %s'), $anchor_tag);

		$this->displayStatusSpan();

		$header_div->close();
	}

	// }}}
	// {{{ protected function getView()

	protected function getView()
	{
		if ($this->view === null && $this->app !== null) {
			$this->view = SiteViewFactory::get($this->app, 'post-comment');
			$this->view->setPartMode('bodytext', SiteView::MODE_SUMMARY);
			$this->view->setPartMode('permalink', SiteView::MODE_ALL, false);
			$this->view->setPartMode('author', SiteView::MODE_ALL, false);
			$this->view->setPartMode('link', SiteView::MODE_ALL, false);
		}

		return $this->view;
	}

	// }}}
}

?>
