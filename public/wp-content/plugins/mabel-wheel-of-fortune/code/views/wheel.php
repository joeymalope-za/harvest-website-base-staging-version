<?php
	/** @var \MABEL_WOF\Code\Models\Wheel_Model $model */
	use MABEL_WOF\Core\Common\Managers\Settings_Manager;
	use MABEL_WOF\Code\Services\Wheel_service;

	$degree_per_slice = 360/$model->amount_of_slices;

?>
<style>
	<?php
	    $css_bg = $model->get_background();
	    $css_bottom_bg = $model->get_background_bottom();
	    if(!empty($css_bg)) {
	?>
		.wof-wheel[data-id="<?php echo $model->id; ?>"] .wof-bg{<?php echo $css_bg; ?>}
	<?php }
	    if(!empty($css_bottom_bg))
	        echo '.wof-wheel[data-id="'. $model->id.'"] .wof-bottom-bg{'.$css_bottom_bg.'}';
    ?>
	<?php if($model->has_setting('fgcolor')){ ?>
		div.wof-wheel[data-id="<?php echo $model->id; ?>"] .wof-fgcolor{ color:<?php echo $model->fgcolor; ?>;}
	<?php } ?>
	<?php if($model->has_setting('secondary_color')){ ?>
		div.wof-wheel[data-id="<?php echo $model->id; ?>"] .wof-title em{ color:<?php  echo $model->secondary_color; ?>;}
	<?php } ?>
	<?php if($model->has_setting('button_fgcolor')){ ?>
		div.wof-wheel[data-id="<?php echo $model->id; ?>"] .wof-form-wrapper button{ color:<?php echo $model->button_fgcolor; ?>;}
		div.wof-wheel[data-id="<?php echo $model->id; ?>"] .wof-form-wrapper a.button{ color:<?php echo $model->button_fgcolor; ?>;}
	<?php } ?>
	<?php if($model->has_setting('button_bgcolor')){ ?>
		div.wof-wheel[data-id="<?php echo $model->id; ?>"] .wof-form-wrapper button{ background:<?php echo $model->button_bgcolor; ?>;}
		div.wof-wheel[data-id="<?php echo $model->id; ?>"] .wof-form-wrapper a.button{ background:<?php echo $model->button_bgcolor; ?>;}
	<?php } ?>
	<?php if($model->has_setting('widget_bgcolor')){ ?>
		div.wof-widget[data-id="<?php echo $model->id; ?>"] .wof-widget-inner { background:<?php echo $model->widget_bgcolor; ?>;}
	<?php } ?>
</style>

<?php
if(strpos($model->appeartype,'non') !== false) {
if($model->widget === 'pullout') { ?>
	<div class="wof-widget wof-widget-<?php echo $model->widget_position; ?> wof-widget-pull-out" style="display: none;" data-id="<?php echo $model->id; ?>">
		<div class="wof-widget-inner"></div>
		<?php if(!empty($model->widget_text)){?>
			<div class="wof-widget-title"><?php echo esc_html($model->widget_text); ?></div>
		<?php }?>
	</div>
<?php }if($model->widget === 'bubble'){ ?>
	<div class="wof-widget wof-widget-<?php echo $model->widget_position; ?> wof-widget-bubble" style="display: none;" data-id="<?php echo $model->id; ?>">
		<div class="wof-widget-inner"></div>
		<?php if(!empty($model->widget_text)){?>
			<div class="wof-widget-title"><?php echo esc_html($model->widget_text); ?></div>
		<?php }?>
	</div>
	<?php } if($model->widget === 'wheel'){ ?>
	<div class="wof-widget wof-widget-wheel wof-widget-<?php echo $model->widget_position; ?>" style="display: none;" data-id="<?php echo $model->id; ?>">
		<div class="wof-widget-inner">
			<div>
				<svg xmlns="http://www.w3.org/2000/svg" style="filter:drop-shadow(0 0 10px rgba(0, 0, 0, .3))" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="-1 -1 2 2">
					<g transform="rotate(-<?php echo $degree_per_slice/2; ?> 0 0) scale(.89,.89)">
						<?php
						for ($i = 0;$i < count($model->slices);$i++) {
							echo $model->create_slice_path($i,$model->amount_of_slices,$model->slices[$i]->bg);
						}
						?>
					</g>
				</svg>
			</div>
			<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" transform="scale(1.022,1.022)" viewBox="0 0 1024 1024" height="100%" width="100%">
				<circle stroke="<?php echo $model->wheel_color; ?>" r="456" fill="transparent" stroke-width="44" cx="512" cy="512"></circle>
				<circle fill="<?php echo $model->wheel_color; ?>" cx="512" cy="512" r="110"></circle>
			</svg>
		</div>
		<?php if(!empty($model->widget_text)){?>
			<div class="wof-widget-title"><?php echo esc_html($model->widget_text); ?></div>
		<?php }?>
	</div>
<?php }} ?>

