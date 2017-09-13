<?php

/**
 * Index page for Authors
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFrontIndex extends AdminPage
{
	// {{{ class constants

	const MAX_COMMENTS = 5;
	const MAX_POSTS    = 10;

	// }}}
	// {{{ protected properties

	/**
	 * @var string
	 */
	protected $ui_xml = __DIR__.'/index.xml';

	/**
	 * @var array
	 */
	protected $comments;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);

		$this->initComments();
		$this->initCommentReplicator();
	}

	// }}}
	// {{{ protected function initComments()

	protected function initComments()
	{
		$instance_id = $this->app->getInstanceId();

		// load recent comments
		$sql = sprintf('select BlorgComment.* from BlorgComment
			inner join BlorgPost on BlorgComment.post = BlorgPost.id and
				BlorgPost.instance %s %s
			where BlorgComment.spam = %s
			order by BlorgComment.createdate desc',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(false, 'boolean'));

		$this->app->db->setLimit(self::MAX_COMMENTS);

		$wrapper = SwatDBClassMap::get('BlorgCommentWrapper');
		$comments = SwatDB::query($this->app->db, $sql, $wrapper);

		// efficiently load posts for all comments
		$instance_id = $this->app->getInstanceId();
		$post_sql = sprintf('select id, title, bodytext
			from BlorgPost
			where instance %s %s and id in (%%s)',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$post_wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$comments->loadAllSubDataObjects('post', $this->app->db, $post_sql,
			$post_wrapper);

		$this->comments = array();
		foreach ($comments as $comment) {
			$this->comments[$comment->id] = $comment;
		}
	}

	// }}}
	// {{{ protected function initCommentReplicator()

	protected function initCommentReplicator()
	{
		$comment_display = $this->ui->getWidget('comment');
		$comment_display->setApplication($this->app);

		$replicator = $this->ui->getWidget('comment_replicator');
		$replicator->replication_ids = array_keys($this->comments);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildInfo();
		$this->buildPostsView();
		$this->buildCommentReplicator();
	}

	// }}}
	// {{{ protected function buildInfo()

	protected function buildInfo()
	{
		$locale = SwatI18NLocale::get();
		$info = $this->getInfo();

		ob_start();

		echo '<ul>';

		$li_tag = new SwatHtmlTag('li');
		$li_tag->setContent(sprintf(Blorg::_('%s posts tagged %s times'),
			$locale->formatNumber($info['post_count'], 0),
			$locale->formatNumber($info['tag_count'], 0)));

		$li_tag->display();

		$li_tag = new SwatHtmlTag('li');
		$li_tag->setContent(sprintf(Blorg::_('%s posts / month'),
			$locale->formatNumber($info['posts_per_month'], 2)));

		$li_tag->display();

		echo '</ul><ul>';

		$li_tag = new SwatHtmlTag('li');
		$li_tag->setContent(sprintf(Blorg::_('%s comments'),
			$locale->formatNumber($info['comment_count'], 0)));

		$li_tag->display();

		$li_tag = new SwatHtmlTag('li');
		$li_tag->setContent(sprintf(Blorg::_('%s comments / day'),
			$locale->formatNumber($info['comments_per_day'], 2)));

		$li_tag->display();

		echo '</ul>';

		// author warning
		if ($this->getVisibleAuthorCount() === 0) {
			$this->ui->getWidget('new_post')->visible = false;
			$this->ui->getWidget('manage_authors')->visible = true;

			echo '<p><strong>', Blorg::_('Warning:'), '</strong> ';
			echo Blorg::_(
				'At least one author must be set to “show on site” in '.
				'order to create new posts.');

			echo '</p>';
		}

		$this->ui->getWidget('info')->content = ob_get_clean();
		$this->ui->getWidget('info')->content_type = 'text/xml';
	}

	// }}}
	// {{{ protected function buildNavBar()

	protected function buildNavBar()
	{
		$this->layout->navbar->popEntry();
	}

	// }}}
	// {{{ protected function buildPostsView()

	protected function buildPostsView()
	{
		$view = $this->ui->getWidget('posts_view');
		$view->model = $this->getPostsTableModel();
	}

	// }}}
	// {{{ protected function buildCommentReplicator()

	protected function buildCommentReplicator()
	{
		$comment_replicator = $this->ui->getWidget('comment_replicator');
		foreach ($comment_replicator->replication_ids as $id) {
			$comment_display = $comment_replicator->getWidget('comment', $id);
			$comment_display->setComment($this->comments[$id]);
		}
	}

	// }}}
	// {{{ protected function getPostsTableModel()

	protected function getPostsTableModel()
	{
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf(
			'select id, title, publish_date, bodytext
			from BlorgPost
			where instance %s %s and enabled = %s
			order by publish_date desc, title',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$this->app->db->setLimit(self::MAX_POSTS);
		$posts = SwatDB::query($this->app->db, $sql, 'BlorgPostWrapper');

		$store = new SwatTableStore();

		foreach ($posts as $post) {
			$ds = new SwatDetailsStore($post);
			$ds->title = $post->getTitle();
			$store->add($ds);
		}

		return $store;
	}

	// }}}
	// {{{ protected function getInfo()

	protected function getInfo()
	{
		$instance_id = $this->app->getInstanceId();

		$sql = sprintf('select count(1) as post_count,
			sum(visible_comment_count) as comment_count,
			min(publish_date) as start_date from BlorgPost
				left outer join BlorgPostVisibleCommentCountView as v on
					BlorgPost.id = v.post and BlorgPost.instance = v.instance
			where BlorgPost.instance %s %s and BlorgPost.enabled = %s and
				BlorgPost.publish_date is not null',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		$post_info = SwatDB::queryRow($this->app->db, $sql,
			array('integer', 'integer', 'date'));

		$sql = sprintf('select sum(post_count) as tag_count from
			BlorgTagVisiblePostCountView as v
				inner join BlorgTag on BlorgTag.id = v.tag
			where instance %s %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$tag_info = SwatDB::queryRow($this->app->db, $sql,
			array('integer'));

		$start_date = new SwatDate($post_info->start_date);
		$now = new SwatDate();

		$start_month = $start_date->getMonth();
		$end_month   = $now->getMonth();
		$start_year  = $start_date->getYear();
		$end_year    = $now->getYear();

		if ($start_month > $end_month) {
			$end_month += 12;
			$end_year--;
		}
		$months = ($end_month - $start_month) + ($end_year - $start_year) * 12;

		$days = $now->diff($start_date)->days;

		$posts_per_month = ($months == 0) ?
			0 : $post_info->post_count / $months;

		$comments_per_day = ($days == 0) ?
			0 : $post_info->comment_count / $days;

		return array(
			'post_count'       => $post_info->post_count,
			'posts_per_month'  => $posts_per_month,
			'comment_count'    => $post_info->comment_count,
			'comments_per_day' => $comments_per_day,
			'tag_count'        => $tag_info->tag_count,
		);
	}

	// }}}
	// {{{ protected function getVisibleAuthorCount()

	protected function getVisibleAuthorCount()
	{
		$instance_id = $this->app->getInstanceId();
		$sql = sprintf('select count(1) from BlorgAuthor
			where instance %s %s and visible = %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(true, 'boolean'));

		return SwatDB::queryOne($this->app->db, $sql);
	}

	// }}}

	// finalize phase
	// {{{ public function finalize()

	public function finalize()
	{
		parent::finalize();
		$this->layout->addHtmlHeadEntry(new SwatStyleSheetHtmlHeadEntry(
			'packages/blorg/admin/styles/blorg-front-page.css',
			Blorg::PACKAGE_ID));
	}

	// }}}
}

?>
