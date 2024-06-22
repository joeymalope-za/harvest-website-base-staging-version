<?php
/** @var \MABEL_WOF\Core\Models\Start_VM $model */
$nonce = $model->has_license ? 'deactivate-pro' : 'activate-pro';
?>

<div class="mabel-tab tab-options-license" style="<?php if($model->has_license) echo 'display:none;'; ?>">
	<form class="form-table" method="POST" action="">
		<input type="hidden" name="_mabelnonce" value="<?php echo wp_create_nonce($nonce); ?>">
		<?php
		if(!$model->has_license) {
			echo '<p>' . __( 'Please enter your license key here in order to use the plugin.', $model->slug ) . '</p>';
		}
		echo '<label><b>'.__('Your license key',$model->slug).'</b></label>';
		?>

		<div class="p-t-2">
			<input
				class="widefat"
				placeholder="Your license key"
				value="<?php echo $model->has_license ? '*********************' : ''; ?>"
				type="<?php echo $model->has_license ? 'password' : 'text'; ?>"
				<?php echo $model->has_license ? 'readonly="readonly"' : 'name="mabel-wheel-of-fortune-license"'; ?>
			/>
		</div>

		<?php if($model->has_license){ ?>
			<?php if($model->license_overdue) { ?>
				<p class="p-t-2">
					<i class="icon-error"></i> <?php echo sprintf(__('Your license <b class="msg msg-bad">is invalid</b> and %s days overdue.', $model->slug),$model->time_left_in_days); ?>
					<br/>
					<?php _e("When your license runs out, the software will still function, but you won't be able to install updates, receive bugfixes or get support.",$model->slug); ?>
				</p>
			<?php } else { ?>
				<p class="p-t-2">
					<?php echo sprintf(__('Your license <b class="msg msg-good">is valid</b> for %s more days.', $model->slug), $model->time_left_in_days); ?>
					<br/>
					<?php _e("When your license runs out, the software will still function, but you won't be able to install updates, receive bugfixes or get support.",$model->slug); ?>
				</p>
			<?php } ?>
		<?php } ?>

		<div class="p-t-3">
			<input type="submit" name="submit" id="submit" class="mabel-btn" value="<?php echo __( $model->has_license ? 'Deactivate license' : 'Activate license', $model->slug); ?>" />
		</div>

	</form>
</div>