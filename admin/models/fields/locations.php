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
defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldLocations extends JFormFieldList
{

	protected $type = 'locations';

	public function getOptions()
	{
		$user = \Secretary\Joomla::getUser();
		$business = \Secretary\Application::company();
		$extension = (string) $this->element['extension'];
		$html = array();

		$db = \Secretary\Database::getDBO();

		$where = array('business = ' . intval($business['id']));
		if (!empty($extension)) {
			array_push($where, 'extension = ' . $db->quote($extension));
		}

		$items = \Secretary\Database::getObjectList('locations', ['id', 'title'], $where, 'title ASC');

		// Make list
		$html[] = JHtml::_('select.option', 0, JText::_('COM_SECRETARY_SELECT_OPTION'));
		foreach ($items as $message) {
			if (
				$user->authorise('core.show', 'com_secretary.location.' . $message->id)
				|| $user->authorise('core.show.other', 'com_secretary.location.' . $message->id)
			) {
				$html[] = JHtml::_('select.option', $message->id, $message->title);
			}
		}

		return $html;
	}

	public function getLocations($view, $not = NULL)
	{
		$user = \Secretary\Joomla::getUser();
		$locations = array();
		$business = \Secretary\Application::company();

		$db = \Secretary\Database::getDBO();
		$query = $db->getQuery(true)
			->select("id,title")
			->from($db->quoteName("#__secretary_locations"))
			->where($db->qn('business') . ' = ' . intval($business['id']))
			->where($db->quoteName('extension') . '=' . $db->quote($view))
			->order('title ASC');

		if (!empty($not))
			$query->where($db->quoteName('id') . "!=" . intval($not));
		$db->setQuery($query);
		$locations = $db->loadObjectList();

		for ($i = 0, $n = count($locations ?? []); $i < $n; $i++) {
			if (
				$user->authorise('core.show', 'com_secretary.location.' . $locations[$i]->id)
				|| $user->authorise('core.show.other', 'com_secretary.location.' . $locations[$i]->id)
			) {
				$locations[$i]->title = '- ' . $locations[$i]->title;
			} else {
				unset($locations[$i]);
			}
		}

		$title = JText::sprintf('COM_SECRETARY_FILTER_SELECT_LABEL_ALL', JText::_('COM_SECRETARY_LOCATIONS_DOCUMENTS'));
		array_unshift($locations, $title);

		return $locations;
	}

	public function getList($default = 0, $name = 'jform[items][##counter##][location]')
	{
		$html = $this->getOptions();
		$result = '<select name="' . $name . '" id="jform_items_location" class="form-control location-select">' . JHtml::_('select.options', $html, 'value', 'text') . '</select>';
		return $result;
	}
}