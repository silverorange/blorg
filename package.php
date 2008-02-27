<?php

require_once 'PEAR/PackageFileManager2.php';

$version = '0.0.13';
$notes = <<<EOT
see ChangeLog
EOT;

$description =<<<EOT
Blorg!
EOT;

$package = new PEAR_PackageFileManager2();
PEAR::setErrorHandling(PEAR_ERROR_DIE);

$result = $package->setOptions(
	array(
		'filelistgenerator' => 'svn',
		'simpleoutput'      => true,
		'baseinstalldir'    => '/',
		'packagedirectory'  => './',
		'dir_roles'         => array(
			'Blorg' => 'php',
			'locale' => 'data',
			'www' => 'data',
		),
	)
);

$package->setPackage('Blorg');
$package->setSummary('Blorg!');
$package->setDescription($description);
$package->setChannel('pear.silverorange.com');
$package->setPackageType('php');
$package->setLicense('private', 'http://www.silverorange.com/');

$package->setReleaseVersion($version);
$package->setReleaseStability('stable');
$package->setAPIVersion('0.0.1');
$package->setAPIStability('stable');
$package->setNotes($notes);

$package->addIgnore('package.php');

$package->addMaintainer('lead', 'gauthierm', 'Mike Gauthier', 'mike@silverorange.com');

$package->addReplacement('Blorg/Blorg.php', 'pear-config', '@DATA-DIR@', 'data_dir');

$package->setPhpDep('5.1.5');
$package->setPearinstallerDep('1.4.0');
$package->addPackageDepWithChannel('required', 'Swat', 'pear.silverorange.com', '1.3.2');
$package->addPackageDepWithChannel('required', 'Site', 'pear.silverorange.com', '1.2.4');
$package->addPackageDepWithChannel('required', 'Admin', 'pear.silverorange.com', '1.2.1');
$package->addPackageDepWithChannel('required', 'XML_RPC2', 'pear.silverorange.com', '1.0.1so1');
$package->generateContents();

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
	$package->writePackageFile();
} else {
	$package->debugPackageFile();
}

?>
