<div id="wof-form-builder-modal" style="display: none;">
    <div class="thickbox-content">
        <table class="form-table add-form-builder-field-table">
            <tr>
                <th><?php _e('Type','mabel-wheel-of-fortune');?></th>
                <td>
                    <select name="wof-form-builder-type">
                        <option value="text"><?php _e('Text','mabel-wheel-of-fortune');?></option>
                        <option value="textarea"><?php _e('Multiline text','mabel-wheel-of-fortune');?></option>
                        <option value="number"><?php _e('Number','mabel-wheel-of-fortune');?></option>
                        <option value="dropdown"><?php _e('Select list','mabel-wheel-of-fortune');?></option>
                        <option value="email"><?php _e('Email','mabel-wheel-of-fortune');?></option>
                        <option value="consent_checkbox"><?php _e('Consent checkbox','mabel-wheel-of-fortune');?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php _e('Label','mabel-wheel-of-fortune');?></th>
                <td>
                    <input type="text" name="wof-form-builder-placeholder" placeholder="<?php _e('Your label','mabel-wheel-of-fortune');?>" />
                </td>
            </tr>
            <tr class="wof-form-builder-listitems" style="display: none">
                <th><?php _e('List items','mabel-wheel-of-fortune');?></th>
                <td>
                    <div class="wof-form-builder-listitem">
                        <input type="text" class="wof-form-builder-listitem-text" placeholder="<?php _e('Your list item','mabel-wheel-of-fortune');?>" />
                        <a href="#" class="wof-form-builder-btn-del-listitem mabel-btn mabel-secondary small"><i class="dashicons dashicons-trash"></i></a>
                    </div>
                    <a href="#" class="wof-form-builder-btn-listitem mabel-btn mabel-secondary" style="margin-top:10px">+</a>
                </td>
            </tr>
            <tr class="formbuilder-add-field-required-row">
                <th><?php _e('Required','mabel-wheel-of-fortune');?></th>
                <td>
                    <input type="checkbox" name="wof-form-builder-required" id="wof-fb-cb"/> <label for="wof-fb-cb"><?php _e('This field is required','mabel-wheel-of-fortune');?></label>
                </td>
            </tr>
        </table>
    </div>
    <div class="mabel-modal-button-row">
        <a href="#" class="btn-add-form-builder-field mabel-btn"><?php _e('Add','mabel-wheel-of-fortune');?></a>
    </div>

</div>

<table class="form-table wof-styled-table wof-field-builder-table">
	<thead>
		<tr>
			<th colspan="10" style="text-align: right;">
				<a title="<?php _e('Add a field to the opt-in form','mabel-wheel-of-fortune');?>" href="#TB_inline?width=500&height=450&inlineId=wof-form-builder-modal" class="thickbox">
					<?php _e('Add new field','mabel-wheel-of-fortune');?>
				</a>
			</th>
		</tr>
		<tr>
			<th></th>
			<th><?php _e('ID','mabel-wheel-of-fortune');?></th>
			<th><?php _e('Label','mabel-wheel-of-fortune');?></th>
			<th><?php _e('Type','mabel-wheel-of-fortune');?></th>
			<th><?php _e('Required','mabel-wheel-of-fortune');?></th>
			<th></th>
		</tr>

	</thead>
	<tbody>
	</tbody>
</table>

<script id="tpl-wof-field-builder-other-row" type="text/x-dot-template">
	{{~ it.fields :value}}
	<tr class="form-field-tr" data-field-id="{{=value.id}}" data-options="{{? value.options}}{{=btoa(escape(JSON.stringify(value.options)))}}{{?}}">
		<td>
			<span class="sorting-handle">☰</span>
		</td>
        <td>
            {{=value.id}}
        </td>
		<td class="field-placeholder">
            {{? value.type === 'primary_email'}}
            <input class="widefat mabel-form-element" type="text" name="email_placeholder" value="{{=value.placeholder}}" placeholder="<?php _e('Your email','mabel-wheel-of-fortune');?>" data-key="email_placeholder">
            {{??}}
			{{=value.placeholder}}
            {{?}}
		</td>
        <td class="field-type" style="text-transform: capitalize;">
            {{=value.type.replace('_',' ')}}
        </td>
		<td class="field-required">
			{{? value.required}}
			<?php _e('Yes','mabel-wheel-of-fortune');?>
			{{??}}
			<?php _e('No','mabel-wheel-of-fortune');?>
			{{?}}
		</td>
		<td style="text-align: right;">
            {{? value.type !== 'primary_email'}}
            <a href="#" class="btn-form-builder-other-remove-field"><?php _e('Remove','mabel-wheel-of-fortune');?></a>
            {{?}}
        </td>
	</tr>
	{{~}}
</script>