<?php

require_once 'SwatDB/SwatDBClassMap.php';
require_once 'Site/pages/SitePage.php';
require_once 'Site/exceptions/SiteNotFoundException.php';
require_once 'Blorg/BlorgViewFactory.php';
require_once 'Blorg/dataobjects/BlorgPostWrapper.php';

/**
 * Displays all recent posts in reverse chronological order
 *
 * The constant MAX_POSTS determines how many posts are displayed on the page.
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class BlorgFrontPage extends SitePage
{
	// {{{ class constants

	const MAX_POSTS = 10;

	// }}}
	// {{{ protected properties

	/**
	 * @var BlorgPostWrapper
	 */
	protected $posts;

	// }}}
	// {{{ public function __construct()

	public function __construct(SiteApplication $app, SiteLayout $layout = null)
	{
		parent::__construct($app, $layout);
		$this->initPosts();
	}

	// }}}
	// {{{ protected function initPosts()

	protected function initPosts()
	{
		$instance_id = $this->app->instance->getId();

		$sql = sprintf('select * from BlorgPost
			where instance %s %s
				and enabled = true
			order by post_date desc',
			SwatDB::equalityOperator($instance_id),
			$this->app->db->quote($instance_id, 'integer'));

		$this->app->db->setLimit(self::MAX_POSTS);

		$wrapper = SwatDBClassMap::get('BlorgPostWrapper');
		$this->posts = SwatDB::query($this->app->db, $sql, $wrapper);
	}

	// }}}

	// build phase
	// {{{ public function build()

	public function build()
	{
		ob_start();
		$this->displayPosts();
		$this->layout->data->content = ob_get_clean();
	}

	// }}}
	// {{{ protected function displayPosts()

	protected function displayPosts()
	{
		$view = BlorgViewFactory::build('post', $this->app);
		foreach ($this->posts as $post) {
			$view->display($post);
		}
	}

	// }}}
}

?>
