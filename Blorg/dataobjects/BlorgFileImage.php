<?php

require_once 'Site/dataobjects/SiteImage.php';

/**
 * An image attached to a BlorgFile
 *
 * @package   Blörg
 * @copyright 2008-2016 silverorange
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
}
