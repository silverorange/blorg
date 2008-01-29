<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePathPage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgPostFullView.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Displays all recent posts revers chronologically
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFrontPage extends SitePathPage
{
	// {{{ class constants

	const MAX_POSTS = 20;

	// }}}
	// {{{ protected properties

	/**
	 * @var BlorgPostWrapper
	 */
	protected $posts;

	// }}}
	// {{{ public function init()

	public function init()
	{
		$this->initPosts();
	}

	// }}}
	// {{{ public function build()

	public function build()
	{
		$this->getPath()->addEntriesToNavBar($this->layout->navbar);

		ob_start();
		$this->displayPosts();
		$this->layout->data->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function displayPosts()

	protected function displayPosts()
	{
		foreach ($this->posts as $post) {
			$view = new BlorgPostFullView($this->app, $post);
			$view->display(true);
		}
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts()
	{
		$instance_id = $this->app->instance->getId();

		$sql = sprintf('select * from BlorgPost
			where instance %s %s
				and enabled = true
			order by post_date desc limit %s',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'),
			$this->app->db->quote(self::MAX_POSTS, 'integer'));

		$wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$this->posts = SwatDB::query($this->app->db, $sql, $wrapper);
	}

	// }}}
}

?>
