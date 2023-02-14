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

jimport('joomla.application.component.view');

JFormHelper::addFieldPath(SECRETARY_ADMIN_PATH . '/models/fields');

class SecretaryViewItems extends JViewLegacy
{
	protected $title;
	protected $extension;
	protected $items;
	protected $pagination;
	protected $state;
	protected $params;

	/**
	 * Method to display the View
	 *
	 * {@inheritDoc}
	 * @see \Joomla\CMS\MVC\View\HtmlView::display()
	 */
	public function display($tpl = null)
	{
		$app 				= Secretary\Joomla::getApplication();
		$user				= Secretary\Joomla::getUser();
		$this->extension	= $app->input->getCmd('extension', 'status');
		$this->canDo		= \Secretary\Helpers\Access::getActions();

		if (!$user->authorise('core.admin', 'com_secretary')) {
			JError::raiseError(500, JText::_('JERROR_ALERTNOAUTHOR'));
			return;
		}

		$this->state		= $this->get('State');

		if ($this->extension == 'plugins') {
			$this->items		= $this->get('Plugins');
		} else {
			$this->items		= $this->get('Items');
			$this->pagination	= $this->get('Pagination');

			$this->section	= $this->state->get('filter.section');
			$this->sections	= $this->getModules();
		}

		if ($this->extension == 'status') {
			$this->module = $this->state->get('filter.category_id', 'system');
		}

		// Check for errors.
		if (count(($errors = $this->get('Errors')) ?? [])) {
			throw new Exception(implode("\n", $errors));
		}

		if ($this->extension == 'uploads') {
			$this->extraItems = $this->get('EmailFiles');
		}

		if ($this->extension == 'settings') $app->redirect(Secretary\Route::create('dashboard'));

		$this->title =  JText::_('COM_SECRETARY_' . strtoupper($this->extension));
		$this->getJS();

		parent::display($tpl);
	}


	private function getModules()
	{
		$modules = JFormHelper::loadFieldType('SecretarySections', false)->getOptions();
		return $modules;
	}

	/**
	 * Method to create the Toolbar
	 */
	protected function addToolbar()
	{

		$this->document->setTitle('Secretary - ' . $this->title);

		//Check if the form exists before showing the add/edit buttons
		$formPath = SECRETARY_ADMIN_PATH . '/views/item';
		if (file_exists($formPath) && ($this->extension != 'plugins')) {

			$module =  (!empty($this->module)) ? '&module=' . $this->module : '';
			if ($this->canDo->get('core.create')) {
				echo '<a href="index.php?option=com_secretary&task=item.add&extension=' . $this->extension . $module . '" class="btn btn-newentry">' . JText::_('COM_SECRETARY_TOOLBAR_NEW') . '</a>';
			}

			if ($this->canDo->get('core.edit') && isset($this->items[0]) && $this->canDo->get('core.delete')) {
				echo Secretary\Navigation::ToolbarItem('items.delete', 'COM_SECRETARY_TOOLBAR_DELETE', true, 'default hidden-toolbar-btn', 'fa-trash');
			}
		}

		if ($this->extension == 'plugins') {
			echo '<a class="btn " href="' . JRoute::_("index.php?option=com_plugins&filter_folder=secretary") . '">' . JText::_('COM_SECRETARY_PLUGINS') . '</a>';
		}
	}

	protected function getSortFieldsEntities()
	{
		return array(
			'a.id' => JText::_('JGRID_HEADING_ID'),
			'a.title' => JText::_('COM_SECRETARY_ENTITY_TITEL'),
			'a.description' => JText::_('COM_SECRETARY_ENTITY_LANG'),
		);
	}

	protected function getSortFieldsUploads()
	{
		return array(
			'a.id' => JText::_('JGRID_HEADING_ID'),
			'a.title' => JText::_('COM_SECRETARY_TITLE'),
		);
	}

	protected function getSortFieldsStatus()
	{
		return array(
			'a.id' => JText::_('JGRID_HEADING_ID'),
			'a.title' => JText::_('COM_SECRETARY_TITLE'),
			'a.ordering' => JText::_('COM_SECRETARY_ORDERING'),
			'a.closeTask' => JText::_('COM_SECRETARY_CLOSETASK'),
		);
	}

	protected function getJS()
	{
		$document = JFactory::getDocument();

		$document->addScriptDeclaration('
			Joomla.orderTable = function() {
				table = document.getElementById("sortTable");
				direction = document.getElementById("directionTable");
				order = table.options[table.selectedIndex].value;
				if (order != "' . $this->state->get('list.ordering') . '") {
					dirn = "asc";
				} else {
					dirn = direction.options[direction.selectedIndex].value;
				}
				Joomla.tableOrdering(order, dirn, "");
			}
		');
	}

	protected function checkPlugin($name)
	{
		$db		= \Secretary\Database::getDBO();
		$query	= $db->getQuery(true);

		$query->select('a.extension_id,a.enabled')
			->from($db->quoteName('#__extensions', 'a'))
			->where($db->quoteName('folder') . '=' . $db->quote("secretary"))
			->where($db->quoteName('name') . '=' . $db->quote($name));

		$db->setQuery($query);
		$result = $db->loadObject();
		return $result;
	}
}
