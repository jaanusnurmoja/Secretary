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

$user   = Secretary\Joomla::getUser();

$tables	= Secretary\Database::getTables();
$allowedSize = ini_get('upload_max_filesize');

$db     = Secretary\Database::getDbo(); 
$prefix = $db->getPrefix();


$directory = SECRETARY_ADMIN_PATH.'/application/install/samples';
$scanned_directory = array_diff(scandir($directory), array('..','.','.DS_STORE','_notes','index.html'));
?>

<div class="secretary-main-container database">
<div class="secretary-main-area">
    
    <h2 class="documents-title"><?php echo JText::_('COM_SECRETARY_DATABASE');?></h2>
    <hr />
    
    <form action="<?php echo JRoute::_('index.php?option=com_secretary&view=database'); ?>" method="post" enctype="multipart/form-data" name="adminForm">
        
        <h3><?php echo JText::_('COM_SECRETARY_ASSETS_ERRORS');?></h3>
        <?php 
		if($this->assetsErrors['status'] > 0) { 
			$ids = array();
			foreach($this->assetsErrors['no_parent'] as $child)
				$ids[] = $child->id . ' - '. $child->name .'<br>';
			echo JText::sprintf('COM_SECRETARY_ASSETS_ERRORS_DESC', $this->assetsErrors['status'], implode("",$ids) );
			?>
       		<button type="submit" name="assets_fix" class="btn"><?php echo JText::_('COM_SECRETARY_ASSETS_ERRORS_FIX');?></button>
       		<button type="submit" name="assets_clear" title="Remove all Secretary entries" class="btn"><?php echo JText::_('Clear assets table');?></button>
        <?php } else { ?>
            <?php echo JText::_('COM_SECRETARY_NONE');?>
        <?php } ?>
        <hr />
            
        <input type="hidden" name="task" value="database.fixassets" />
        <?php echo JHtml::_('form.token'); ?>
    </form>
    
    <form action="<?php echo JRoute::_('index.php?option=com_secretary&view=database'); ?>" method="post" enctype="multipart/form-data" name="adminForm">
       
        <div class="fullwidth">
            <h3><?php echo JText::_('COM_SECRETARY_EXPORT');?></h3>
            
            <div class="control-group">
                <select name="jform[exportTables][]" multiple="multiple" size="<?php echo count($tables ?? []);?>">
                <?php 
                foreach($tables AS $table) {
                    echo '<option value="'.$table.'">'.$prefix.'secretary_'. $table .'</option>';
                }
                ?>
                </select>
            </div>
            <div class="form-control">
                <button type="submit" name="export_sql" class="btn">SQL</button>
                <button type="submit" name="export_csv" class="btn">CSV</button>
                <button type="submit" name="export_xlm" class="btn">XML</button>
                <button type="submit" name="export_excel" class="btn">EXCEL</button>
                <button type="submit" name="export_json" class="btn">JSON</button>
            </div>
        </div>
    
        <hr />
        
        <div class="fullwidth">
            <h3><?php echo JText::_('COM_SECRETARY_IMPORT');?></h3>
            
            <div class="row">
            	<div class="col-md-6">
                <h4><?php echo JText::_('COM_SECRETARY_UPLOAD');?></h4>
				<?php echo JText::_('COM_SECRETARY_SETTINGS_DOCUMENT_ENDUNG') . ": <strong>.sql</strong><br>"; ?>
                <?php echo JText::_('COM_SECRETARY_DOCUMENT_SIZE_ALLOWED') . ': <strong>' . $allowedSize . "B</strong><br>"; ?>
                <input type="file" name="jform[import]" />
                </div>
            	<div class="col-md-6">
                <h4><?php echo JText::_('COM_SECRETARY_SAMPLE_DATA'); ?></h4>
                <select name="jform[import][]" multiple="multiple" size="<?php echo count($scanned_directory ?? []);?>">
                <?php 
                foreach($scanned_directory AS $item) {
                    echo '<option value="'.$item.'">'.$item .'</option>';
                }
                ?>
                </select>
                </div>
            </div>
            <button type="submit" name="import" class="btn"><i class="fa fa-upload"></i>&nbsp;<?php echo JText::_('COM_SECRETARY_SEND');?></button>
        </div>
        
        <input type="hidden" name="task" value="database.submit" />
        <?php echo JHtml::_('form.token'); ?>
            
    </form>
</div> 
</div> 
