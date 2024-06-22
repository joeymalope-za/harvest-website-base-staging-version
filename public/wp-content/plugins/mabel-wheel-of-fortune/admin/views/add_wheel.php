<div class="step-tracker-wrapper" id="add-wheel"
 data-slices="<?php  echo htmlspecialchars(json_encode($data['slices']), ENT_QUOTES, 'UTF-8'); ?>">
	<ul class="step-tracker steps-4">
		<li class="step current">
			<span></span><h2><?php _e('Data', 'mabel-wheel-of-fortune'); ?></h2>
		</li>
		<li class="step">
			<span></span><h2><?php _e('Design', 'mabel-wheel-of-fortune'); ?></h2>
		</li>
		<li class="step form-builder-step-tab" style="display: none;">
			<span></span><h2><?php _e('Form builder', 'mabel-wheel-of-fortune'); ?></h2>
		</li>
		<li class="step">
			<span></span><h2><?php _e('Chance & slices', 'mabel-wheel-of-fortune'); ?></h2>
		</li>
		<li class="step">
			<span></span><h2><?php _e('Settings', 'mabel-wheel-of-fortune'); ?></h2>
		</li>
	</ul>
	<div class="step-tracker-content">
		<div data-step="1" class="p-t-5">
			<div class="wof-name-wrapper">
				<p>
					<b>1) <?php _e("What is your wheel's name?",'mabel-wheel-of-fortune'); ?></b>
				</p>
				<input class="mabel-form-element skip-save m-t-3" type="text" value="<?php echo $data['default_name']; ?>" placeholder="<?php echo $data['default_name']; ?>" data-key="name" />
				<div class="p-t-1 extra-info"><?php _e('For easy reference', 'mabel-wheel-of-fortune'); ?></div>
			</div>
            <p style="padding-top:40px;">
                <b>2) <?php _e('How will you use your wheel?','mabel-wheel-of-fortune'); ?></b>
            </p>
            <select class="mabel-form-element skip-save m-t-3" name="usage" data-key="usage">
                <option value="popup"><?php _e('As a popup','mabel-wheel-of-fortune'); ?></option>
                <option value="sc"><?php _e('Embedded (with shortcode)','mabel-wheel-of-fortune'); ?></option>
            </select>
            <div class="m-t-2 wof-info-bubble info-bubble-sc">
				<?php _e("Selecting <b>Embedded</b> means you don't want to display the wheel as a popup, but rather inline in a post or page via the shortcode <b>[wof_wheel id=\"your wheel id\"]</b>",'mabel-wheel-of-fortune'); ?>
            </div>
			<p style="padding-top:40px;">
				<b>3) <?php _e('Which tool would you like to use to capture opt-ins?','mabel-wheel-of-fortune'); ?></b>
			</p>
			<select class="mabel-form-element skip-save m-t-3" name="list_provider"></select>
			<div class="m-t-2 wof-info-bubble info-bubble-tool-none">
				<?php _e('Selecting <b>None</b> allows users to play without inputting data. This is great if you just want to hand out prizes.','mabel-wheel-of-fortune'); ?>
			</div>
			<div class="m-t-2 wof-info-bubble info-bubble-tool-wordpress" style="display: none;">
				<?php _e('Your <b>WordPress</b> database will be used to store opt-ins.','mabel-wheel-of-fortune'); ?>
			</div>
			<div class="m-t-2 wof-info-bubble info-bubble-tool-custom" style="display: none;">
				<?php _e("Uzing <b>Zapier</b> allows you to connect the wheel to any other software via web hooks. You'll see the necessary settings under your wheel > Settings > Integrations.",'mabel-wheel-of-fortune'); ?>
			</div>
            <div class="m-t-2 wof-info-bubble">
                <?php _e('Worried about data collection? Read our guide on <a href="https://www.studiowombat.com/knowledge-base/data-collection-gdpr-privacy/?utm_source=wof&utm_medium=plugin&utm_campaign=info" target="_blank">data collection, GDPR, and privacy</a>', 'mabel-wheel-of-fortune') ?>
            </div>
			<div class="list-setting-wrapper p-t-5" style="display: none;">
				<p style="padding-top:40px;">
					<b>4) <?php _e('Which list would you like to use for this wheel?','mabel-wheel-of-fortune');?></b>
				</p>
				<select class="mabel-form-element skip-save m-t-3" name="list_list"></select>
			</div>

			<div class="m-t-5">
				<button class="mabel-btn-next-step mabel-btn"><?php _e('Next','mabel-wheel-of-fortune'); ?></button>
				<button class="btn-save-wheel mabel-btn btn-save-when-editing" style="display: none;"><?php _e('Save','mabel-wheel-of-fortune'); ?></button>
			</div>
		</div>
		<div data-step="2" class="p-t-5" style="display: none;">
			<div class="mabel-row skip-save">
				<div class="mabel-seven mabel-columns">
					<div class="wof-theme-wrapper">
						<?php \MABEL_WOF\Core\Common\Html::option($data['theme_setting']); ?>
					</div>
					<div class="slices-design-settings" style="margin-top:20px;">
						<?php \MABEL_WOF\Core\Common\Html::option($data['slices_design_settings']); ?>
					</div>
					<div class="extra-design-settings" style="margin-top:20px;">
						<?php \MABEL_WOF\Core\Common\Html::option($data['design_settings']); ?>
					</div>
				</div>
				<div class="mabel-five mabel-columns">
					<div class="wof-wheel-preview">
						<?php
						echo \MABEL_WOF\Core\Common\Html::view('wheel-shortcode',$data['wheels_vm']);
						?>
					</div>
				</div>
			</div>

			<div class="m-t-5 t-c">
				<button class="mabel-btn-prev-step mabel-btn mabel-secondary"><?php _e('Back','mabel-wheel-of-fortune');?></button>
				<button class="mabel-btn-next-step mabel-btn"><?php _e('Next','mabel-wheel-of-fortune');?></button>
				<button class="btn-save-wheel mabel-btn btn-save-when-editing" style="display: none;"><?php _e('Save','mabel-wheel-of-fortune');?></button>
			</div>
		</div>

		<div data-step="3" class="skip-save p-t-5" style="display: none;">
			<div class="form-builder-for-lists" style="display: none">
				<div class="wof-info-bubble">
					<?php _e('Build your opt-in form here. This is what the user needs to fill out before playing or seeing their prize. The fields you see below are coming from your selected op-in tool. Select the ones you want to include in the form.','mabel-wheel-of-fortune'); ?>
				</div>
				<?php
					\MABEL_WOF\Core\Common\Html::option($data['form_builder_for_lists']);
				?>
			</div>
			<div class="form-builder-for-other">
				<div class="wof-info-bubble">
					<?php _e("Build your opt-in form here. This is what the user needs to fill out before playing or seeing their prize. The 'primary email' field is always present. Add additional fields by clicking 'add field'.",'mabel-wheel-of-fortune'); ?>
				</div>
				<?php
				\MABEL_WOF\Core\Common\Html::option($data['form_builder_for_other']);
				?>
			</div>
			<div class="p-t-5 t-c">
				<button class="mabel-btn-prev-step mabel-btn mabel-secondary"><?php _e('Back','mabel-wheel-of-fortune');?></button>
				<button class="mabel-btn-next-step mabel-btn"><?php _e('Next','mabel-wheel-of-fortune');?></button>
				<button class="btn-save-wheel mabel-btn btn-save-when-editing" style="display: none;"><?php _e('Save','mabel-wheel-of-fortune');?></button>
			</div>
		</div>

		<div data-step="4" class="skip-save p-t-5" style="display: none;">
			<table class="form-table">
				<?php
				foreach($data['chance_settings'] as $o) {
					echo '<tr>';
					if(!empty($o->title))
						echo '<th scope="row">'.$o->title.'</th>';
					echo '<td>';
					\MABEL_WOF\Core\Common\Html::option($o);
					echo '</td></tr>';
				}
				?>
			</table>
			<table class="form-table wof-slice-wrapper wof-styled-table m-t-5">
				<thead>
					<th style="width:45px;"></th>
					<th><?php _e('Type', 'mabel-wheel-of-fortune') ?></th>
					<th><?php _e('Text on slice', 'mabel-wheel-of-fortune') ?></th>
					<th><?php _e('Value', 'mabel-wheel-of-fortune') ?></th>
					<th style="width:135px;"><?php _e('Chance', 'mabel-wheel-of-fortune') ?></th>
					<th class="wof-td-limit" style="width: 90px;display: none;"><?php _e('Limit', 'mabel-wheel-of-fortune') ?></th>
					<th style="width:100px;">&nbsp;</th>
				</thead>
				<tbody></tbody>
			</table>
			<div class="wof-total">
				<?php _e('Chance total','mabel-wheel-of-fortune'); ?>: <span class="wof-total-percentage"></span> %</th>
			</div>
			<p class="msg-bad msg-incorrect-percentage" style="display: none;">
				<?php _e("The total sum of chance should be 100. Please double check and adjust accordingly.",'mabel-wheel-of-fortune'); ?>
			</p>
			<div class="p-t-5 t-c">
				<button class="mabel-btn-prev-step mabel-btn mabel-secondary"><?php _e('Back','mabel-wheel-of-fortune'); ?></button>
				<button class="mabel-btn mabel-btn-next-step "><?php _e('Next','mabel-wheel-of-fortune'); ?></button>
				<button class="btn-save-wheel mabel-btn btn-save-when-editing" style="display: none;"><?php _e('Save','mabel-wheel-of-fortune'); ?></button>
			</div>
		</div>
		<div data-step="5" class="skip-save" style="display: none;">
			<table class="form-table wof-wheel-other-settings">
				<?php
				foreach($data['settings'] as $o) {
					echo '<tr>';
					if(!empty($o->title))
						echo '<th scope="row">'.$o->title.'</th>';
					echo '<td>';
					\MABEL_WOF\Core\Common\Html::option($o);
					echo '</td></tr>';
				}
                if(!empty($data['integration_settings'])){
                    foreach($data['integration_settings'] as $is) {
                        echo '<tr><td>';
                        \MABEL_WOF\Core\Common\Html::option($is);
                        echo '</td></tr>';
                    }
                }
				?>
			</table>

			<div class="p-t-5 t-c">
				<button class="mabel-btn-prev-step mabel-btn mabel-secondary"><?php _e('Back','mabel-wheel-of-fortune');?></button>
				<button class="btn-save-wheel mabel-btn"><?php _e('Save','mabel-wheel-of-fortune');?></button>
			</div>
		</div>
		<div class="t-c p-t-5" data-final-step style="display: none;">
				<b><?php _e("All done! Your wheel of fortune is now live.", 'mabel-wheel-of-fortune'); ?></b>
                <div class="p-t-5 wof-shortcode-hint" style="display: none">
                    <div><?php _e("The wheel's shortcode is", 'mabel-wheel-of-fortune'); ?>: </div>
                    <div class="p-t-2">
                        <code style="display: inline-block;padding:5px 12px;border-radius: 4px;" class="wof-shortcode-hint-code"></code>
                    </div>
                </div>
                <div class="p-t-5">
                    <button class="btn-start-over mabel-btn"><?php _e('Add new wheel', 'mabel-wheel-of-fortune'); ?></button>
                </div>
		</div>
	</div>
