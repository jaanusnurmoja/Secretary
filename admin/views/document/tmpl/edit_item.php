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

$app	= \Secretary\Joomla::getApplication();
$toggleTaxRateColumn= (int) $app->getUserState('filter.toggleTaxRateColumn', 1);
$taxrateClass = ($toggleTaxRateColumn== 0) ? 'none' : 'block';
?>

<li class="table-item clearfix dd-item" style="display:none;" data-id="0">
	
    <div class="dd-handle"><i class="fa fa-sort"></i></div>
    
    <div class="table-item-col-1">
        <div class="control-group">
            <div class="input-append">
            
                <input type="text" class="table-item-quantity input-mini validate-number" value="##quantity##"  min="0" name="jform[items][##counter##][quantity]" placeholder="<?php echo JText::_('COM_SECRETARY_QUANTITY');?>" style="float:left;" />
                
                <?php if( $this->state->params->get('entitySelect') == 1) { ?>
                    <?php echo $this->entityoptions; ?>
                <?php } elseif( $this->state->params->get('entitySelect') == 0) { ?>
                    <input type="text" value="##entity##"  class="table-item-entity input-mini validate-number" name="jform[items][##counter##][entity]" placeholder="<?php echo JText::_('COM_SECRETARY_ENTITY');?>" />	
                <?php } ?>
                    
            </div>
        </div>
    </div>
    
    <div class="table-item-col-pno">
    	<input type="text" value="##pno##"  class="table-item-pno validate-number" name="jform[items][##counter##][pno]" placeholder="<?php echo JText::_('COM_SECRETARY_PRODUCT_NO');?>" />	
    </div>
    
    <div class="table-item-col-2">
    
        <div class="table-item-head clearfix">
                    
            <input type="text" class="table-item-title pro" id="title_##counter##" value="##title##" name="jform[items][##counter##][title]" placeholder="<?php echo JText::_('COM_SECRETARY_PRODUCT_TITLE');?>" />
            <div class="open-desc hasTooltip" data-original-title="<?php echo JText::_('COM_SECRETARY_PRODUCT_DESC'); ?>"><span class="fa fa-plus"></span></div>
        	<textarea style="display:none;" class="table-item-title form-control" name="jform[items][##counter##][description]" placeholder="<?php echo JText::_('COM_SECRETARY_PRODUCT_DESC');?>">##desc##</textarea>
        </div>
    </div>
    
    <div class="table-item-col-3">
        <input type="number" step="0.01" class="table-item-price validate-number" value="##price##" name="jform[items][##counter##][price]" placeholder="<?php echo JText::_('COM_SECRETARY_EINZELPREIS');?>" />
    </div>
    
    <div class="table-item-col-4" style="display:<?php echo $taxrateClass ?>">
        <input type="text" class="btn-no-bg table-item-taxrate validate-number" value="##taxRate##" name="jform[items][##counter##][taxRate]" placeholder="0" />
        <span class="table-item-taxrate-perc">%</span>
    </div>
    
    <div class="table-item-col-5">
        <input type="number" step="0.01" class="table-item-total validate-number" value="##total##"  name="jform[items][##counter##][total]" placeholer="<?php echo JText::_('COM_SECRETARY_GESAMTPREIS');?>" />
    </div>
    
    <div class="table-item-col-6">
        <button type="button" class="btn table-item-remove" name="jform_items_##counter##_remove"><i class="fa fa-remove"></i></button>
    </div>

</li>


<li class="table-item-document clearfix dd-item" style="display:none;" data-id="0">
	
    <div class="dd-handle"><i class="fa fa-sort"></i></div>
    
    <div class="table-item-col-1"><div class="btn-blank-text"><?php echo JText::_('COM_SECRETARY_DOCUMENT'); ?></div></div>
    
    <div class="table-item-col-pno">&nbsp;</div>
    
    <div class="table-item-col-2">
        <input type="text" class="search-documents" id="title_##counter##" value="##title##" name="jform[items][##counter##][title]" placeholder="<?php echo JText::_('COM_SECRETARY_PRODUCT_TITLE');?>" />
        <input type="hidden" value="##id##" name="jform[items][##counter##][id]"  />
        <div class="add-subject-as-contact"><span>##subject##</span><?php echo JText::_('COM_SECRETARY_AS_CONTACT');?></div>
        <input type="hidden" value="##subjectid##" name="jform[items][##counter##][subjectid]" class="table-item-subjectid" />
    </div>
    
    <div class="table-item-col-3">
        <div class="btn-blank-text">
        <span class="table-item-price">##price##</span> <?php echo $this->item->currencySymbol; ?>
        </div>
        <input type="hidden" name="jform[items][##counter##][subtotal]" />
    </div>
    
    <div class="table-item-col-4">
        <div class="btn-blank-text">
        <span class="table-item-taxrate">##tax##</span> <?php echo $this->item->currencySymbol; ?>
        </div>
        <input type="hidden" name="jform[items][##counter##][tax]" />
    </div>
    
    <div class="table-item-col-5">
        <div class="btn-blank-text"><span class="table-item-total">##total##</span> <?php echo $this->item->currencySymbol; ?></div>
        <input class="table-item-total" name="jform[items][##counter##][total]" type="hidden" value="##total##" min="0.0" step="0.01" />
        <input type="hidden" name="jform[items][##counter##][deadline]" value="##deadline##" class="table-item-deadline" />
        <input type="hidden" name="jform[items][##counter##][created]" value="##created##" class="table-item-created" />
        <input type="hidden" name="jform[items][##counter##][nr]" value="##nr##" class="table-item-nr" />
    </div>
    
    <div class="table-item-col-6">
        <button type="button" class="btn table-item-remove" name="jform_items_##counter##_remove"><i class="fa fa-remove"></i></button>
    </div>


</li>
