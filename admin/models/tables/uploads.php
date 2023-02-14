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

class SecretaryTableUploads extends JTable
{

	/**
	 * Class constructor
	 *
	 * @param mixed $db
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__secretary_uploads', 'id', $db);
	}

	/**
	 * Delete and save activity
	 *
	 * {@inheritDoc}
	 * @see \Joomla\CMS\Table\Table::delete()
	 */
	public function delete($pk = null)
	{
		// Delete file
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		$app = \Secretary\Joomla::getApplication();

		if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
		$upload = Secretary\Database::getQuery('uploads', $pk);

		$path = SECRETARY_ADMIN_PATH . '/uploads/' . $upload->business . '/' . $upload->folder . '/' . $upload->title;
		if (JFile::delete($path)) {
			if ($upload->itemID > 0) $this->_updateItemDocument((int) $upload->itemID, $upload->extension, (int) $pk);
			$app->enqueueMessage(JText::sprintf('COM_SECRETARY_UPLOAD_DELETED', $upload->title), 'notice');
		} else {
			$app->enqueueMessage(JText::sprintf('COM_SECRETARY_UPLOAD_DELETED_NOT', $upload->title), 'error');
		}

		parent::delete($pk);
	}

	/**
	 * Update the section item
	 * 
	 * @param int $itemID
	 * @param string $extension
	 * @param int $uploadID
	 */
	private function _updateItemDocument($itemID, $extension, $uploadID)
	{
		$db			= \Secretary\Database::getDBO();
		$query		= $db->getQuery(true);
		$fields		= array($db->qn('upload') . " = ''");
		$conditions	= array($db->qn('id') . ' = ' . $db->escape($itemID), $db->qn('upload') . ' = ' . $db->escape($uploadID));
		$query->update($db->qn('#__secretary_' . $extension))->set($fields)->where($conditions);
		$db->setQuery($query);
		$result = $db->execute();
	}
}
