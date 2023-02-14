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

use JError;
use JModelLegacy;
use JText;
use stdClass;

// No direct access
defined('_JEXEC') or die;

class Documents
{

	/**
	 * Method to acquit an invoice after due date
	 * 
	 * @param int $pk document id 
	 * @return boolean
	 */
	public static function acquit($pk)
	{
		if (!empty($pk)) {
			$db = \Secretary\Database::getDBO();
			$user = \Secretary\Joomla::getUser();

			// Item
			$item = \Secretary\Database::getQuery('documents', $pk, 'id', 'created_by,total,created');
			// ACL
			$canChange = false;
			if (($user->id == $item->created_by && $user->authorise('core.edit.own', 'com_secretary.document')) || $user->authorise('core.edit', 'com_secretary.document')) {
				$canChange = true;
			} else {
				$canChange = $user->authorise('core.edit.state', 'com_secretary.document');
			}

			if ($canChange) {

				$sql = $db->getQuery(true);

				$sql->update($db->qn('#__secretary_documents'))
					->set('paid="' . $db->escape($item->total) . '"')
					->where('id="' . $db->escape($pk) . '"');

				try {
					$db->setQuery($sql);
					$db->execute();
				} catch (\Exception $exception) {
					throw new \Exception($exception->getMessage());
				}

				$title = $item->created;
				\Secretary\Joomla::getApplication()->enqueueMessage(JText::sprintf('COM_SECRETARY_PAIDUP_THAT_DOCUMENT', $title));
			} else {
				JError::raiseError(1, JText::_('COM_SECRETARY_PERMISSION_FAILED'));
			}
		}
		return true;
	}

	/**
	 * Method to check if the document nr is already taken 
	 */
	public static function getDoubleCategoryNumber($nr_to_check, $catid, $id = false)
	{
		if (!empty($nr_to_check) && !empty($catid)) {
			$return = '';
			$db = \Secretary\Database::getDBO();
			$sql = $db->getQuery(true);

			$sql->select("COUNT(*)")
				->from($db->qn('#__secretary_documents'))
				->where($db->qn('nr') . '=' . $db->quote($nr_to_check))
				->where($db->qn('catid') . '=' . $db->escape((int) $catid));

			if (isset($id))
				$sql->where($db->qn('id') . ' != ' . $db->escape((int) $id));

			$db->setQuery($sql);
			$NrExists = $db->loadResult();

			if (!empty($NrExists)) {

				$up = $nr_to_check + 5;
				$down = $nr_to_check - 5;

				$sql = $db->getQuery(true);
				$sql->select('nr');
				$sql->from($db->qn('#__secretary_documents'));
				$sql->where($db->qn('catid') . '=' . (int) $catid);
				$sql->where($db->qn('nr') . ' BETWEEN ' . $down . ' AND ' . $up);
				if (!empty($id))
					$sql->where($db->qn('id') . ' != ' . $db->escape((int) $id));

				$db->setQuery($sql);
				$return = $db->loadColumn();
			}

			return $return;
		}

		return false;
	}

