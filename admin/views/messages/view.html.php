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

JFormHelper::addFieldPath(SECRETARY_ADMIN_PATH.'/models/fields');

class SecretaryViewMessages extends JViewLegacy
{
	
	protected $items;
	protected $pagination;
	protected $state;
	protected $isChatFrontend;
	
	/**
	 * Method to display the View
	 *
	 * {@inheritDoc}
	 * @see \Joomla\CMS\MVC\View\HtmlView::display()
	 */
	public function display($tpl = null)
	{
	    
	    $this->app          = \Secretary\Joomla::getApplication();
		$this->categoryId	= $this->app->input->getInt('catid',0);
		$this->contactID	= $this->app->input->getInt('contact_to');
		$this->referTo      = $this->app->input->getInt('rid',0);
		$this->view			= $this->app->input->getCmd('view');
		$this->key			= $this->app->input->getVar('k');
		$this->pagination	= $this->get('Pagination');
		$this->state		= $this->get('State');
        $this->params       = \Secretary\Application::parameters();
		$this->canDo		= \Secretary\Helpers\Access::getActions($this->view);
		
		if( $this->_layout === 'talk' || $this->_layout === 'chat' ) {
			$this->talks		= $this->get('Talks');
			$this->otherTalks	= $this->get('RecentTalks');
		} else {
			$this->items			= $this->get('Items');
			$this->correspondence	= $this->get('Correspondence'); 
		}
		
		$canShow =  $this->canDo->get('core.show');
		$this->isChatFrontend = ($this->app->isAdmin()) ? true : false;
		if(!$this->isChatFrontend && (isset($this->key) && md5($this->talks[0]->id.$this->talks[0]->created) === $this->key)) {
		    $this->isChatFrontend = true;
		    $canShow = true;
		}
		
		if (false === boolval($canShow)) {
			throw new Exception( JText::_('JERROR_ALERTNOAUTHOR') , 500);
			return false;
		} elseif (count(($errors = $this->get('Errors')) ?? [])) {
			throw new Exception( implode("\n", $errors) , 500);
			return false;
		}
		
		$this->user = \Secretary\Joomla::getUser();
		$userContact = \Secretary\Database::getQuery('subjects',(int) $this->user->id,'created_by','id','loadResult');
		$this->userContactId = (isset($userContact)) ? (int) $userContact : -1;
		
		$this->folders            = JFormHelper::loadFieldType('Categories', false)->getCategories( $this->view );
		$this->contactsFolders    = JFormHelper::loadFieldType('Categories', false)->getCategories( 'subjects' );
		$this->states             = JFormHelper::loadFieldType('Secretarystatus', false)->getOptions( $this->view );
		$this->_getJS();
		
		parent::display($tpl);
	} 
	
	/**
	 * Method to create the Toolbar
	 */
	protected function addToolbar()
	{
		$html	= array();
		
		if ($this->_layout === 'talk' && $this->canDo->get('core.delete')) 
			$html[] = '<div class="pull-left margin-right">'. Secretary\HTML::_('status.checkall') .'<span class="lbl"></span></div>';
		
		if ($this->_layout != 'talk' && $this->canDo->get('core.create')) {
			$addEventText = JText::_('COM_SECRETARY_MESSAGE');
			$html[] = Secretary\Navigation::ToolbarItem('message.add', JText::sprintf('COM_SECRETARY_NEW_ENTRY_TOOLBAR',$addEventText), false, 'newentry');
		}
		
		if ($this->canDo->get('core.delete')) {
			$html[] = Secretary\Navigation::ToolbarItem('messages.delete', 'COM_SECRETARY_TOOLBAR_DELETE', true, 'default hidden-toolbar-btn', 'fa-trash');
		}

		if ($this->canDo->get('core.edit')) {
			$title = JText::_('COM_SECRETARY_TOOLBAR_BATCH');
			$html[] = "<button data-toggle=\"modal\" data-target=\"#collapseModal\" class=\"btn btn-small  hidden-toolbar-btn\">
						<i class=\"icon-checkbox-partial\" title=\"$title\"></i> $title</button>";
			
		}  
		
		echo implode("\n", $html);
	}
	
	protected function _getJS()
	{
		$document = JFactory::getDocument();
		$document->setBase(JURI::base());
		
		$document->addScriptDeclaration('
			jQuery(document).ready(function($){
				$("#subjects_category").change(function(){
					var value = $(this).val();
					$("#subjects_catID").val(value);
					$("form").get(0).setAttribute("action", "index.php?option=com_secretary&view=messages&catid="+value); 
					this.form.submit();
				});
			});
		');
	}
}
