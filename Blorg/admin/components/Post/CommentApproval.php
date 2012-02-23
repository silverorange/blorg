<?php

require_once 'Blorg/dataobjects/BlorgComment.php';
require_once 'Blorg/BlorgPageFactory.php';
require_once 'Site/admin/SiteCommentApprovalPage.php';

/**
 * Index page for Posts
 *
 * @package   Blörg
 * @copyright 2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostCommentApproval extends SiteCommentApprovalPage
{
	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();

		$this->navbar->createEntry(Blorg::_('Comment Approval'));
		$this->ui->getWidget('frame')->title = Blorg::_('Comment Approval');
	}

	// }}}
	// {{{ protected function initDataObject()

	protected function initDataObject($id)
	{
		$class_name = SwatDBClassMap::get('BlorgComment');
		$this->data_object = new $class_name();
		$this->data_object->setDatabase($this->app->db);

		if (!$this->data_object->load($id))
			throw new AdminNotFoundException(
				sprintf('Comment with id ‘%s’ not found.', $id));
	}

	// }}}
	// {{{ protected function getPendingIds()

	protected function getPendingIds()
	{
		$sql = sprintf('select id from BlorgComment
			where status = %s and spam = %s
			order by createdate asc',
			$this->app->db->quote(SiteComment::STATUS_PENDING, 'integer'),
			$this->app->db->quote(false, 'boolean'));

		$rows = SwatDB::query($this->app->db, $sql);

		$ids = array();
		foreach ($rows as $row)
			$ids[] = $row->id;

		return $ids;
	}

	// }}}

	// build phase
	// {{{ protected function displayContent()

	protected function displayContent()
	{
		$comment = $this->data_object;

		$div_tag = new SwatHtmlTag('div');
		$div_tag->setContent($this->data_object->post->getTitle());
		$div_tag->display();

		$h2_tag = new SwatHtmlTag('h2');
		$h2_tag->setContent($this->data_object->fullname);
		$h2_tag->display();

		$abbr_tag = new SwatHtmlTag('abbr');
		$date = clone $comment->createdate;
		$date->convertTZ($this->app->default_time_zone);
		$abbr_tag->setContent(sprintf(Blorg::_('Posted: %s'),
			$date->formatLikeIntl(SwatDate::DF_DATE)));

		$abbr_tag->display();

		echo SwatString::toXHTML($comment->bodytext);
	}

	// }}}
}

?>
