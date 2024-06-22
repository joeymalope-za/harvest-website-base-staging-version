<div id="wof-addon-dialog" style="display: none;">
	<div class="dialog-content m-t-5">

	</div>
	<div class="mabel-modal-button-row">
		<a href="javascript:tb_remove();" class="mabel-btn"><?php _e('Close','mabel-wheel-of-fortune');?></a>
	</div>
</div>
<div class="wof-integration-settings" style="display: none;"></div>
<p class="wof-info-bubble">
	<?php _e('Optin Wheel works with any of the tools below. That means every time someone spins the wheel, their data will be captured by the tool of your preference. Select your preferred tool and fill out the settings to activate the connection. Worried about data collection? Read our guide on <a href="https://www.studiowombat.com/knowledge-base/data-collection-gdpr-privacy/?utm_source=wof&utm_medium=plugin&utm_campaign=info" target="_blank">data collection, GDPR, and privacy</a>.','mabel-wheel-of-fortune');?>
</p>
<div class="all-integrations-wrapper" data-integrations="<?php echo htmlspecialchars(json_encode($data['integrations_without_card']), ENT_QUOTES, 'UTF-8');?>" style="padding-top:30px;">
	<?php foreach($data['integrations'] as $integration){
			if(empty($integration->card))
				continue;
			$card = (object)$integration->card;
		?>
		<div data-id="<?php echo $integration->id; ?>" class="<?php  echo (empty($card->classes)? '':$card->classes.' '); ?>image-tile integration-tile">
			<div class="tile-header" style="background-image: url('<?php _e($card->img); ?>');background-color:<?php _e($card->background); ?>;">
				<span class="tag-id" <?php echo $integration->installed ? '' : 'style="display:none;"'; ?>>
					<?php _e('Active','mabel-wheel-of-fortune');?>
				</span>
			</div>
		</div>
			<div data-for="<?php echo $integration->id ?>" class="wof-integrations-settings-wrapper" style="display: none;">
			<table class="form-table">
				<?php
				if(isset($card->settings)) {
					foreach ( $card->settings as $o ) {
						echo '<tr>';
						if ( ! empty( $o->title ) ) {
							echo '<th scope="row">' . $o->title . '</th>';
						}
						echo '<td '.( empty($o->title)? 'colspan="2"' : '') .'>';
						\MABEL_WOF\Core\Common\Html::option( $o );
						echo '</td></tr>';
					}
				}
				?>
			</table>
		</div>
	<?php } ?>
</div>