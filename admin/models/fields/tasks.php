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

JFormHelper::loadFieldClass('list');

class JFormFieldTasks extends JFormFieldList
{

	protected $type = 'tasks';

	protected function getOptions()
	{
		$app = \Secretary\Joomla::getApplication();
		$db = \Secretary\Database::getDBO();
		$pid = $app->input->getInt('pid', '');

		if (empty($pid)) {
			$id = $app->input->getInt('id', '');
			$pid = Secretary\Database::getQuery('tasks', (int) $id, 'id', $db->qn('projectID'), 'loadResult');
		}

		$user = \Secretary\Joomla::getUser();
		$tasks = array();
		$business = \Secretary\Application::company();

		$query = $db->getQuery(true)
			->select($db->qn(array("id", "title", "parentID", "level", "state")))
			->select("id AS value,title AS text")
			->from($db->quoteName("#__secretary_tasks"))
			->where($db->qn('business') . '=' . intval($business['id']));

		if (!empty($pid))
			$query->where($db->quoteName("projectID") . " =" . intval($pid));

		$db->setQuery($query);
		$tasks = $db->loadObjectList();

		$tasks = \Secretary\Helpers\Times::reorderTasks($tasks);

		for ($i = 0, $n = count($tasks ?? []); $i < $n; $i++) {
			if (
				$user->authorise('core.show', 'com_secretary.task.' . $tasks[$i]->id)
				|| $user->authorise('core.show.other', 'com_secretary.task.' . $tasks[$i]->id)
			) {
				$tasks[$i]->text = str_repeat('- ', $tasks[$i]->level) . JText::_($tasks[$i]->text);
			} else {
				unset($tasks[$i]);
			}
		}

		array_unshift($tasks, JText::_('COM_SECRETARY_SELECT_OPTION'));
		return $tasks;
	}
}