<?php
/** @var \MABEL_WOF\Code\Models\CouponBar_VM $model */
?>
<div class="wof-coupon-bars">
	<?php foreach($model->coupon_bars as $bar){ ?>
		<div class="wof-coupon-bar"
		     data-duration="<?php echo $bar->duration; ?>"
		     data-timeframe="<?php echo $bar->timeframe; ?>"
		     style="color:<?php echo $bar->fgcolor; ?>;background:<?php echo $bar->bgcolor ?>;display:none;"
		     data-id="<?php echo $bar->wheel_id; ?>">
			<div class="wof-coupon-text">
				<?php echo $bar->text; ?>
			</div>
			<div class="wof-bar-close">
				<svg width="30" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
					<path fill="<?php echo $bar->fgcolor; ?>" d="M77.6 21.1l-28 28.1-28.1-28.1-1.9 1.9 28 28.1-28 28.1 1.9 1.9L49.6 53l28 28.1 2-1.9-28.1-28.1L79.6 23"/>
				</svg>
			</div>
		</div>
	<?php } ?>
</div>

