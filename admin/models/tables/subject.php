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

class SecretaryTableSubject extends JTable
{

	/**
	 * Class constructor
	 *
	 * @param mixed $db
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__secretary_subjects', 'id', $db);
	}

	/**
	 * {@inheritDoc}
	 * @see \Joomla\CMS\Table\Table::bind()
	 */
	public function bind($array, $ignore = '')
	{
		$acl = JFactory::getACL();
		$user = \Secretary\Joomla::getUser();

		if (!$user->authorise('core.admin', 'com_secretary.subject.' . $array['id'])) {
			$actions = $acl->getActions('com_secretary', 'subject');
			$default_actions = $acl->getAssetRules('com_secretary.subject.' . $array['id'])->getData();
			$array_jaccess = array();
			foreach ($actions as $action) {
				if (isset($default_actions[$action->name]))
					$array_jaccess[$action->name] = $default_actions[$action->name];
			}
			$array['rules'] = \Secretary\Helpers\Access::JAccessRulestoArray($array_jaccess);
		}

		//Bind the rules for ACL where supported.
		if (isset($array['rules']) && is_array($array['rules'])) {
			$array['rules'] = \Secretary\Helpers\Access::JAccessRulestoArray($array['rules']);
			$this->setRules($array['rules']);
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Prepares data before saving it
	 * 
	 * @param array $data
	 */
	public function prepareStore(&$data)
	{

		//$data['created_by']	= ($this->created_by > 1) ? $this->created_by : 0;
		$data['created']	= (isset($this->created) && ($this->created != '0000-00-00')) ? $this->created : date('Y-m-d');

		$business = Secretary\Application::company();
		$data['business'] = (isset($this->business)) ? $this->business : (int) $business['id'];

		// Data Fields
		$data['fields']	= (isset($data['fields'])) ? \Secretary\Helpers\Items::saveFields($data['fields']) : FALSE;

		// Google Maps
		if (!empty($data['location'])) {
			$coords = \Secretary\Helpers\Locations::getCoords($data['street'], $data['zip'], $data['location']);
			$data['lat'] = $coords['lat'];
			$data['lng'] = $coords['lng'];
		} else {
			$data['lat'] = 0.0;
			$data['lng'] = 0.0;
		}
	}

	/**
	 * {@inheritDoc}
	 * @see \Joomla\CMS\Table\Table::check()
	 */
	public function check()
	{
		// No Contact
		if (empty($this->lastname)) {
			$errTitle = JText::_('COM_SECRETARY_SUBJECT_NAME');
			$this->setError(JText::sprintf('COM_SECRETARY_ERROR_CHECK_THIS', $errTitle));
			return false;
		}
		// Wrong Email
		if (!empty($this->email) && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
			$this->setError('Invalid Email');
			return false;
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 * @see \Joomla\CMS\Table\Table::_getAssetName()
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;
		return 'com_secretary.subject.' . (int) $this->$k;
	}

	/**
	 * {@inheritDoc}
	 * @see \Joomla\CMS\Table\Table::_getAssetParentId()
	 */
	protected function _getAssetParentId(JTable $table = null, $id = null)
	{
		$asset = self::getInstance('Asset');
		$asset->loadByName('com_secretary.subject');
		return $asset->id;
	}

	/**
	 * Delete and save activity
	 *
	 * {@inheritDoc}
	 * @see \Joomla\CMS\Table\Table::delete()
	 */
	public function delete($pk = null)
	{
		$this->load($pk);
		$result = parent::delete($pk);
		if ($result) {
			// Remove in Newsletter Lists
			\Secretary\Helpers\Newsletter::removeContactFromAllNewsletters($pk);
			\Secretary\Helpers\Activity::set('subjects', 'deleted', $this->catid, $pk);
		}
		return $result;
	}
}
