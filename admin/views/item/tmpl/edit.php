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
?>

<div class="secretary-main-container">
 
<?php if($this->extension == 'fields') { ?>
<div class="field-item" style="display:none;">
	<div class="field-item-title">
		<input id="jform_values_##counter##_key" type="text" class="form-control" name="jform[values][##counter##][key]" placeholder="<?php echo JText::_('COM_SECRETARY_KEY');?>" value="##key##" />
    </div>
	<div class="field-item-values">
		<input id="jform_values_##counter##_value" type="text" class="form-control" name="jform[values][##counter##][value]" placeholder="<?php echo JText::_('COM_SECRETARY_NAME');?>" value="##value##" />
    </div>
    <div class="btn btn-default field-remove"><i class="fa fa-remove"></i></div>
</div>
<?php } ?>

<div class="secretary-main-area">
 
	<div class="fullwidth">
        <h2 class="documents-title">
            <span class="documents-title-first"><?php echo $this->title; ?></span>
            <?php if($this->extension !== 'settings') { ?>
                <a class="pull-right btn btn-default" href="<?php echo JRoute::_("index.php?option=com_secretary&view=items&extension=".$this->extension); ?>"><i class="fa fa-angle-double-left"></i>&nbsp;<?php echo $this->title; ?></a>
            <?php } ?>
        </h2>
        <hr />
    </div>
      
	<div class="secretary-toolbar clearfix">
		<?php $this->addToolbar(); ?>
	</div> 
        
    <fieldset class="form-horizontal">   
        <form action="<?php echo Secretary\Route::create('item', array('layout'=>'edit','extension'=>$this->extension,'id'=> (int) $this->item->id)); ?>" 
        method="post" enctype="multipart/form-data" name="adminForm" id="adminForm">
        
        	<?php  echo $this->loadTemplate($this->extension); ?>
        	
            <input type="hidden" name="extension" value="<?php echo $this->extension; ?>" />
            <input type="hidden" name="task" value="" />
            <?php echo $this->form->getInput('id'); ?>
            <?php echo JHtml::_('form.token'); ?>
        </form>
    </fieldset>
    
	<?php if( $this->extension == 'settings') { ?>
    <div class="secretary_tab_pane " style="display:none;" id="settings_access">
    
        <div class="tabbable tabs-left">
        
        	<ul class="nav nav-tabs">
    			<?php foreach($this->rulesList as $title => $rule) { ?>
            	<li class=" <?php if($title == 'component') echo 'active'; ?>"><a data-toggle="tab" href="#permission-<?php echo $title; ?>"><?php echo JText::_('COM_SECRETARY_'. strtoupper($title)); ?></a></li>
    			<?php } ?>
            </ul>
            
            <div class="tab-content">
    			<?php foreach($this->rulesList as $title => $rule) { ?>
            	<div id="permission-<?php echo $title ?>" class="tab-pane <?php if($title == 'component') echo 'active'; ?>"><?php echo $rule; ?>
                </div>
    			<?php } ?>
            </div>
            
        </div>
        
        <div class="alert alert-info"><?php echo JText::_('COM_SECRETARY_RULES_SETTING_NOTES_ITEM');?></div>
        
    </div>
	<?php } ?>
	
<script>
jQuery(document).ready(function($){
	$('#secretary_tabs_list li a').click(function(){
		$('#secretary_tabs_list li').removeClass('active');
		$(this).parent().addClass('active');
		$('.secretary_tab_pane').hide();
		var tabpane = $(this).data('tabcontent'); 
		$('#'+tabpane).show();
	});
});
</script>

</div>

</div>