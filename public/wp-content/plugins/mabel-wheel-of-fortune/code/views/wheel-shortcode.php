<?php
/** @var \MABEL_WOF\Code\Models\Wheel_Shortcode_VM $model */

if( empty($model->wheel)) return;
$script = 'var  ' . \MABEL_WOF\Core\Common\Managers\Script_Style_Manager::$frontend_js_var . '=' . wp_json_encode( \MABEL_WOF\Core\Common\Managers\Script_Style_Manager::$script_variables ) . ';';
?>

<script>
    <?php echo $script ?>
</script>
<div class="wof-wheel-standalone">
	<?php
		echo \MABEL_WOF\Core\Common\Html::view('wheel',$model->wheel);
	?>
</div>