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

class JFormFieldSubjects extends JFormFieldList
{

	protected $type = 'subjects';

	public function getOptions()
	{
		$html = array();

		$user = \Secretary\Joomla::getUser();
		$db = \Secretary\Database::getDBO();
		$query = $db->getQuery(true);

		$query->select("id,CONCAT( lastname ,', ', firstname, ' aus ', location, ' (', street, ')') AS username")
			->from($db->qn('#__secretary_subjects'))
			->order('lastname ASC');

		$db->setQuery($query);
		$items = $db->loadObjectList();

		foreach ($items as $subject) {
			if (
				$user->authorise('core.show', 'com_secretary.subject.' . $subject->id)
				|| $user->authorise('core.show.other', 'com_secretary.subject.' . $subject->id)
			) {
				$html[] = JHtml::_('select.option', $subject->id, $subject->username);
			}
		}

		return $html;
	}

	protected function getInput()
	{

		// Load the javascript
		JHtml::_('bootstrap.tooltip');

		JFactory::getLanguage()->load('com_secretary', JPATH_ADMINISTRATOR);
		$html = array();

		// Note: class='required' for client side validation.
		$class = '';
		if ($this->required) {
			$class = ' class="required modal-value"';
		}

		// The active contact id field.
		if (0 == (int) $this->value) {
			$value = '';
		} else {
			$value = (int) $this->value;
		}

		// Get the title of the linked chart
		if ((int) $this->value > 0) {
			$fieldStr = (Secretary\Database::getDbType() == 'postgresql') ? "firstname || ' ' || lastname" : 'CONCAT_WS(" ",firstname,lastname)';

			$db = \Secretary\Database::getDBO();
			$query = $db->getQuery(true)
				->select($fieldStr)
				->from($db->qn('#__secretary_subjects'))
				->where($db->qn('id') . '=' . (int) $this->value);
			$db->setQuery($query);

			try {
				$title = $db->loadResult();
			} catch (RuntimeException $e) {
				JError::raiseWarning(500, $e->getMessage());
			}
		}

		if (empty($title)) {
			$title = JText::_('COM_SECRETARY_SELECT_A_CONTACT');
		}

		$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		// Build the script.
		$script = array();

		// Select button script
		$script[] = '	function jSelectUser( name, street, zip, location, phone, email, gender, id ) {';
		$script[] = '		document.getElementById("subject_name").value = name;';
		$script[] = '		document.getElementById("subject_id").value = id;';
		$script[] = '		SqueezeBox.close();return false;';
		$script[] = '	}';

		// Add the script to the document head.
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		$link = 'index.php?option=com_secretary&amp;view=subjects&amp;layout=modal&amp;tmpl=component&amp;' . JSession::getFormToken() . '=1';

		if (isset($this->element['language'])) {
			$link .= '&amp;forcedLanguage=' . $this->element['language'];
		}

		if (isset($this->element['email'])) {
			$link .= '&amp;email=1';
		}

		// The current contact display field.
		$html[] = '<span class="input-append">';
		$html[] = '<input type="text" class="input-medium" id="subject_name" value="' . $title . '" disabled="disabled" size="35" />';
		$html[] = '<input type="hidden" id="subject_id"' . $class . ' name="' . $this->name . '" value="' . $value . '" />';

		$html[] = '<a class="modal btn btn-default hasTooltip" title="' . JHtml::tooltipText('COM_SECRETARY_CONTACTS_MODAL_DIALOG') . '"  href="' . $link . '" rel="{handler: \'iframe\', size: {x: 800, y: 450}}">' . JText::_('COM_SECRETARY_SELECT') . '</a>';

		// Edit contact
		$html[] = '<a class="btn hasTooltip' . ($value ? '' : ' hidden') . '" target="_blank"'
			. ' href="index.php?option=com_secretary&layout=modal&tmpl=component&task=subject.edit&id=' . $value . '"'
			. ' title="' . JHtml::tooltipText('COM_SECRETARY_EDIT') . '" >'
			. '<span class="icon-edit"></span>' . JText::_('JACTION_EDIT') . '</a>';

		// Clear contact
		$html[] = '<button id="' . $this->id . '_clear" class="btn' . ($value ? '' : ' hidden') . '"'
			. " onclick=\"return jSelectUser('','','','','','','','')\">"
			. '<span class="icon-remove"></span>' . JText::_('COM_SECRETARY_CLEAR')
			. '</button>';

		// The current contact display field.
		$html[] = JHtml::_(
			'bootstrap.renderModal',
			$this->id,
			array(
				'url' => $link . '&amp;' . JSession::getFormToken() . '=1"',
				'title' => JText::_('COM_SECRETARY_CHANGE_CONTACT'),
				'width' => '800px',
				'height' => '300px',
				'footer' => '<button class="btn" data-dismiss="modal" aria-hidden="true">' . JText::_("JLIB_HTML_BEHAVIOR_CLOSE") . '</button>'
			)
		);
		$html[] = '</span>';

		return implode("\n", $html);
	}
}