<div class="wof-theme-data"
     data-backgrounds="<?php echo htmlspecialchars(json_encode($data['backgrounds']), ENT_QUOTES, 'UTF-8'); ?>"
     data-themes="<?php echo htmlspecialchars(json_encode($data['themes']), ENT_QUOTES, 'UTF-8'); ?>"></div>
<h3><?php _e('Colorful themes','mabel-wheel-of-fortune');?></h3>
<?php foreach($data['themes']['colorized_themes'] as $theme) { ?>
	<div class="wof-theme">
		<label for="wof-theme-<?php _e($theme['id']) ?>">
			<img src="<?php echo $theme['preview'] ?>" />
		</label>
		<input class="skip-save" <?php echo $theme['id'] === 'blue'?'checked':''; ?> name="wof-wheel-theme" type="radio" value="<?php _e($theme['id']) ?>" id="wof-theme-<?php _e($theme['id']) ?>" />
	</div>
<?php } ?>

<h3 style="clear:both;margin:0;padding-top:30px;"><?php _e('Tinted themes','mabel-wheel-of-fortune');?></h3>
<?php foreach($data['themes']['monochromatic_themes'] as $theme) { ?>
	<div class="wof-theme">
		<label for="wof-theme-<?php _e($theme['id']) ?>">
			<img src="<?php echo $theme['preview']; ?>" />
		</label>
		<input class="skip-save" <?php echo $theme['id'] === 'blue'?'checked':''; ?> name="wof-wheel-theme" type="radio" value="<?php _e($theme['id']) ?>" id="wof-theme-<?php _e($theme['id']) ?>" />
	</div>
<?php } ?>

<h3 style="clear:both;margin:0;padding-top:30px;"><?php _e('Seasonal themes','mabel-wheel-of-fortune');?></h3>
<?php foreach($data['themes']['seasonal_themes'] as $theme ) { ?>
	<div class="wof-theme">
		<div><?php _e($theme['title']) ?></div>
		<label for="wof-theme-<?php _e($theme['id']) ?>">
			<img src="<?php echo $theme['preview']; ?>" />
		</label>
		<input class="skip-save" <?php echo $theme['id'] === 'blue'?'checked':''; ?> name="wof-wheel-theme" type="radio" value="<?php _e($theme['id']) ?>" id="wof-theme-<?php _e($theme['id']) ?>" />
	</div>
<?php } ?>