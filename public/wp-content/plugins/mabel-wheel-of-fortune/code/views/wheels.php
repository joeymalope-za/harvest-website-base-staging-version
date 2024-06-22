<?php
/** @var \MABEL_WOF\Code\Models\Wheels_VM $model */
?>

<div class="wof-overlay" style="display: none;"></div>

<div class="wof-wheels">
	<?php
		foreach($model->wheels as $wheel) {
			echo \MABEL_WOF\Core\Common\Html::view('wheel', $wheel);
		}
	?>
</div>

<div class="wof-mobile-check"></div>
<div class="wof-tablet-check"></div>
<div class="wof-desktop-check"></div>