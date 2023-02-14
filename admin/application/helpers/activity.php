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

namespace Secretary\Helpers;

use JObject;
use JText;

// No direct access
defined('_JEXEC') or die;

class Activity
{
	/**
	 * Get an activity
	 */
	public static function get($id)
	{

		$activity = \Secretary\Database::getQuery('activities', (int) $id);

		$userName = \Secretary\Database::getJDataResult('users', intval($activity->created_by), 'name');
		if (empty($userName))
			$userName = JText::_('COM_SECRETARY_UNKNOWN');

		if (in_array($activity->extension, array('projects', 'events')))
			$table = 'times';
		else
			$table = $activity->extension;

		$item = ($activity->itemID > 0) ? \Secretary\Database::getQuery($table, (int) $activity->itemID) : 0;

		$folder = new JObject();
		$itemTitle = '';

		// Sanitize
		if ("s" !== substr($activity->extension, -1)) {
			$view = $activity->extension;
		} elseif ($activity->extension === 'businesses') {
			$view = substr($activity->extension, 0, -2);
		} else {
			$view = substr($activity->extension, 0, -1);
		}

		if ($activity->extension === 'documents') {
			$folder = \Secretary\Database::getQuery('folders', $activity->catid);
			$itemTitle = (!empty($folder->alias)) ? JText::_($folder->alias) : JText::_('COM_SECRETARY_DOCUMENT');
		} else {
			$itemTitle = JText::_('COM_SECRETARY_' . strtoupper($view));
		}

		// Link
		if ($activity->action == 'deleted') {
			$linkItem = $itemTitle . ' (#' . $activity->itemID . ')';
		} else {
			$linkItem = '<a href="' . \Secretary\Route::create($view, array('id' => $activity->itemID)) . '">' . $itemTitle . '</a>';
		}

		if ($activity->extension === 'tasks') {
			$linkItem = '<a href="' . \Secretary\Route::create('time', array('id' => $activity->itemID, 'extension' => 'tasks')) . '">' . JText::_('COM_SECRETARY_TASK') . '</a>';
		}

		// Result
		$result = $linkItem . ' ' . JText::_('COM_SECRETARY_ACTIVITY_' . strtoupper($activity->action)) . ' ' . JText::_('COM_SECRETARY_BY') . ' ' . $userName;

		return $result;
	}

	/**
	 * Saves an actity done by a user
	 * 
	 * @param string $extension
	 * @param string $action
	 * @param int $catid
	 * @param int $pk
	 * @param string $activity_done_by
	 */
	public static function set($extension, $action, $catid, $pk, $activity_done_by = NULL)
	{
		$params = \Secretary\Application::parameters();

		if ($pk > 0 && (($action == 'created' && ($params->get('activityCreated') == 1)) || ($action == 'edited' && ($params->get('activityEdited') == 1)) || ($action == 'deleted' && ($params->get('activityDeleted') == 1)) || $action == 'start' || $action == 'stop')) {

			// Update Upload Table
			$business = \Secretary\Application::company();
			$db = \Secretary\Database::getDBO();

			if (is_numeric($activity_done_by) && $activity_done_by > 0) {
				$activity_done_by = $db->escape($activity_done_by);
			} elseif (!is_null($activity_done_by)) {
				$activity_done_by = $db->quote($activity_done_by);
			} else {
				$activity_done_by = \Secretary\Joomla::getUser()->id;
			}

			$col = array('extension', 'business', 'catid', 'itemID', 'action', 'created', 'created_by');
			$val = array($db->quote($extension), intval($business['id']), intval($catid), intval($pk), $db->quote($action), $db->quote(date('Y-m-d H:i:s')), $activity_done_by);

			$uploadId = \Secretary\Database::insert('activities', $col, $val);
		}
	}
}