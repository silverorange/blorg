<?php

require_once 'Swat/Swat.php';
require_once 'Site/Site.php';
require_once 'Admin/Admin.php';

/**
 * Container for package wide static methods
 *
 * @package   Blorg
 * @copyright 2008 silverorange
 */
class Blorg
{
	// {{{ constants

	/**
	 * The package identifier
	 */
	const PACKAGE_ID = 'Blorg';

	const GETTEXT_DOMAIN = 'blorg';

	// }}}
	// {{{ public static function _()

	public static function _($message)
	{
		return Blorg::gettext($message);
	}

	// }}}
	// {{{ public static function gettext()

	public static function gettext($message)
	{
		return dgettext(Blorg::GETTEXT_DOMAIN, $message);
	}

	// }}}
	// {{{ public static function ngettext()

	public static function ngettext($singular_message,
		$plural_message, $number)
	{
		return dngettext(Blorg::GETTEXT_DOMAIN,
			$singular_message, $plural_message, $number);
	}

	// }}}
	// {{{ public static function setupGettext()

	public static function setupGettext()
	{
		bindtextdomain(Blorg::GETTEXT_DOMAIN, '@DATA-DIR@/Blorg/locale');
		bind_textdomain_codeset(Blorg::GETTEXT_DOMAIN, 'UTF-8');
	}

	// }}}
	// {{{ public static function getDependencies()

	/**
	 * Gets the packages this package depends on
	 *
	 * @return array an array of package IDs that this package depends on.
	 */
	public static function getDependencies()
	{
		return array(Swat::PACKAGE_ID, Site::PACKAGE_ID, Admin::PACKAGE_ID);
	}

	// }}}
	// {{{ public static function getConfigDefinitions()

	/**
	 * Gets configuration definitions used by the Blorg package
	 *
	 * Applications should add these definitions to their config module before
	 * loading the application configuration.
	 *
	 * @return array the configuration definitions used by this package.
	 *
	 * @see SiteConfigModule::addDefinitions()
	 */
	public static function getConfigDefinitions()
	{
		return array(
			// TODO
		);
	}

	// }}}
	// {{{ private function __construct()

	/**
	 * Prevent instantiation of this static class
	 */
	private function __construct()
	{
	}

	// }}}
}

Blorg::setupGettext();
SwatUI::mapClassPrefixToPath('Blorg', 'Blorg');

SwatDBClassMap::addPath('Blorg/dataobjects');

?>
