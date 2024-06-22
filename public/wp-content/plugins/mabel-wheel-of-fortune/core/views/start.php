<?php
if(!defined('ABSPATH')){die;}
use \MABEL_WOF\Core\Common\Managers\Config_Manager;
use \MABEL_WOF\Core\Common\Html;
/** @var \MABEL_WOF\Core\Models\Start_VM $model */

add_thickbox();
?>
<div class="mabel-error-popup-wrapper" style="display:none;">
	<div class="mabel-error-popup">
		<div class="mabel-error-popup-message"></div>
		<div class="mabel-modal-button-row">
			<button class="mabel-btn" onclick="Core.Admin.Main.hideError()" >Close</button>
		</div>
	</div>
</div>
<div class="mabel-loading" style="display: none;"></div>
<div class="padding-t">
	<div class="mabel-container">
		<div class="mabel-row">
			<div class="mabel-twelve mabel-columns">
				<h2 class="mabel-nav-tab-wrapper">
					<?php
					if($model->has_license)
					{
						foreach($model->sections as $section)
						{
							echo
								'<a data-tab="options-'.$section->id.'" href="#" class="mabel-nav-tab'.($section->active === true? '  mabel-nav-tab-active':'').'">
										<i class="dashicons dashicons-'.$section->icon.'"></i>
										<span>'.__($section->title, $model->slug).'</span>
									</a>';
						}
						do_action($model->slug . '-add-tabs');
					}
					echo
						'<a data-tab="options-license" href="#" class="mabel-nav-tab '. ($model->has_license?'':'mabel-nav-tab-active').'">
								<i class="dashicons dashicons-admin-network"></i>
								<span>'.__('License', $model->slug).'</span>
							</a>';

					?>
				</h2>
				<?php if($model->has_license) { ?>
					<form action="options.php" id="<?php echo $model->slug; ?>-form" method="POST">
						<?php
						settings_fields( $model->slug );
						foreach($model->sections as $section)
						{
							echo '<div class="mabel-tab tab-options-'.$section->id.'" '.($section->active === true? '':'style="display:none;"').'>';

							do_action($model->slug . '-add-section-content-before-' . $section->id);

							if($section->has_options())
							{
								echo '<table class="form-table">';
								foreach($section->get_options() as $o)
								{
									echo '<tr>';
									if(!empty($o->title))
										echo '<th scope="row">'.$o->title.'</th>';
									echo '<td>';
									Html::option($o);
									echo '</td></tr>';
								}
								echo '</table>';
							}

							do_action($model->slug . '-add-section-content-after-' . $section->id);

							echo '<div class="p-t-2">
											<span class="all-settings-saved"><i class="icon-check icon-15"></i> '.__('All settings saved', $model->slug). '</span>
											<span style="display:none;" class="saving-settings">Saving settings...</span>
								     </div>';
							echo '</div>';

						}

						do_action($model->slug . '-add-panels');

						foreach($model->hidden_settings as $option)
						{
							include Config_Manager::$dir . 'core/views/fields/hidden.php';
						}
						?>
					</form>
				<?php } ?>
				<?php Html::partial('core/views/updates-form',$model); ?>
			</div>

			<div class="mabel-two mabel-columns">
				<div style="display: none;" class="mabel-sidebar sidebar-main" data-sidebar-for="main">
					<?php
					do_action($model->slug . '-render-sidebar');
					?>
				</div>
				<?php
				foreach($model->sections as $section)
				{
					echo '<div style="display: none;" class="mabel-sidebar sidebar-' .$section->id. '" data-sidebar-for="options-' .$section->id. '">';
					do_action($model->slug . '-render-sidebar-'.$section->id);
					echo '</div>';
				}
				?>
			</div>
		</div>
	</div>
</div>

<?php
do_action($model->slug . '-add-content');
?>
<div
	data-context
    data-woo-active="<?php echo $model->woo_active ?>"
	data-base-url="<?php echo Config_Manager::$url; ?>"
	data-activated="<?php echo $model->has_license; ?>"
	data-settings-key="<?php echo $model->settings_key ?>"
	data-slug="<?php echo $model->slug ?>"
	data-admin-ajax-url="<?php echo admin_url('admin-ajax.php'); ?>">
</div>