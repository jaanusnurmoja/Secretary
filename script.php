<?php

/**
 * @version     3.2.0
 * @package     com_secretary
 *
 * @author       Fjodor Schaefer (schefa.com)
 * @copyright    Copyright (C) 2015-2017 Fjodor Schaefer. All rights reserved.
 * @license      MIT License
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 * 
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

JHTML::_('behavior.modal');

if (!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);
define('COM_SECRETARY_INSTALLER_PATH', __DIR__);
define('COM_SECRETARY_INSTALLER_ADMINPATH', __DIR__ . "/admin");
define('COM_SECRETARY_INSTALLER_SITEPATH', __DIR__ . "/site");

class com_secretaryInstallerScript
{
	protected $component_name;
	protected $extension_name;

	protected $messageInstall = "";
	protected $message = "";
	protected $minimumPHPVersion = '5.3.0';
	protected $minimumJoomlaVersion = '3.4.0';
	protected $installedVersion = '1.0.0';
	static protected $_helper = NULL;

	function __construct()
	{
		// Prepare installation
		$fileHelper = COM_SECRETARY_INSTALLER_ADMINPATH . "/application/install/helper.php";

		// get installed version
		$xmlPath = JPATH_ADMINISTRATOR . "/components/com_secretary/secretary.xml";
		if (file_exists($xmlPath)) {
			$xml = JFactory::getXML($xmlPath);
			$this->installedVersion = $xml->version;
		}

		if (file_exists($fileHelper)) {
			require_once $fileHelper;

			if (is_null(self::$_helper) && class_exists('SecretaryInstall'))
				self::$_helper = new SecretaryInstall();
		}
	}

	function install($parent)
	{
		$this->messageInstall .= '<div class="nextsteps">';
		$this->messageInstall .= '<h3>Next Steps</h3><ol>';
		$this->messageInstall .= '<li>Start your first business OR install sample data <a href="index.php?option=com_secretary" target="_blank">here</a></li>';
		$this->messageInstall .= '<li>Customize and save the System Configuration <a href="index.php?option=com_secretary&view=item&id=1&layout=edit&extension=settings" target="_blank">here</a></li>';
		$this->messageInstall .= '<li>Create your folders (e.g. invoices, quotes) for the area documents <a href="index.php?option=com_secretary&view=folders&extension=documents" target="_blank">here</a></li>';
		$this->messageInstall .= '<li>Create your first documents for the folder, that means: write your invoices, quotes etc. <a href="index.php?option=com_secretary&view=folders&extension=documents" target="_blank">here</a></li>';
		$this->messageInstall .= '</ol></div>';
	}

	function uninstall($parent)
	{
	}

	function preflight($type, $parent)
	{
		$version = new JVersion;
		if (version_compare($version->getShortVersion(), $this->minimumJoomlaVersion, '<')) {
			JLog::add("<h2>Secretary requires Joomla 3. Your version is too old.</h2>", JLog::WARNING, 'jerror');
			return false;
		}

		if (version_compare(phpversion(), $this->minimumPHPVersion, '<')) {
			JLog::add("<h2>Secretary requires PHP 5.3 or higher. Your PHP is too old.</h2>", JLog::WARNING, 'jerror');
			return false;
		}

		if (!is_null(self::$_helper) && $type == 'update') {
			self::$_helper->deleteFolder(JPATH_SITE . '/media/secretary/assets');
			self::$_helper->deleteFolder(JPATH_SITE . '/media/secretary/css', array('custom.css'));
			self::$_helper->deleteFolder(JPATH_SITE . '/media/secretary/fontawesome');
			self::$_helper->deleteFolder(JPATH_SITE . '/media/secretary/images');
			self::$_helper->deleteFolder(JPATH_SITE . '/media/secretary/js');
			self::$_helper->deleteFolder(JPATH_SITE . '/administrator/components/com_secretary/application');
			self::$_helper->deleteFolder(JPATH_SITE . '/administrator/components/com_secretary/assets');
			self::$_helper->deleteFolder(JPATH_SITE . '/administrator/components/com_secretary/controllers');
			self::$_helper->deleteFolder(JPATH_SITE . '/administrator/components/com_secretary/helpers');
			self::$_helper->deleteFolder(JPATH_SITE . '/administrator/components/com_secretary/models');
			self::$_helper->deleteFolder(JPATH_SITE . '/administrator/components/com_secretary/views');
			self::$_helper->deleteFolder(JPATH_SITE . '/components/com_secretary');
		}
	}

	function postflight($type, $parent)
	{
		$version = $parent->get('manifest')->version;

		$this->message .= self::$_helper->checkFolder();

		$this->message .= "<h4>Database</h4>";
		// Update Database
		switch ($type) {

			case 'update':

				// Update Database if installed version is lower 
				if ((version_compare($this->installedVersion, $version, '<'))) {
					$this->message .= self::$_helper->updateDatabase($version, $this->installedVersion);
				} else {
					$this->message .= '<p>' . JText::_('Database is up to date!') . '</p>';
				}

				// Major Changes
				if (version_compare($this->installedVersion, '2.0.4') <= 0) {
					self::$_helper->_update_2_0_5();
				}
				if (version_compare($this->installedVersion, '3.1.9') <= 0) {
					self::$_helper->_update_3_2_0();
				}

				break;

			case 'install':
				$this->message .= JText::_('install.' . self::$_helper->getDbType() . '.sql executed<br>');
				$this->message .= self::$_helper->updateDatabase($version, NULL, '2.5.0');
				break;

			default:
				break;
		}

		self::$_helper->message($version, $this->message, $this->messageInstall);
	}
}