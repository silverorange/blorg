<?

require_once 'Site/dataobjects/SiteImage.php';

/**
 * An image attached to a BlorgFile
 *
 * @package   Blörg
 * @copyright 2008 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 *
 * @see BlorgFile
 */
class BlorgFileImage extends SiteImage
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();

		$this->image_set_shortname = 'files';
	}

	// }}}
	// {{{ public static function getHeaderDirectory()

	public static function getHeaderDirectory($mime_type)
	{
		switch ($mime_type) {
			case 'image/jpeg':
				$shortname = 'header_jpg';
				break;
			case 'image/png':
				$shortname = 'header_png';
				break;
			case 'image/gif':
				$shortname = 'header_gif';
				break;
		}

		return $shortname;
	}

	// }}}
}
