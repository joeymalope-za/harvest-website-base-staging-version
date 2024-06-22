<?php

namespace MABEL_WOF\Code\Models {

	class CouponBar_VM
	{

		public $coupon_bars;

		public function __construct() {
			$this->coupon_bars = [];
		}

	}

	class CouponBar{
		public $fgcolor;
		public $bgcolor;
		public $text;
		public $wheel_id;
		public $duration;
		public $timeframe;

		public function __construct() {
			$this->text = '';
			$this->bgcolor = '#000';
			$this->fgcolor = '#fff';
		}
	}
}