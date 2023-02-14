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

class JFormFieldGender extends JFormFieldList
{

	protected $type = 'gender';
	private $_list = array();

	public function getOptions($key = false)
	{

		$options = array();

		if (empty($this->_list)) {
			$db = \Secretary\Database::getDBO();
			$query = $db->getQuery(true);
			$query->select('*')
				->from($db->qn('#__secretary_fields'))
				->where($db->qn('hard') . '=' . $db->quote('anrede'))
				->where($db->qn('extension') . "=" . $db->quote('system'))
				->order('id');
			$db->setQuery($query);
			$this->_list = $db->loadObject();
		}

		$object = $this->_list;
		if ($fieldvalues = json_decode($object->values, true)) {

			foreach ($fieldvalues as $pos => $val) {
				$fieldvalues[$pos] = JText::_($val);
				if (strlen($val) >= 1) {
					$val = JText::_($val);
				} else {
					$val = JText::_('COM_SECRETARY_NONE');
				}
				$options[$pos] = JHTML::_('select.option', $pos, $val);
			}
			if ($key === false) {
				return $options;
			} elseif (isset($options[$key])) {
				return JText::_($fieldvalues[$key]);
			}
		}
		return false;
	}

	public function getList($default, $name = 'jform[subject][]', $id = 'jform_subject_gender')
	{
		$options = $this->getOptions();
		if ($options) {
			return '<select name="' . $name . '" id="' . $id . '" class="form-control inputbox">' . JHtml::_('select.options', $options, 'value', 'text', $default) . '</select>';
		}
		return false;
	}
}