(function($){
	$.widget("ui.formEditor", {
		// Constructor
		_init: function() {
			var self = this;
			self._bindButtons.apply(self);
			var fields = $("tbody tr[rel]", self.element);
			if (fields.length){
				fields.each(function(){
					var rel = parseInt($(this).attr("rel"));
					if (rel > self._fieldCounter)
						self._fieldCounter = rel;
				});
			} else
				self._fieldCounter = 0;
		},
		/**
		 *	Bind buttons actions (most are add/remove buttons)
		 */
		_bindButtons: function(){
			var self = this;
			// Add field
			$(".addField", self.element).click(function(){
				self._addField.apply(self);
			});
			// Remove fields
			$('tbody', self.element).click(function(evt){
				var elt = $(evt.target).closest('.removeField');
				if (elt.length){
					elt.closest('tr').remove();
					return;
				} else {
					elt = $(evt.target).closest('.addValue');
					if (elt.length)
						self._addValue.apply(self, [elt]);
					else {
						elt = $(evt.target).closest('.removeValue');
						if (elt.length)
							elt.closest('.value').remove();
					}
				}
			});
		},
		/**
		 *	Add a possible value to a field (used for radio, checkbox and select only)
		 *	@param element elt : inner element of the values'td
		 */
		_addValue: function(elt){
			elt = $(elt).closest('td');
			if (elt.length){
				var fieldNumber = elt.closest('tr').attr('rel');
				$('<div class="value"><span class="removeValue button-secondary">-</span><input type="text" name="fields[values]['+fieldNumber+'][]"/></div>').appendTo(elt);
			}
		},
		/**
		 *	Add a field to the form
		 */
		_addField: function(){
			var self = this;
			var row = $('<tr/>')
				.attr('rel', self._fieldCounter);
			
			var buttonTd = $('<td/>')
				.appendTo(row);
			var button = $('<span/>')
				.addClass('removeField')
				.addClass('button-secondary')
				.text('-')
				.appendTo(buttonTd);
				
			var labelTd = $('<td/>')
				.appendTo(row);
			var labelInput = $('<input/>')
				.attr('type', 'text')
				.attr('name', 'fields[name]['+self._fieldCounter+']')
				.appendTo(labelTd);
				
			var typeTd = $('<td/>')
				.appendTo(row);
			var typeInput = $('<select/>')
				.attr('name', 'fields[type]['+self._fieldCounter+']')
				.appendTo(typeTd);
			
			var types = self._getData("types");
			for(x in types)
				typeInput.append( '<option value="'+types[x]+'" >'+types[x]+'</option>' );
				
			var valuesTd = $('<td/>')
				.appendTo(row)
				.html('<div class="addValue button-secondary"><center>+</center></div>');

			$("tbody", self.element).append(row);
			self._fieldCounter++;
		}
	});
	
	$.extend($.ui.formEditor, {
		getter: "",
		defaults: {
			types:[ "text", "radio", "checkbox", "select" ]
		}
	});
})(jQuery);