</div>


<script id="tpl-woc-slice-tr" type="text/x-dot-template">
	{{~ it.slices :value:index}}
	<tr data-idx="{{=index}}">
		<td style="text-align: center;">{{=index+1}}</td>
		<td>
			<select name="wof-slice-type">
				<option {{? value.type == 0}}selected="selected"{{?}} value="0"><?php _e('No Prize', 'mabel-wheel-of-fortune') ?></option>
				<option {{? value.type == 1}}selected="selected"{{?}} value="1"><?php _e('Coupon Code', 'mabel-wheel-of-fortune') ?></option>
                <?php echo $data['woo_active'] ? '<option {{? value.type == 5}}selected="selected"{{?}} value="5">' . __('Free gift (product)', 'mabel-wheel-of-fortune') . '</option>' : '' ?>
				<option {{? value.type == 2}}selected="selected"{{?}} value="2"><?php _e('Plain link', 'mabel-wheel-of-fortune') ?></option>
				<option {{? value.type == 3}}selected="selected"{{?}} value="3"><?php _e('Redirect', 'mabel-wheel-of-fortune') ?></option>
				<option {{? value.type == 4}}selected="selected"{{?}} value="4"><?php _e('Text/HTML', 'mabel-wheel-of-fortune') ?></option>
			</select>
		</td>
		<td>
			<input type="text" value="{{? value.label }}{{!value.label}}{{?}}" name="wof-slice-label" />
		</td>
		<td>
			<span class="td-content">
				<input type="text" value="{{? value.value }}{{!value.value}}{{?}}" name="wof-slice-value" />
				<input name="wof-slice-link" type="text" placeholder="<?php _e('https://yourlink.com/your-page','mabel-wheel-of-fortune'); ?>" value="{{? value.value }}{{!value.value}}{{?}}" style="display: none;" />

                <div class="mabel-autocomplete-wrapper free_gift_product" data-action="mb-wof-get-wc-product">
                    <input type="hidden"
                           name="wof-slice-gift"
                           value="{{? value.value }}{{!value.value}}{{?}}"
                    />
                    <div>
                    <input
                            style="background:white;border:none;width:50px;margin:0;padding:0;"
                            type="text"
                            placeholder="Search..."
                            class="mabel-formm-element"
                    />
                    </div>
                </div>

				<input name="wof-slice-redirect" type="text" placeholder="<?php _e('https://yourlink.com/your-page','mabel-wheel-of-fortune'); ?>" value="{{? value.value }}{{!value.value}}{{?}}" style="display: none;" />
				<textarea name="wof-slice-texthtml" style="display:none;border-radius: 4px;width:100%;height:85px;" placeholder="<?php _e('Your text here','mabel-wheel-of-fortune'); ?>">
					{{? value.value }}{{!value.value}}{{?}}
				</textarea>
				<input min="0" placeholder="<?php _e('Coupon amount between 0% - 100%','mabel-wheel-of-fortune'); ?>" max="100" value="{{? value.value }}{{!value.value}}{{?}}" type="number" name="wof-slice-wc-coupon-percentage"  style="display: none;"/>
				<input min="0" placeholder="<?php _e('Coupon amount','mabel-wheel-of-fortune'); ?>" value="{{? value.value }}{{!value.value}}{{?}}" type="number" name="wof-slice-wc-coupon-value"  style="display: none;"/>

			</span>
		</td>
		<td>
			<div class="wof-slice-chance-wrapper" style="display: none;">
				<input style="width:70px;" type="number" min="0" step=".25" max="100" value="{{? value.chance }}{{=value.chance}}{{??}}0{{?}}" name="wof-slice-chance" /> %
			</div>
		</td>
		<td  class="wof-td-limit" style="width: 90px;display:none;">
			<input type="number" min="-1" max="9999999" value="{{? value.limit }}{{=value.limit}}{{??}}-1{{?}}" name="wof-slice-limit" />
		</td>
		<td>
			<a style="display: none;" class="btn-wc-coupon-settings" data-slice="{{=index+1}}" href="#"><?php _e('More settings','mabel-wheel-of-fortune'); ?></a>
		</td>
	</tr>
	{{~}}
</script>

<div id="wof-wc-coupon-options" style="display: none">
	<table class="form-table skip-save">
		<?php
			foreach($data['woo_coupon_settings'] as $o) {
				echo '<tr>';
					if(!empty($o->title))
					echo '<th style="width: 125px;">'.$o->title.'</th>';
					echo '<td>';
						\MABEL_WOF\Core\Common\Html::option($o);
						echo '</td></tr>';
			}
		?>
	</table>
	<div class="modal-button-row">
		<a href="javascript:tb_remove(true);" class="mabel-btn"><?php _e('Done','mabel-wheel-of-fortune');?></a>
	</div>
</div>
