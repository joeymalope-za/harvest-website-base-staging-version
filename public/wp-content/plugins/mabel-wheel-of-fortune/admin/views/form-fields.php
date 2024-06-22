<div class="form-field-builder">
	<div class="builder-row builder-header">
		<div class="builder-small-col"></div>
		<div class="builder-small-col" style="padding-left:0;"><?php _e('Include','mabel-wheel-of-fortune');?></div>
		<div style="padding-left:0;"><?php _e('ID','mabel-wheel-of-fortune');?></div>
		<div style="padding-left:0;"><?php _e('Name','mabel-wheel-of-fortune');?></div>
		<div style="padding-left:0;" class="builder-large-col"><?php _e('Placeholder text','mabel-wheel-of-fortune');?></div>
		<div style="padding-left:0;" class="builder-small-col"><?php _e('Required','mabel-wheel-of-fortune');?></div>
	</div>
	<div class="builder-row"><div class="builder-small-col"></div><div class="builder-small-col"><input checked="checked" disabled="" type="checkbox"></div><div><label><?php _e('Email','mabel-wheel-of-fortune');?></label></div><div class="builder-large-col"><input class="widefat mabel-form-element" type="text" name="email_placeholder" placeholder="<?php _e('Your email','mabel-wheel-of-fortune');?>" data-key="email_placeholder"></div><div class="builder-small-col"><input checked="checked" disabled="" type="checkbox"></div></div>
	<div class="builder-playground-wrapper">
			<div class="form-field-builder-loading" style="display: block;text-align: center;opacity: .75;">
				<?php _e("Loading...",'mabel-wheel-of-fortune'); ?>
			</div>
			<div class="form-field-builder-nofields" style="display: none;text-align: center;opacity: .75;">
				<?php _e("If your email list has extra fields (such as first name or last name), they will show up here.",'mabel-wheel-of-fortune'); ?>
			</div>
			<div class="builder-playground"></div>
	</div>
</div>

<script id="tpl-form-field" type="text/x-dot-template">
	{{~ it.fields :field:index}}
		<div class="builder-row" data-field-id="{{=field.id}}">
			<div class="builder-small-col"><span class="sorting-handle">☰</span></div>
			<div class="builder-small-col">
				<input {{? field.checked}}checked="checked"{{?}} class="field-include" type="checkbox" />
			</div>
            <div>
                <label for="field-{{=index}}">{{=field.id}}</label>
            </div>
			<div>
				<label for="field-{{=index}}">{{=field.title}}</label>
			</div>
			<div class="builder-large-col">
				<input class="widefat" value="{{=field.placeholder}}" type="text" name="field-placholder-{{=index}}" placeholder="<?php _e('Your placeholder text','mabel-wheel-of-fortune');?>">
			</div>
			<div class="builder-small-col">
				<input {{? field.required}}checked="checked"{{?}} class="field-required" type="checkbox">
			</div>
		</div>
	{{~}}
</script>
