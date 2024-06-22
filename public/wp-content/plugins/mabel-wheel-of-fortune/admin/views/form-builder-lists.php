<div id="wof-custom-field-modal" style="display: none;">
	<table class="form-table wof-custom-field-table">
		<tr>
			<th><?php _e('Type','mabel-wheel-of-fortune');?></th>
			<td>
				<select name="wof-form-builder-type">
					<option value="consent_checkbox"><?php _e('Consent Checkbox','mabel-wheel-of-fortune');?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th><?php _e('Label','mabel-wheel-of-fortune');?></th>
			<td>
				<input type="text" name="wof-form-builder-placeholder" placeholder="<?php _e('Your label (can contain some HTML)','mabel-wheel-of-fortune');?>" />
			</td>
		</tr>
		<tr class="formbuilder-add-field-required-row">
			<th><?php _e('Required','mabel-wheel-of-fortune');?></th>
			<td>
				<input type="checkbox" name="wof-form-builder-required" id="wof-add-custom-field-cb"/> <label for="wof-add-custom-field-cb"><?php _e('This field is required','mabel-wheel-of-fortune');?></label>
			</td>
		</tr>
	</table>
	<div class="mabel-modal-button-row">
		<a href="#" class="btn-wof-add-custom-field mabel-btn"><?php _e('Add','mabel-wheel-of-fortune');?></a>
	</div>
</div>

<table class="form-table wof-styled-table wof-field-builder-table">
	<thead>
	<tr>
		<th colspan="10" style="text-align: right;">
			<a title="<?php _e('Add a custom field to the opt-in form','mabel-wheel-of-fortune');?>" href="#TB_inline?width=500&height=350&inlineId=wof-custom-field-modal" class="thickbox">
				<?php _e('Add new field','mabel-wheel-of-fortune');?>
			</a>
		</th>
	</tr>
	<tr>
		<th></th>
		<th><?php _e('Include','mabel-wheel-of-fortune');?></th>
		<th><?php _e('ID','mabel-wheel-of-fortune');?></th>
		<th><?php _e('Name','mabel-wheel-of-fortune');?></th>
		<th><?php _e('Label','mabel-wheel-of-fortune');?></th>
		<th><?php _e('Required','mabel-wheel-of-fortune');?></th>
	</tr>
	</thead>
	<tbody>
	</tbody>
	<div class="form-builder-for-lists-loading t-c">
		<?php _e('Loading...','mabel-wheel-of-fortune');?>
	</div>
</table>

<script id="tpl-wof-field-builder-lists-row" type="text/x-dot-template">
	{{~ it.fields :field:index}}
	<tr class="form-field-tr" data-field-type="{{=field.type}}" data-field-id="{{=field.id}}" data-options="{{? field.options}}{{=btoa(escape(JSON.stringify(field.options)))}}{{?}}">
		<td class="builder-small-col"><span class="sorting-handle">☰</span></td>
		<td class="builder-small-col">
			<input {{? field.checked}}checked="checked"{{?}} {{? field.type === 'primary_email'}}disabled{{?}} class="field-include" type="checkbox" />
		</td>
		<td>
			{{=field.id}}
		</td>
        <td>
            {{=field.title}}
        </td>
		<td>
			<input class="widefat" value="{{!field.placeholder}}" type="text" placeholder="<?php _e('Your label','mabel-wheel-of-fortune');?>">
		</td>
		<td>
			<input {{? field.type === 'primary_email'}}disabled{{?}} {{? field.required}}checked="checked"{{?}} class="field-required" type="checkbox">
		</td>
	</tr>
	{{~}}
</script>