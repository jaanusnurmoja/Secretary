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

JFormHelper::addFieldPath(SECRETARY_ADMIN_PATH.'/models/fields');

class SecretaryViewTemplates extends JViewLegacy
{
    protected $categoryId;
    protected $categories;
    protected $canDo;
    protected $extension;
    protected $items;
    protected $pagination;
    protected $state;
    protected $states;
	protected $title;
	protected $view;
	
	/**
	 * Method to display the View
	 *
	 * {@inheritDoc}
	 * @see \Joomla\CMS\MVC\View\HtmlView::display()
	 */
	public function display($tpl = null)
	{
	    $app				= \Secretary\Joomla::getApplication();
		$this->view			= $app->input->getCmd('view', 'templates');
		$this->extension	= $app->input->getCmd('extension');
		$this->categoryId	= $app->input->getInt('catid',0);
		
		$this->state		= $this->get('State');
		$this->items		= $this->get('Items');
		$this->pagination	= $this->get('Pagination');
		$this->canDo		= \Secretary\Helpers\Access::getActions($this->view);
		
		$this->title		= JText::_('COM_SECRETARY_TEMPLATES');
		if(!empty($this->extension)) $this->title .= " - ". JText::_('COM_SECRETARY_'.strtoupper($this->extension));
		
		// Permission check
		if ( !$this->canDo->get('core.show')) { 
			$app->enqueueMessage( JText::_('JERROR_ALERTNOAUTHOR'), 'error');
			return false;
		} elseif (count(($errors = $this->get('Errors')) ?? [])) {
		    throw new Exception(implode("\n", $errors));
		    return false;
		}
        
		$this->categories	= JFormHelper::loadFieldType('Categories', false)->getCategories( $this->view );
		$this->states		= JFormHelper::loadFieldType('Secretarystatus', false)->getOptions( $this->view );
		$this->getJS();
		
		parent::display($tpl);
	} 
	
	/**
	 * Method to create the Toolbar
	 */
	protected function addToolbar()
	{
		$html = array();

		if ($this->canDo->get('core.create')) {
			$addEventText = JText::_('COM_SECRETARY_TEMPLATE');
			if(isset($this->extension)) $addEventText .= ' '.JText::_('COM_SECRETARY_'.$this->extension);
			$html[] = Secretary\Navigation::ToolbarItem('template.add', JText::sprintf('COM_SECRETARY_NEW_ENTRY_TOOLBAR',$addEventText), false, 'newentry');
		}

		if ($this->extension == 'newsletters' && \Secretary\Joomla::getUser()->authorise('com_secretary.message','core.create')) {
			$html[] = Secretary\Navigation::ToolbarItem('templates.sendLetter', 'COM_SECRETARY_SEND', true, 'default', 'fa-send');
		}

		// Stapel
		if ($this->canDo->get('core.edit')) {
		    $html[] = '<button data-toggle="modal" data-target="#collapseModal" class="btn btn-small">
						<span class="fa fa-database" title=\"'.JText::_('COM_SECRETARY_TOOLBAR_BATCH').'\"></span>'.
		    JText::_('COM_SECRETARY_TOOLBAR_BATCH').'</button>';
		}
		
		
		if ($this->canDo->get('core.delete') && isset($this->items[0])) {
			$html[] = Secretary\Navigation::ToolbarItem('templates.delete', 'COM_SECRETARY_TOOLBAR_DELETE', true, 'default hidden-toolbar-btn', 'fa-trash');
		}
		
		if(!empty($html))
			array_unshift($html, '<div class="select-arrow-toolbar-next">&#10095;</div>');
		
		echo implode("\n", $html);
	}
	
	protected function getSortFields()
	{
		return array(
			'a.id' => JText::_('JGRID_HEADING_ID'),
			'a.title' => JText::_('COM_SECRETARY_NAME'),
			'a.desc' => JText::_('COM_SECRETARY_DESCRIPTION'),
			'a.business' => JText::_('COM_SECRETARY_BUSINESS'),
			'category' => JText::_('COM_SECRETARY_CATEGORY'),
			'a.state' => JText::_('JSTATUS'),
			'a.language' => JText::_('COM_SECRETARY_LANGUAGE'),
		);
	}

	protected function getJS()
	{
		$document = JFactory::getDocument();
		$document->addScriptDeclaration("
		jQuery(document).ready(function($){
			$('#select_category').change(function(){
				var value = $(this).val();
				$('#products_catID').val(value);
				$('form').get(0).setAttribute('action', 'index.php?option=com_secretary&view=templates&catid='+value); 
				this.form.submit();
			});
		});

		Joomla.orderTable = function() {
				table = document.getElementById('sortTable');
				direction = document.getElementById('directionTable');
				order = table.options[table.selectedIndex].value;
				if (order != '". $this->state->get('list.ordering') ."') {
					dirn = 'asc';
				} else {
					dirn = direction.options[direction.selectedIndex].value;
				}
				Joomla.tableOrdering(order, dirn, '');
			}
		");
	}
    
}