	/**
	 * Prepares a Document  
	 */
	public static function getDocumentsPrepareRow(&$row, &$result)
	{

		if (!empty($result->id)) {
			$row["id"] = $result->id;
			$row["category"] = isset($result->category) ? JText::_($result->category) : '';
			$row["subjectid"] = $result->subjectid;
			$row["created"] = $result->created;

			if (!empty($result->title)) {
				$row["title"] = \Secretary\Utilities::cleaner($result->title, true);
			} elseif (!empty($row["category"])) {
				$row["title"] = $row["category"];
			} else {
				$row["title"] = JText::_('COM_SECRETARY_DOCUMENT');
			}

			$row["value"] = '# ' . \Secretary\Utilities::cleaner($result->nr, true) . ' | ' . $row["title"] . ' | ' . $result->created;
			if ($result->subjectid > 0) {
				$model = JModelLegacy::getInstance('Subject', 'SecretaryModel');
				$subject = $model->getItem($result->subjectid);

				$row["subject"] = $subject;
			} else {
				$row["subject"] = new stdClass();
			}

			$row["total"] = round($result->total, 2);
			$row["subtotal"] = $result->subtotal;
			$row["taxtotal"] = $result->taxtotal;

			if ($tax = json_decode($result->taxtotal, true)) {
				$row["tax"] = array_sum($tax);
			} else {
				$row["tax"] = (!empty($result->taxtotal)) ? $result->taxtotal : 0;
			}
			$row["taxtotal"] = $result->taxtotal;

			$row["taxtype"] = $result->taxtype;
			$row["deadline"] = $result->deadline;
			$row["currency"] = $result->currency;
			$row["rabatt"] = $result->rabatt;
			$row["created"] = $result->created;
			$row["nr"] = $result->nr;
		} else {
			$row["value"] = "";
		}
		unset($result);
	}

	/**
	 * Search documents for a term
	 * 
	 * @param string $search term
	 * @return string JSON result
	 */
	public static function search($search)
	{
		$i = 0;
		$json = array();

		if (!isset($search) || strlen($search) <= 2)
			exit;

		$business = \Secretary\Application::company();
		$user = \Secretary\Joomla::getUser();
		$db = \Secretary\Database::getDBO();
		$searchValue = $db->quote('%' . htmlentities($search, ENT_QUOTES) . '%');

		$query = $db->getQuery(true);
		$query->select($db->qn(array('d.id', 'd.nr', 'd.title', 'd.total', 'd.subtotal', 'd.taxtotal', 'd.taxtype', 'd.deadline', 'd.currency', 'd.rabatt', 'd.subjectid', 'd.created')))
			->from($db->quoteName('#__secretary_documents', 'd'));

		$query->select('c.title as category,alias')
			->leftJoin($db->quoteName('#__secretary_folders', 'c') . ' ON c.id = d.catid');

		$query->where("d.business=" . intval($business['id']))
			->where(' (d.title LIKE ' . $searchValue . ') OR (d.nr LIKE ' . $searchValue . ') OR (d.id LIKE ' . $searchValue . ')')
			->order('created DESC');
		$db->setQuery($query, 0, 50);
		try {
			$results = $db->loadObjectList();
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
			exit;
		}

		foreach ($results as $result) {

			if (
				$user->authorise('core.show', 'com_secretary.document.' . $result->id)
				|| $user->authorise('core.show.other', 'com_secretary.document')
			) {
				$row = array();
				\Secretary\Helpers\Documents::getDocumentsPrepareRow($row, $result);
				$json[$i] = $row;
				$i++;
			}

			if ($i > 9) {
				break;
			}
		}

		flush();

		return json_encode($json);
	}

	/**
	 * Search document titles
	 */
	public static function searchTitle($search)
	{
		$i = 0;
		$json = array();

		if (!isset($search) || strlen($search) <= 2)
			exit;

		$business = \Secretary\Application::company();
		$db = \Secretary\Database::getDBO();
		$searchValue = $db->quote('%' . htmlentities($search, ENT_QUOTES) . '%');

		$query = $db->getQuery(true);
		$query->select("DISTINCT(title)");
		$query->from($db->qn("#__secretary_documents"));
		$query->where($db->qn('business') . ' = ' . (int) $business['id']);
		$query->where($db->qn('title') . ' LIKE ' . $db->quote('%' . $search . '%'));
		$query->order('title ASC');
		$db->setQuery($query);

		try {
			$results = $db->loadObjectList();
			foreach ($results as $result) {
				$json[$i]["value"] = \Secretary\Utilities::cleaner($result->title, true);
				array_filter($json);
				if ($i > 9)
					break;
				$i++;
			}
			return json_encode($json);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}
	}
}