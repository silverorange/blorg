<?php

require_once 'Swat/SwatDetailsStore.php';
require_once 'Admin/pages/AdminPage.php';
require_once 'Blorg/dataobjects/BlorgFile.php';
require_once 'Site/SiteCommentStatus.php';

/**
 * Shows editable configuration values for a Blörg site
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgConfigIndex extends AdminPage
{
	// {{{ protected properties

	protected $ui_xml = 'Blorg/admin/components/Config/index.xml';

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->ui->loadFromXML($this->ui_xml);
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		parent::buildInternal();
		$this->buildMessages();
		$this->buildSiteSettingsView();
		$this->buildAdSettingsView();
	}

	// }}}
	// {{{ protected function buildSiteSettingsView()

	protected function buildSiteSettingsView()
	{
		$setting_keys = array(
			'site' => array(
				'title',
				'tagline',
				'meta_description',
			),
			'blorg' => array(
				'header_image',
				'feed_logo',
				'default_comment_status',
				'visual_editor',
			),
			'comment' => array(
				'akismet_key',
			),
			'date' => array(
				'time_zone',
			),
			'analytics' => array(
				'google_account',
			),
		);

		$ds = new SwatDetailsStore();

		foreach ($setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;

				$details_method = 'buildDetails'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $details_method)) {
					$this->$details_method($ds);
				} else {
					$ds->$field_name = $this->app->config->$section->$name;
				}
			}
		}

		$view = $this->ui->getWidget('config_settings_view');
		$view->data = $ds;
	}

	// }}}
	// {{{ protected function buildAdSettingsView()

	protected function buildAdSettingsView()
	{
		$setting_keys = array(
			'blorg' => array(
				'ad_bottom',
				'ad_top',
				'ad_post_content',
				'ad_post_comments',
				'ad_referers_only',
			),
		);

		$ds = new SwatDetailsStore();

		foreach ($setting_keys as $section => $keys) {
			foreach ($keys as $name) {
				$field_name = $section.'_'.$name;

				$details_method = 'buildDetails'.str_replace(' ', '',
					ucwords(str_replace('_', ' ', $field_name)));

				if (method_exists($this, $details_method)) {
					$this->$details_method($ds);
				} else {
					$ds->$field_name = $this->app->config->$section->$name;
				}
			}
		}

		$view = $this->ui->getWidget('ad_settings_view');
		$view->data = $ds;
	}

	// }}}
	// {{{ protected function buildDetailsBlorgHeaderImage()

	protected function buildDetailsBlorgHeaderImage(SwatDetailsStore $ds)
	{
		$ds->blorg_header_image = '';
		$ds->has_blorg_header_image = false;

		$header_image = $this->app->config->blorg->header_image;
		if ($header_image != '') {
			$class = SwatDBClassMap::get('BlorgFile');
			$file = new $class();
			$file->setDatabase($this->app->db);
			if ($file->load(intval($header_image))) {
				$path = $file->getRelativeUri('../');
				$ds->blorg_header_image = $path;
				$ds->has_blorg_header_image = true;
			}
		}
	}

	// }}}
	// {{{ protected function buildDetailsBlorgHeaderImage()

	protected function buildDetailsBlorgFeedLogo(SwatDetailsStore $ds)
	{
		$ds->blorg_feed_logo = '';
		$ds->has_blorg_feed_logo = false;

		$header_image = $this->app->config->blorg->feed_logo;
		if ($header_image != '') {
			$class = SwatDBClassMap::get('BlorgFile');
			$file = new $class();
			$file->setDatabase($this->app->db);
			if ($file->load(intval($header_image))) {
				$path = $file->getRelativeUri('../');
				$ds->blorg_feed_logo = $path;
				$ds->has_blorg_feed_logo = true;
			}
		}
	}

	// }}}
	// {{{ protected function buildDetailsBlorgAdTop()

	protected function buildDetailsBlorgAdTop(SwatDetailsStore $ds)
	{
		$ds->blorg_ad_top = ($this->app->config->blorg->ad_top != '');
	}

	// }}}
	// {{{ protected function buildDetailsBlorgAdBottom()

	protected function buildDetailsBlorgAdBottom(SwatDetailsStore $ds)
	{
		$ds->blorg_ad_bottom = ($this->app->config->blorg->ad_bottom != '');
	}

	// }}}
	// {{{ protected function buildDetailsBlorgAdPostContent()

	protected function buildDetailsBlorgAdPostContent(SwatDetailsStore $ds)
	{
		$ds->blorg_ad_post_content =
			($this->app->config->blorg->ad_post_content != '');
	}

	// }}}
	// {{{ protected function buildDetailsBlorgAdPostComments()

	protected function buildDetailsBlorgAdPostComments(SwatDetailsStore $ds)
	{
		$ds->blorg_ad_post_comments =
			($this->app->config->blorg->ad_post_comments != '');
	}

	// }}}
	// {{{ protected function buildDetailsBlorgDefaultCommentStatus()

	protected function buildDetailsBlorgDefaultCommentStatus(
		SwatDetailsStore $ds)
	{
		switch ($this->app->config->blorg->default_comment_status) {
		case 'open':
			$value = SiteCommentStatus::OPEN;
			break;

		case 'moderated':
			$value = SiteCommentStatus::MODERATED;
			break;

		case 'locked':
			$value = SiteCommentStatus::LOCKED;
			break;

		case 'closed':
		default:
			$value = SiteCommentStatus::CLOSED;
			break;
		}

		$title = BlorgPost::getCommentStatusTitle($value);
		$ds->blorg_default_comment_status = $title;
	}

	// }}}
}

?>