<div
	style="<?php if(!$model->is_preview && !$model->standalone) { echo 'transform:translateX(-110%);';} if($model->has_setting('bgcolor')){ echo 'background-color:'.$model->bgcolor.';';} ?>"
    <?php if(!$model->is_preview && !$model->standalone) { echo ' aria-hidden="true" '; } ?>
	data-seq="<?php esc_attr_e(Wheel_service::get_sequence($model)) ?>"
	class="<?php esc_attr_e($model->classes()) ?>"
	<?php echo $model->data_attributes(); ?>
>
	<div class="wof-bg" role="img" aria-label="<?php _e('Background image','mabel-wheel-of-fortune') ?>"></div>
	<div class="wof-bottom-bg" role="img" aria-label="<?php _e('Background image','mabel-wheel-of-fortune') ?>"></div>
	<div role="button" aria-label="<?php _e('Close','mabel-wheel-of-fortune') ?>" tabindex="0" class="wof-close wof-close-icon">
		<svg width="30" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><path fill="<?php _e($model->has_setting('fgcolor') ? $model->fgcolor : 'white'); ?>" d="M77.6 21.1l-28 28.1-28.1-28.1-1.9 1.9 28 28.1-28 28.1 1.9 1.9L49.6 53l28 28.1 2-1.9-28.1-28.1L79.6 23"/></svg>
	</div>
	<div class="wof-wrapper">
		<?php if($model->has_setting('close_text')){ ?>
			<div class="wof-close-wrapper">
				<a class="wof-close wof-fgcolor" href="#">
					<?php _e($model->close_text) ?>
				</a>
			</div>
		<?php } ?>
		<div class="wof-inner-wrapper">

			<div class="wof-left">
				<div class="wof-left-inner">
					<div class="wof-pointer">
						<svg role="presentation" width="100%" height="100%" viewBox="0 0 273 147">
							<g>
								<path <?php echo ($model->has_setting('pointer_color')) ? 'fill="'.$model->pointer_color.'"' : 'class="wof-pointer-color"'; ?> d="M196.3 0h10.5l1 .25c10.06 1.9 19.63 5.06 28.1 10.93 11.28 7.55 19.66 18.43 25.12 30.78 1.9 6.4 4.06 12.23 4 19.04-.1 5.3.3 10.7-.34 15.97-2.18 14.1-9.08 27.46-19.38 37.33-10.03 10-23.32 16.4-37.33 18.4-4.95.54-10 .3-14.97.3-6.4-.02-13.06-2.82-19.2-4.68-54.98-17.5-109.95-35.08-164.96-52.5C4.7 74.7 2.14 73.33 0 69.5v-6.26c1.47-1.93 2.94-3.95 5.34-4.77C64.47 39.78 123.84 20.77 183 2c4.3-1.15 8.9-1.2 13.3-2z"/>
								<?php if($model->shadows){?>
									<path class="wof-pointer-shadow" opacity=".2" d="M261.02 41.96c6.74 9.2 10.54 20.04 11.98 31.3V88c-1.9 14.78-8.25 28.63-18.78 39.24-11 11.34-25.83 18.16-41.52 19.78h-12.65c-3.8-.6-7.57-1.4-11.22-2.63C132.4 126.43 76 108.37 19.55 90.5c-3.4-1.22-8.1-1.62-10.12-4.94-2.2-3.14-1.5-6.3-.6-9.73 55.02 17.4 110 35 164.97 52.5 6.14 1.85 12.8 4.65 19.2 4.66 4.97 0 10.02.24 14.97-.3 14-2 27.3-8.4 37.33-18.4 10.3-9.87 17.2-23.24 19.38-37.33.63-5.27.23-10.66.34-15.97.06-6.8-2.1-12.64-4-19.04v.01z"/>
								<?php } ?>
								<ellipse stroke="none" ry="25" rx="25" cy="65" cx="199.124" fill="<?php echo $model->wheel_color; ?>"/>
							</g>
						</svg>
					</div>
					<div class="wof-wheel-container" >
						<div class="wof-wheel-bg">
							<div class="wof-spinning" >
								<svg role="presentation" class="wof-svg-bg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="-1 -1 2 2">
									<g transform="rotate(-<?php echo $degree_per_slice/2; ?> 0 0) scale(.89,.89)">
									<?php
										for ($i = 0;$i < count($model->slices);$i++) {
											echo $model->create_slice_path($i,$model->amount_of_slices,$model->slices[$i]->bg);
										}
									?>
									</g>
								</svg>
							</div>

							<svg role="presentation" class="wof-svg-wheel" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" transform="scale(1.022,1.022)" viewBox="0 0 1024 1024" height="100%" width="100%">
								<?php if($model->shadows){ ?>
								<defs>
									<filter id="outer" height="130%" >
										<feGaussianBlur in="SourceAlpha" stdDeviation="0"></feGaussianBlur>
										<feOffset dx="8" dy="9"></feOffset>
										<feComponentTransfer>
											<feFuncA type="linear" slope="0.35"></feFuncA>
										</feComponentTransfer>
										<feMerge>
											<feMergeNode/>
											<feMergeNode in="SourceGraphic"/>
										</feMerge>
									</filter>
								</defs>
								<?php } ?>
								<circle stroke="<?php echo $model->wheel_color; ?>" r="456" fill="transparent" <?php if($model->shadows) echo 'filter="url(#outer)"'; ?> stroke-width="33" cx="512" cy="512"></circle>
								<circle <?php if($model->shadows) echo 'filter="url(#outer)"';?> fill="<?php echo $model->wheel_color; ?>" cx="512" cy="512" r="110"></circle>
							</svg>

							<div class="wof-spinning">
								<svg role="presentation" class="wof-svg-ornaments" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" transform="scale(1.022,1.022)" viewBox="0 0 1024 1024" height="100%" width="100%">
									<defs>
										<path id="b" transform="translate(953,498) scale(.66)" d="M 77.15 0.00 L 80.16 0.00 L 80.75 0.09 C 89.41 1.58 94.83 8.88 96.00 17.26 L 96.00 22.02 C 94.55 30.64 88.85 37.88 79.81 39.00 L 76.82 39.00 C 51.69 34.59 25.32 23.36 0.00 23.82 L 0.00 16.07 C 25.99 16.32 51.56 4.54 77.15 0.00 Z" />
										<circle id="c" r="8" transform="translate(968,510)"></circle>
									</defs>
									<?php if($model->handles){ ?>
									<g class="wof-handles" transform="rotate(-<?php echo $degree_per_slice/2; ?> 512 512)" fill="<?php echo $model->wheel_color; ?>" >
										<use xlink:href="#b" href="#b"></use>
										<?php for($i=1;$i<$model->amount_of_slices;$i++) {
											echo '<use xlink:href="#b" href="#b" transform="rotate('.$degree_per_slice*$i.' 512 512)"/>';
										}
										?>
									</g>
									<?php } ?>
									<g class="wof-circles" transform="rotate(-<?php echo $degree_per_slice/2; ?> 512 512)" fill="<?php echo $model->dots_color; ?>" >
										<use xlink:href="#c" href="#c"></use>
										<?php
											for($i=1;$i < $model->amount_of_slices;$i++) {
												echo '<use xlink:href="#c" href="#c" transform="rotate('.$degree_per_slice*$i.' 512 512)"></use>';
											}
										?>
									</g>
								</svg>
							</div>

							<div class="wof-spinning wof-slices">
								<?php foreach($model->slices as $slice) {
									$degrees = $degree_per_slice * ($slice->id - 1);
									?>
									<div class="wof-slice" data-slice="<?php echo $slice->id; ?>" style="color:<?php echo $slice->fg; ?>;transform:rotate(<?php echo $degrees ?>deg) translate(0px, -50%);"><?php echo wp_kses($slice->label, [ 'a' => [ 'href'=>[],'target'=>[],'style'=>[],'class'=>[],'id'=>[] ],'i' => [],'em' => [ 'class'=>[],'id'=>[],'style'=>[] ],'b' => [ 'class'=>[],'id'=>[],'style'=>[] ],'img' => [ 'id'=>[],'class'=>[],'src'=>[],'style'=>[] ],'br' => [],'span' => ['class'=>[],'style'=>[],'id'=>[] ] ]); ?></div>
								<?php } ?>
							</div>
							<?php if($model->is_preview || !empty($model->logo)){?>
							<div class="wof-logo" role="img" aria-label="<?php _e('logo','mabel-wheel-of-fortune') ?>" style="background-image: url(<?php echo $model->logo; ?>);"></div>
							<?php } ?>
						</div>

					</div>
				</div>
			</div>

			<div class="wof-right">
				<div class="wof-right-inner">
					<div class="wof-title wof-fgcolor">
						<?php _e($model->title); ?>
					</div>

                    <div class="wof-explainer wof-fgcolor">
                        <?php if($model->has_setting('explainer')) _e($model->explainer); ?>
                    </div>

					<div class="wof-form-wrapper">
						<?php if($model->is_preview){ ?>
							<div class="wof-error">
								Example of an error message
							</div>
						<?php }else{ ?>
							<div class="wof-error wof-fgcolor" style="<?php if($model->has_setting('error_color')) _e('color:'.$model->error_color.';border-color:'.$model->error_color); ?>;display: none;"></div>
						<?php } ?>
						<div class="wof-form-fields">
						</div>
						<?php
							$has_fb_optin = false;

							if($model->enable_fb && $model->list_provider != 'none'  && !$has_fb_optin && Settings_Manager::has_setting('chatfuel_page_id') && Settings_Manager::has_setting('chatfuel_app_id')) {
								$has_fb_optin = true;
								\MABEL_WOF\Code\Services\CF_Service::form_html(
									$model->id,
									$model->fb_obligated,
									$model->retries
								);
							}

						?>
						<button class="wof-btn-submit wof-color-2" type="submit">
							<span><?php _e($model->setting_or_default('button_text',__('Try your luck','mabel-wheel-of-fortune'))) ?></span>
							<div class="wof-loader" style="display: none;">
								<div class="b1"></div>
								<div class="b2"></div>
								<div></div>
							</div>
						</button>
						<div class="wof-response"></div>
					</div>
                    <div class="wof-disclaimer wof-fgcolor">
                        <?php if($model->has_setting('disclaimer')) _e($model->disclaimer); ?>
                    </div>
				</div>
			</div>

		</div>
	</div>
</div>

<?php  if( $model->esc_key ) { ?>
<script>
    document.onkeydown = function(e) {
        if (e.key === "Escape") {
            var id = <?php echo $model->id ?>;
            if(!WOF.Frontend.wheels || !WOF.Frontend.wheels.length) return;
            WOF.Frontend.wheels.forEach(function(w) {if(w.id == id && !w.standalone && w.status === 'visible' ) {w.hide();return;}});
        }
    }
</script>
<?php } ?>
