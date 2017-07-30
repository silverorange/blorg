<?php

require_once 'Site/admin/components/InstanceSetting/include/SiteAbstractConfigPage.php';
require_once 'Site/admin/SiteCommentStatusSlider.php';
require_once 'Site/SiteCommentStatus.php';
require_once 'Blorg/dataobjects/BlorgPost.php';

/**
 * Advertising Blörg instance settings
 *
 * @package   Blörg
 * @copyright 2010-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgPostConfigPage extends SiteAbstractConfigPage
{
	// {{{ protected properties

	/**
	 * @var array
	 */
	protected $comment_status_map = array(
		'open'      => SiteCommentStatus::OPEN,
		'moderated' => SiteCommentStatus::MODERATED,
		'locked'    => SiteCommentStatus::LOCKED,
		'closed'    => SiteCommentStatus::CLOSED,
	);

	// }}}
	// {{{ public function getPageTitle()

	public function getPageTitle()
	{
		return Blorg::_('Post Editing');
	}

	// }}}
	// {{{ public function getConfigSettings()

	public function getConfigSettings()
	{
		return array(
			'blorg' => array(
				'visual_editor',
				'default_comment_status',
			),
		);
	}

	// }}}
	// {{{ protected function initBlorgDefaultCommentStatus()

	protected function initBlorgDefaultCommentStatus()
	{
		$status = $this->ui->getWidget('blorg_default_comment_status');

		// open
		$option = new SwatOption(SiteCommentStatus::OPEN,
			BlorgPost::getCommentStatusTitle(SiteCommentStatus::OPEN));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can be added by anyone and are immediately visible on '.
			'this post.'));

		// moderated
		$option = new SwatOption(SiteCommentStatus::MODERATED,
			BlorgPost::getCommentStatusTitle(
				SiteCommentStatus::MODERATED));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can be added by anyone but must be approved by a site '.
			'author before being visible on this post.'));

		// locked
		$option = new SwatOption(SiteCommentStatus::LOCKED,
			BlorgPost::getCommentStatusTitle(SiteCommentStatus::LOCKED));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can only be added by an author. Existing comments are '.
			'still visible on this post.'));

		// closed
		$option = new SwatOption(SiteCommentStatus::CLOSED,
			BlorgPost::getCommentStatusTitle(SiteCommentStatus::CLOSED));

		$status->addOption($option);
		$status->addContextNote($option, Blorg::_(
			'Comments can only be added by an author. No comments are visible '.
			'on this post.'));
	}

	// }}}
	// {{{ protected function loadBlorgDefaultCommentStatus()

	protected function loadBlorgDefaultCommentStatus(
		SiteConfigModule $config,
		SwatWidget $widget
	) {
		$value  = $config->blorg->default_comment_status;

		switch ($value) {
		case 'open':
			$widget->value = SiteCommentStatus::OPEN;
			break;

		case 'moderated':
			$widget->value = SiteCommentStatus::MODERATED;
			break;

		case 'locked':
			$widget->value = SiteCommentStatus::LOCKED;
			break;

		case 'closed':
		default:
			$widget->value = SiteCommentStatus::CLOSED;
			break;
		}
	}

	// }}}
	// {{{ protected function saveBlorgDefaultCommentStatus()

	protected function saveBlorgDefaultCommentStatus(
		SiteConfigModule $config,
		SwatWidget $widget
	) {
		$saved = false;

		$value = array_search($widget->value, $this->comment_status_map, true);
		if ($config->blorg->default_comment_status !== $value) {
			$config->blorg->default_comment_status = $value;
			$saved = true;
		}

		return $saved;
	}

	// }}}
	// {{{ protected function getUiXml()

	protected function getUiXml()
	{
		return dirname(__FILE__).'/post-config-page.xml';
	}

	// }}}
}

?>
