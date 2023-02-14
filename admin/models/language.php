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
 */

// No direct access
defined('_JEXEC') or die;

use Joomla\Registry\Registry;

jimport('joomla.application.component.modeladmin');

class SecretaryModelLanguage extends JModelAdmin
{
	protected $app;

	public function __construct($config = array())
	{
		// Only admin
		if (!\Secretary\Joomla::getUser()->authorise('core.admin', 'com_secretary'))
			die;

		$this->app = \Secretary\Joomla::getApplication();
		parent::__construct($config);
	}

	protected function populateState($ordering = null, $direction = null)
	{
		$search = $this->app->getUserStateFromRequest('com_secretary.filter_search', 'filter_search', '', 'string');
		$this->setState('filter_search', $search);

		$filter_language = $this->app->getUserStateFromRequest('com_secretary.filter_language', 'filter_language', '', 'string');
		$this->setState('filter_language', $filter_language);

		$params = Secretary\Application::parameters();
		$this->setState('params', $params);
	}

	public function getTranslation($lang = 'en-GB', $item = 'com_secretary', $original = false)
	{
		$registry  = new Registry();
		$languages = array();

		$originalPath	= JPATH_ADMINISTRATOR . '/language/' . $lang . '/' . $lang . '.' . $item . '.ini';
		$overrideFile	= JPATH_ADMINISTRATOR . '/language/overrides/' . $lang . '.override.ini';

		if (JFile::exists($originalPath)) {
			$registry->loadFile($originalPath, 'INI');
			$languages['original'] = $registry->toArray();
		} else {
			$registry->loadFile(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.' . $item . '.ini', 'INI');
			$languages['original'] = $registry->toArray();
		}

		if (JFile::exists($overrideFile)) {
			$registry->loadFile($overrideFile, 'INI');
			$languages[$lang] = $registry->toArray();
		} elseif (JFile::exists($originalPath)) {
			$registry->loadFile($originalPath, 'INI');
			$languages[$lang] = $registry->toArray();
		} else {
			$languages[$lang] = array();
		}

		return $languages;
	}

	public function getSiteLanguages()
	{
		jimport('joomla.filesystem.folder');

		$languagefolders = JFolder::folders(JPATH_ADMINISTRATOR . '/language');
		$return    = array();

		foreach ($languagefolders as $folder) {
			if (!in_array($folder, array('pdf_fonts', 'overrides'))) {
				$return[] = $folder;
			}
		}

		return $return;
	}

	public function save($data)
	{
		$lang = JFactory::getLanguage();

		$filterLanguage = $this->app->input->getVar('filter_language');
		$overrideFilePath = JPATH_ADMINISTRATOR . '/language/overrides/' . $filterLanguage . '.override.ini';

		$content = $this->makeFile($data);
		JFile::write($overrideFilePath, $content);
	}

	public function makeFile($data, $downable = false)
	{

		$values  = $data['values'];
		$content = "";

		foreach ($values as $key => $value) {
			if ($downable && (strpos($key, 'COM_SECRETARY') === false)) {
				continue;
			}
			$content .= "$key=\"$value\"\n";
		}

		return $content;
	}

	public function getForm($data = array(), $loadData = true)
	{
		return false;
	}
}
