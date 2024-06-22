<?php
/** @var \MABEL_WOF\Code\Models\Wheel_Model $model */
?>

<?php if($model->plays > 0 ){ ?>
	<div class="wof-play-again">
		<button class="wof-btn-again wof-color-2">
			<span><?php _e($model->setting_or_default('button_again','Try again')) ?></span>
			<div class="wof-loader" style="display: none;">
				<div class="b1"></div>
				<div class="b2"></div>
				<div></div>
			</div>
		</button>
		<?php if($model->has_setting('games_left_text')) { ?>
			<div class="wof-plays-left">
				<?php _e(str_replace('{x}',$model->plays_left, $model->games_left_text)) ?>
			</div>
		<?php } ?>
	</div>
<?php }else{ ?>
	<button class="wof-btn-done wof-close wof-color-2">
		<?php _e($model->setting_or_default('button_done','Close this')) ?>
	</button>
<?php } ?>