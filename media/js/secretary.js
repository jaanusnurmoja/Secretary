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
  

// Global Objects
var Secretary = {
	Helpers : {}
};

// Auxiliary functions
Secretary.Helpers = { 
		
	htmlEntities : function(str) {
		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	},
	
	stripslashes : function(str) {
	  return (str + '')
		.replace(/\\(.?)/g, function(s, n1) {
		  switch (n1) {
			case '\\':
			  return '\\';
			case '0':
			  return '\u0000';
			case '':
			  return '';
			default:
			  return n1;
		  }
		});
	},

	cleanJSONbreaks : function(str){
		return str.replace(/BREAK/g, "\n");
	},
};

var chosenSubject = {};

// Global Function to print and activate Fields
Secretary.printFields = function(fields) {
	jQuery( document ).ready(readyFn);
	function readyFn() {
		return Secretary.Fields(fields);
	}
};

(function( $, Secretary ) {
	
	$(document).ready(readyFn);
	
	function readyFn() {

		Secretary.Fields = function( fields ) {
 
			/**
			 * Add a field as filled out HTML
			 */
			function addField(id, hard, title, box, description){
				var counter = $('#field-add').attr('counter'); 
				var html = $('.field-item:first').html();
				html = html.replace(/##values##/g, Secretary.Helpers.stripslashes(box));
				html = html.replace(/##counter##/g, counter);
				html = html.replace(/##id##/g, id);
				html = html.replace(/##hard##/g, hard);
				html = html.replace(/##title##/g, title);
				html = html.replace(/##description##/g, description);
				var parent = $('<div class="field-item '+hard+'">' + html + '</div>');
				if(description.length < 1) parent.find('.tooltip-toggle').remove();
				parent.appendTo('.fields-items').show();
				$('#field-add').attr('counter', parseInt(counter) + 1);
			};

			/**
			 * Click Event
			 * Adds a new field
			 */
			$('#field-add').click(function(){ 
				var id = $('#getfields').val();
				var ext = $('#getfields').data('ext');
				var json = getFieldObject(id,ext); 
				if(json) addField(json.id, json.hard, Secretary.Helpers.htmlEntities(json.title), json.box,json.description );
				return false;						
			});
			
			/**
			 * Run through all fields, get JSON data and create HTML
			 */
			function printFields (fields) {
				var ext = $('#getfields').data('ext');
				for(var i in fields){
					if(fields.hasOwnProperty(i) ){
						for(var key in fields[i]){
							if(typeof(fields[i][key][0]) !== 'undefined')
							{
								// Get data
								var json = getFieldObject(fields[i][key][0], ext, fields[i][key][2] );
								// Print data as HTML
								if(json !== null){ 
									addField(
										json.id,
										json.hard,
										Secretary.Helpers.htmlEntities(fields[i][key][1]),
										Secretary.Helpers.cleanJSONbreaks(json.box),
										json.description
									);
								}
							}
						}
					} 
				}
			}
			
			/**
			 * Calls an AJAX and returns the data
			 */
			function getFieldObject(id,extension,standard) {
				var json = null;
	
				var input = {};
				input.id = id;
				input.extension = extension;
				if(typeof(standard) !== 'undefined') {  input.standard = encodeURIComponent( standard.replace(/#/g, "") ); }
	
				$.ajax({
					  type: "POST",
					  async: false,
					  url: "index.php?option=com_secretary&task=ajax.getField",
					  dataType: "json",
					  data: input,
					  success: function(data){
							json = data;
						},
					});
	
				return json;
			}

			// First output
			printFields(fields); 
			
			$('.field-remove').live('click', function(){
				$(this).parents('.field-item').remove();
				return false;			
			});
			
		};
	
		Secretary.Ajax = {
			
			call : function(container, task, id)
			{
				$(container).addClass('ui-autocomplete-loading');
				$.getJSON(
					"index.php?option=com_secretary&task="+task+"&id=" + id ,
					function(data){
						$(container).removeClass('ui-autocomplete-loading');
						$(container).replaceWith('<div class="btn btn-email-disable">'+ data.msg+'</div>');
					}
				);
			},
			
		};
		
		Secretary.submitbutton = function(task) {
			document.adminForm.task.value = task;
			document.adminForm.submit();
		};
		
		//-----------------	S e a r c h ----------------------------------------------

		//--- Assign methods ----------------------------------------------------

		Secretary.Search = {
				
			drawBlockInput : function(container, title) {
				$(container).parent().children('div.input-blocked').remove();
				$(container).parent().prepend('<div class="input-blocked">'+ title + Secretary.Search.removeInput+'</div>');
				$(container).hide();
			},
			
			drawBudgetContainer : function( item, container ) {
				
				if(typeof(item.id) == 'undefined')
					return;
				
				var container = container || "input.search-documents";
				Secretary.Search.drawBlockInput(container, item.value);
				$("#jform_document_id").val(item.id);
					
				$('.budget').empty();
				$('.budget').append(
					'<div class="budget-total">'+ item.total + ' ' + item.currency + '</div>' +
					'<div class="budget-category"><a rel="{size: {x: 800, y: 500}, handler:\'iframe\'}" href="index.php?option=com_secretary&view=document&id='+item.id+'&tmpl=component&layout=preview" class="modal" target="_blank">'+ item.category + ' / '+ item.created + '</a></div>'
				)
			},
			
			extractLast : function ( term ) {
				split = function ( val ) {
					return val.split( /;\s*/ );
				};
				return split( term ).pop();
			},
			
			removeInput : '<span class="removeInput">x</span>',
		};
		
		/**
		 * Search for Documents
		 */
		$( "input.search-documents" ).live('focus', function() {
			$(this).autocomplete({
				source: 'index.php?option=com_secretary&task=ajax.search&section=documents', 
				minLength:1,
				open: function(event, ui) { $(".ui-autocomplete").css("z-index", 1000); },
				select: function( event, ui ) {
					var parent = $(this).parent();
					if(parent.hasClass('controls')) {
						Secretary.Search.drawBudgetContainer( ui.item, this );
					} else if(parent.hasClass('table-item-col-2')) {
						var row = parent.parent();
						row.find('.table-item-nr').val( ui.item.nr );
						row.find('.table-item-created').val( ui.item.created );
						row.find('.table-item-deadline').val( ui.item.deadline );
						row.find('.table-item-price').html( Number( ui.item.subtotal ) );
						row.find('.table-item-taxrate').html( Number( ui.item.tax ) );
						row.find('.table-item-col-5 span').html( Number( ui.item.total ) );
						row.find('input.table-item-total').val( Number( ui.item.total ) );
	
						row.find('.add-subject-as-contact span').html( ui.item.subject.fullname );
						row.find('input.table-item-subjectid').val( Number( ui.item.subjectid ) );
						Secretary.Document.calculate.total();
						$(this).next().val(ui.item.id);
						chosenSubject = ui.item.subject;
	
					}
				}
			})
			.autocomplete( "instance" )._renderItem = function( ul, item ) {
				return $( "<li>" )
				.append( '<a><span class="ui-menuitem-value">'+ item.value + '</span><br><span class="ui-menuitem-sub">'+ item.subject.fullname +'<br>'+ item.total + ' '+ item.currency  + '</span></a>' )
				.appendTo( ul );
			};
		});
		
		if(typeof(budget) !== 'undefined') {
			Secretary.Search.drawBudgetContainer(budget,"input.search-documents");
		}
		
		if ( $( ".search-locations" ).length)
		{
			var ext = $("input.search-locations").data("extension");
			$( "input.search-locations" ).autocomplete({
				source: 'index.php?option=com_secretary&task=ajax.search&section=locations&extension='+ext, 
				minLength:2,
				open: function(event, ui) {
					$(".ui-autocomplete").css("z-index", 1000);
				},
				select: function( event, ui ) {
					Secretary.Search.drawBlockInput(this, ui.item.title);
					$("#jform_location_id").val(ui.item.id);
				}
			})
			.autocomplete( "instance" )._renderItem = function( ul, item ) {
				return $( "<li>" )
				.append( '<a><span class="ui-menuitem-value">'+ item.title + '</span><br><span class="ui-menuitem-sub">'+ item.street + ', '+ item.zip + ' ' + item.location + '</span></a>' )
				.appendTo( ul );
			};
		};
	
		/**
		 * Search for contact locations
		 */
		$("input.search-subject-zip, input.search-subject-location").live('focus', function(){
			var type = (this.id == 'jform_subject_zip') ? 'zip' : 'location';
			$(this).autocomplete({
				source: 'index.php?option=com_secretary&task=ajax.searchSubjectLocation&type='+type, 
				minLength:2,
				open: function(event, ui) { $(".ui-autocomplete").css("z-index", 1000); },
				select: function( event, ui ) {
					$('#jform_subject_zip').val(ui.item.zip);
					$('#jform_subject_location').val(ui.item.location);
				}
			})
			.autocomplete( "instance" )._renderItem = function( ul, item ) {
				return $("<li>").append( '<a><span class="ui-menuitem-value">'+ item.zip +' '+ item.location + '</span></a>' ).appendTo(ul);
			};
		});
		
		if ( $( ".search-features" ).length)
		{
			
			var theList = '.posts.multiple-input-selection';
			var source = $(theList).data("source");
			var extension = $(theList).data("extension");
			var counter = $(theList).data("counter");
			
			/**
			 * Connections Object
			 */
			Secretary.Features = {
				clear : function() { $(theList).focusout(function(){ $('.search-features').val(''); }); } ,
				input : function(name,type,key,counter) {
							return '<input type="'+type+'" name="jform[features]['+counter+']['+name+']" value="'+ key +'">';
						},
				textarea : function(name,key,counter) {
							return '<textarea name="jform[features]['+counter+']['+name+']" class="fullwidth" placeholder="...">'+ key +'</textarea>';
						},
				results : function() {
					var counter = 0;
					for (var key in featuresList) {
					   if (featuresList.hasOwnProperty(key)) {
						   var subject = featuresList[key];
							var li = '<div class="added-post clearfix">' + 
								'<div class="added-post-left">'+ 
								'<div class="added-post-title">'+ subject.firstname + ' ' + subject.lastname + 
								Secretary.Features.input('id','hidden',subject.id,counter) +  '</div>' ;
								delete subject.id; delete subject.firstname; delete subject.lastname;
							li += Secretary.Features.textarea('note', subject.note, counter);
								delete subject.note;
									
								for (var attributes in subject) { 
									li += Secretary.Features.input( attributes ,'hidden', subject[attributes] ,counter);
								}
								
							li += '</div>' + Secretary.Search.removeInput + '</div>';
								
							$(li).prependTo('div.posts');
							counter++;
					   }
					}
				}
			};
			
			/**
			 * Autocomplete Contacts search
			 */
			$( ".search-features" ).autocomplete({
				source: function( request, response ) {
					$.getJSON( 'index.php?option=com_secretary&task=ajax.search&section=subjects&source='+ source, {
					term: Secretary.Search.extractLast( request.term )
					}, response );
				},
				focus: function() { return false; },
				search: function() {
					var term = Secretary.Search.extractLast( this.value );
					if ( term.length < 2 ) {
						return false;
					}
				},
				select: function( event, ui ) {
					var li = '<div class="added-post clearfix">' + 
								'<div class="added-post-left">'+ 
									'<div class="added-post-title">'+ ui.item.value + 
									Secretary.Features.input('id','hidden', ui.item.id, counter) + '</div>' + 
									Secretary.Features.textarea('note', '', counter) + 
								'</div>' + 
								Secretary.Search.removeInput + 
							'</div>';
					$(li).prependTo('div.posts');
					$(this).val('');
					counter++;
					return false;
				}
			})
			.autocomplete( "instance" )._renderItem = function( ul, item ) {
				return $( "<li>" )
				.append( '<a><span class="ui-menuitem-value">'+ item.value + '</span><br><span class="ui-menuitem-sub">'+ item.street + ', '+ item.zip + ' ' + item.location + '</span></a>' )
				.appendTo( ul );
			};
			
			Secretary.Features.clear();
			
			if( typeof(featuresList) !== 'undefined' ) { 
				Secretary.Features.results();
			}
			
		}
		
		$('.removeInput').live('click',function() {
			var container = $(this).parent();
			var control = container.parent();
			container.remove();
			if( !control.find('input.search-block-input').is(':visible') ) {
				control.find('input.search-block-input').show();
				control.find('div.budget').empty();
				control.children('input').val('');
			}
		});
					
		$('.btn-submittask').live('click', function () {
			var form = $(this).parents('form:first');
			var formTask = form.children('#form-task');
			var value = $(this).data("value");
			formTask.val(value);
			form.submit();
		});
		
		
		//--------	Sidebar ----------------------------------------
		
		$(".secretary-toggle-sidebar").click(function(){
			$('.secretary-container').toggleClass('active');
		});
	
		$("#show-sidebar").hide();
			
		$("#document-repetition-check").live('change', function() {
			$(this).next('.repetition-container').slideToggle().toggleClass('out');
		});
		
		$(".fa.pull-right").click(function() {
			var target = $(this).data('target');
			$(target).slideToggle().toggleClass('out');
			$(this).toggleClass('fa-angle-left fa-angle-down');
		});
		 
		$('#sidebar-angle').click(function() {
			
			$(this).toggleClass('show-sidebar hide-sidebar');
			$('.nav-item-text').toggleClass('hide-sidebar-text');
			
			var children = $(this).children();
			if(children.hasClass('.fa-angle-left'))
				children.toggleClass('fa-angle-left fa-angle-right');
			else 
				children.toggleClass('fa-angle-right fa-angle-left');
			
			var angle = $('.secretary-sidebar-container').find('.fa.pull-right');
			if(angle.hasClass('.fa-angle-down'))
				angle.toggleClass('fa-angle-down fa-angle-right');
			else 
				angle.toggleClass('fa-angle-left fa-angle-right');
			
			var down = children.hasClass('fa-angle-right');
			$('.secretary-sidebar-container').toggleClass('hidden-sidebar');
			$.ajax({ 
				url : 'index.php?option=com_secretary&task=ajax.toggleSidebar&v=' + (+ down)
			})
		});
		
		$( "input[type=checkbox]" ).on( "click", function() {
			var n = $( "input:checked" ).length;
			if(n > 0) {
				$('.secretary-container button.hidden-toolbar-btn').fadeIn();
			} else {
				$('.secretary-container button.hidden-toolbar-btn').fadeOut();
			}
		});
	 
		//-----------------------------------------------------------------------
	
		//------------- Configuration - Access ----------------------------------
		
		$('#settings_access .input-small').change(function(){
			console.clear();
			var selectBox = $(this);
			var input = {};
			input.section = $(this).data('section');
			input.action = $(this).data('action');
			input.group = $(this).data('group');
			input.value = $(this).val();
			var loader = 'background: url(../media/system/images/modal/spinner.gif);display:inline-block;width:16px;height:16px;';
			selectBox.next().attr('style',loader);
			selectBox.next().removeClass('icon-save');
			$.ajax({
	            type: "POST", 
	            url: "index.php?option=com_secretary&task=ajax.updatePermission",
	            dataType: "json",
	            data: input
			}).done(function(){
				selectBox.next().removeAttr('style');
				selectBox.next().addClass('icon-save');
			});
		});
		
		$('select#jform_catid').change(function(){
			var v = $(this).val();
			$('input#catid').val(v);
		});
		
	    $('.custom-columns-btn').click(function(){
	        $(this).toggleClass('active');
			$('.chk_items_container').slideToggle();
	    });

		$('.secretary-status-button').click(function(){
			var element = $(this); 
			element.empty();
			element.html('<div class="loading-gif" style="height:30px"></div>');
			var url = 'index.php?option=com_secretary&task=ajax.setStates';
			var data =  {'section' : element.data('section'), 'id' :  element.data('id') };
			$.ajax({
	            type: "POST",
	            url: url,
	            data: data,
			}).done(function(response) {
				element.empty();
				element.html(response);
	        });
		});
		
		$('.secretary-sort .move-up').click(function(){
			var parent = $(this).closest('.secretary-row-inner').parent();
			if(parent.hasClass('secretary-sort-row')) {
				var before = parent.prev();
				parent.insertBefore(before);
			}
		});
		
		$('.secretary-sort .move-down').click(function(){
			var parent = $(this).closest('.secretary-row-inner').parent();
			if(parent.hasClass('secretary-sort-row')) {
				var next = parent.next();
				parent.insertAfter(next);
			}
		});
		
		/**
		 * Configuration View
		 * Toggle PDF and show info
		 */
		$('#pdf_select').change(function() {
			var value = $(this).val();
			$('.secretary-desc').children().hide();
			switch(value) {
				case 'mpdf': $('.secretary-desc #mpdf').show(); break;
				case 'mpdf7': $('.secretary-desc #mpdf7').show(); break;
				case 'dompdf': $('.secretary-desc #dompdf').show(); break;
			}
		});
	}
}( jQuery, Secretary));	