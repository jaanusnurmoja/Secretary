<?php
/**
 * @version     3.0.0
 * @package     com_secretary
 *
 * @author       Fjodor Schaefer (schefa.com)
 * @copyright    Copyright (C) 2015-2017 Fjodor Schaefer. All rights reserved.
 * @license      GNU General Public License version 2 or later.
 */
 
// No direct access
defined('JPATH_BASE') or die;

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldEntities extends JFormFieldList
{
	
	protected $type = 'entities';
	
	public function getInput( )
	{ 
		$params = Secretary\Application::parameters();
		if( $params->get('entitySelect') != 1) {
			
			$html = '<input id="'.$this->id.'_entity" type="text" class="fullwidth" name="'.$this->name.'" value="'.$this->value.'" />';
		
		} else {
				
			$options = array();
			$db = JFactory::getDbo();
			$q = $db->getQuery(true);
			$q->select('id,title')
				->from($db->qn('#__secretary_entities'))
				->order('title ASC');
			
			$db->setQuery($q);
			$items = $db->loadObjectList();
			
			$options[] = JHtml::_('select.option', 0, JText::_('COM_SECRETARY_ENTITY') );
			foreach($items as $message) {
				$options[] = JHtml::_('select.option', $message->id, JText::_($message->title) );
			}
		
			$html = '<div class="select-arrow select-arrow-white"><select name="'.$this->name.'" id="'.$this->id.'" class="form-control entity-select">'. JHtml::_('select.options', $options, 'value', 'text', $this->value) . '</select></div>';
			
		} 
		
		return $html;
		
	}
	
	public function getOptions( )
	{
		
		$html = array();
		
		$params = Secretary\Application::parameters();
		if( $params->get('entitySelect') == 1) {
				
			$db = JFactory::getDbo();
			$query = $db->getQuery(true)
					->select("id,title")
					->from($db->qn('#__secretary_entities'))
					->order('title ASC');
					
			$db->setQuery($query);
			$items = $db->loadObjectList();
			
			$html[] = JHtml::_('select.option', 0, JText::_('COM_SECRETARY_ENTITY') );
			foreach($items as $message) {
				$html[] = JHtml::_('select.option', $message->id, $message->title );
			}
			
		} 
		
		return $html;
		
	}
	
	public function getList( $default = 0, $name = 'jform[items][##counter##][entity]' )
	{
		$html = $this->getOptions();
		$result = '<select name="'.$name.'" id="jform_items_entity" class="form-control entity-select">'. JHtml::_('select.options', $html, 'value', 'text') . '</select>';
		return $result;
	}
}