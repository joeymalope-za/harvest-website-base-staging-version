<?php

namespace MABEL_WOF\Code\Services {

	use MABEL_WOF\Core\Common\Linq\Enumerable;
	use MABEL_WOF\Core\Common\Managers\Config_Manager;

	class Theming_Service {

		public static function get_backgrounds() {
			return [
				[
					'id' => 'none',
					'title' => 'No pattern'
				],[
					'id' => 'hearts',
					'title' => 'Hearts',
					'opacity' => .085,
					'size' => '11%'
				],
				[
					'id' => 'swirl-light',
					'title' => 'Swirl light',
					'opacity' => .22,
					'size' => '50%'
				],[
					'id' => 'swirl-dark',
					'title' => 'Swirl dark',
					'opacity' => .22,
					'size' => '50%'
				],[
					'id' => 'hypnotize',
					'title' => 'Hypnotize',
					'opacity' => .25,
					'size' => '35%'
				],[
					'id' => 'vintage',
					'title' => 'Vintage',
					'opacity' => .35,
					'size' => '40%'
				],[
					'id' => 'halloween',
					'title' => 'Halloween',
					'opacity' => .3,
					'size' => '60%'
				],[
					'id' => 'christmas',
					'title' => 'Snow',
					'opacity' => .5,
					'size' => '50%'
				],[
					'id' => 'memphis-light',
					'title' => 'Memphis light',
					'opacity' => .1,
					'size' => '30%'
				],[
					'id' => 'memphis-dark',
					'title' => 'Memphis dark',
					'opacity' => .5,
					'size' => '30%'
				],[
					'id' => 'waves-light',
					'title' => 'Waves light',
					'opacity' => .06,
					'size' => '23%'
				],[
					'id' => 'waves-dark',
					'title' => 'Waves dark',
					'opacity' => .5,
					'size' => '23%'
				],[
					'id' => 'waves-alt-light',
					'title' => 'Alternate waves light',
					'opacity' => .06,
					'size' => '20%'
				],[
					'id' => 'waves-alt-dark',
					'title' => 'Alternate waves dark',
					'opacity' => .06,
					'size' => '20%'
				],[
					'id' => 'ethnic-light',
					'title' => 'Ethnic light',
					'opacity' => .1,
					'size' => '45%'
				],[
					'id' => 'ethnic-dark',
					'title' => 'Ethnic dark',
					'opacity' => .1,
					'size' => '45%'
				],
			];
		}

		public static function get_themes() {
			return [
				'monochromatic_themes' => [
					[
						'id' => 'black-and-white',
						'title' => 'Black & White',
						'preview' => Config_Manager::$url . 'admin/img/wheel-black-and-white.png',
						'bgcolor' => '#353535',
						'fgcolor' => '#fff',
						'pointerColor' => '#000',
						'buttonBg' => '#000',
						'buttonFg' => '#fff',
						'emColor' => '#d5d5d5',
						'slices' => [
							'bg' => [
								'#353535','#E5E5E5','#666666','#B5B4B4',
								'#353535','#E5E5E5','#666666','#B5B4B4',
								'#353535','#E5E5E5','#666666','#B5B4B4',
								'#353535','#E5E5E5','#666666','#B5B4B4',
								'#353535','#E5E5E5','#666666','#B5B4B4',
								'#353535','#E5E5E5','#666666','#B5B4B4',
							],
							'fg' => ['#fff','#353535','#fff','#fff','#fff','#353535','#fff','#fff','#fff','#353535','#fff','#fff','#fff','#353535','#fff','#fff','#fff','#353535','#fff','#fff','#fff','#353535','#fff','#fff']
						],
						'wheel' => '#fff','dots' => '#5F5F5F',
						'error' => '#ffafaf',
					],
					[
						'id' => 'yellow',
						'title' => 'Yellow',
						'preview' => Config_Manager::$url . 'admin/img/wheel-yellow.png',
						'bgcolor' => '#4f4f4f',
						'fgcolor' => '#fff',
						'pointerColor'  => '#eded7d',
						'buttonBg' => '#eded7d',
						'buttonFg' => '#6A5E1A',
						'emColor' => '#eded7d',
						'slices' => [
							'bg' => [
								'#6A5E1A','#EFE8BC','#D2BE46','#EEE6BA',
								'#6A5E1A','#EFE8BC','#D2BE46','#EEE6BA',
								'#6A5E1A','#EFE8BC','#D2BE46','#EEE6BA',
								'#6A5E1A','#EFE8BC','#D2BE46','#EEE6BA',
								'#6A5E1A','#EFE8BC','#D2BE46','#EEE6BA',
								'#6A5E1A','#EFE8BC','#D2BE46','#EEE6BA',
							],
							'fg' => ['#fff','#6A5E1A','#fff','#6A5E1A','#fff','#6A5E1A','#fff','#6A5E1A','#fff','#6A5E1A','#fff','#6A5E1A','#fff','#6A5E1A','#fff','#6A5E1A','#fff','#6A5E1A','#fff','#6A5E1A','#fff','#6A5E1A','#fff','#6A5E1A']
						],
						'wheel' => '#fff','dots' => '#FAEA8D',
						'error' => '#ffafaf',
					],
					[
						'id' => 'orange',
						'title' => 'Orange',
						'preview' => Config_Manager::$url . 'admin/img/wheel-orange.png',
						'bgcolor' => '#4f4f4f',
						'fgcolor' => '#fff',
						'pointerColor' => '#ffbb87',
						'buttonBg' => '#ffbb87',
						'buttonFg' => '#773b00',
						'emColor' => '#ffbb87',
						'slices' => [
							'bg' => [
								'#723A13','#F3CFB7','#DF7D3A','#F3CFB6',
								'#723A13','#F3CFB7','#DF7D3A','#F3CFB6',
								'#723A13','#F3CFB7','#DF7D3A','#F3CFB6',
								'#723A13','#F3CFB7','#DF7D3A','#F3CFB6',
								'#723A13','#F3CFB7','#DF7D3A','#F3CFB6',
								'#723A13','#F3CFB7','#DF7D3A','#F3CFB6',
							],
							'fg' => ['#fff','#773b00','#fff','#773b00','#fff','#773b00','#fff','#773b00','#fff','#773b00','#fff','#773b00','#fff','#773b00','#fff','#773b00','#fff','#773b00','#fff','#773b00','#fff','#773b00','#fff','#773b00']
						],
						'wheel' => '#fff','dots' => '#FFC9A4',
						'error' => '#ffafaf',
					],
					[
						'id' => 'red',
						'title' => 'Red',
						'preview' => Config_Manager::$url . 'admin/img/wheel-red.png',
						'bgcolor' => '#4f4f4f',
						'fgcolor' => '#fff',
						'pointerColor' => '#ff9696',
						'buttonBg' => '#ff9696',
						'buttonFg' => '#700000',
						'emColor' => '#ff9696',
						'slices' => [
							'bg' => [
								'#6D1818','#F0BABA','#D74242','#F0B9B9',
								'#6D1818','#F0BABA','#D74242','#F0B9B9',
								'#6D1818','#F0BABA','#D74242','#F0B9B9',
								'#6D1818','#F0BABA','#D74242','#F0B9B9',
								'#6D1818','#F0BABA','#D74242','#F0B9B9',
								'#6D1818','#F0BABA','#D74242','#F0B9B9',
							],
							'fg' => ['#fff','#700000','#fff','#700000','#fff','#700000','#fff','#700000','#fff','#700000','#fff','#700000','#fff','#700000','#fff','#700000','#fff','#700000','#fff','#700000','#fff','#700000','#fff','#700000']
						],
						'wheel' => '#fff','dots' => '#F4CBCB',
						'error' => '#ffafaf',
					],
					[
						'id' => 'green',
						'title' => 'Green',
						'preview' => Config_Manager::$url . 'admin/img/wheel-green.png',
						'bgcolor' => '#4f4f4f',
						'fgcolor' => '#fff',
						'pointerColor' => '#8dea8a',
						'buttonBg' => '#8dea8a',
						'buttonFg' => '#084c00',
						'emColor' => '#8dea8a',
						'slices' => [
							'bg' => [
								'#216425','#C0EAC2','#52C758','#BEEAC0',
								'#216425','#C0EAC2','#52C758','#BEEAC0',
								'#216425','#C0EAC2','#52C758','#BEEAC0',
								'#216425','#C0EAC2','#52C758','#BEEAC0',
								'#216425','#C0EAC2','#52C758','#BEEAC0',
								'#216425','#C0EAC2','#52C758','#BEEAC0',
							],
							'fg' => ['#fff','#084c00','#fff','#084c00','#fff','#084c00','#fff','#084c00','#fff','#084c00','#fff','#084c00','#fff','#084c00','#fff','#084c00','#fff','#084c00','#fff','#084c00','#fff','#084c00','#fff','#084c00']
						],
						'wheel' => '#fff','dots' => '#C0E9C2',
						'error' => '#ffafaf',
					],
					[
						'id' => 'purple',
						'title' => 'Purple',
						'preview' => Config_Manager::$url . 'admin/img/wheel-purple.png',
						'bgcolor' => '#4f4f4f',
						'fgcolor' => '#fff',
						'pointerColor' => '#d3afff',
						'buttonBg' => '#d3afff',
						'buttonFg' => '#600030',
						'emColor' => '#d3afff',
						'slices' => [
							'bg' => [
								'#53275E','#E0C3E7','#A95CBC','#DFC2E6',
								'#53275E','#E0C3E7','#A95CBC','#DFC2E6',
								'#53275E','#E0C3E7','#A95CBC','#DFC2E6',
								'#53275E','#E0C3E7','#A95CBC','#DFC2E6',
								'#53275E','#E0C3E7','#A95CBC','#DFC2E6',
								'#53275E','#E0C3E7','#A95CBC','#DFC2E6',
							],
							'fg' => ['#fff','#600030','#fff','#600030','#fff','#600030','#fff','#600030','#fff','#600030','#fff','#600030','#fff','#600030','#fff','#600030','#fff','#600030','#fff','#600030','#fff','#600030','#fff','#600030']
						],
						'wheel' => '#fff','dots' => '#EEBFFA',
						'error' => '#ffafaf',
					],
					[
						'id' => 'blue',
						'title' => 'Blue',
						'preview' => Config_Manager::$url . 'admin/img/wheel-blue.png',
						'bgcolor' => '#226ea0',
						'fgcolor' => '#fff',
						'pointerColor' => '#c2e078',
						'buttonBg' => '#c2e078',
						'buttonFg' => '#225378',
						'emColor' => '#c2e078',
						'slices' => [
							'bg' => [
								'#0081D7','#225378','#2980B9','#ACDAF2',
								'#0081D7','#225378','#2980B9','#ACDAF2',
								'#0081D7','#225378','#2980B9','#ACDAF2',
								'#0081D7','#225378','#2980B9','#ACDAF2',
								'#0081D7','#225378','#2980B9','#ACDAF2',
								'#0081D7','#225378','#2980B9','#ACDAF2',
							],
							'fg' => ['#fff','#fff','#fff','#225378','#fff','#fff','#fff','#225378','#fff','#fff','#fff','#225378','#fff','#fff','#fff','#225378','#fff','#fff','#fff','#225378','#fff','#fff','#fff','#225378']
						],
						'wheel' => '#fff','dots' => '#7AD7E9',
						'error' => '#ffafaf',
					],
				],
				'colorized_themes' =>  [
					[
						'id' => 'green-desert',
						'title' => 'Green Desert',
						'preview' => Config_Manager::$url . 'admin/img/wheel-green-desert.png',
						'bgcolor' => '#77311b',
						'fgcolor' => '#fff',
						'pointerColor' => '#ffc79a',
						'buttonBg' => '#ffc79a',
						'buttonFg' => '#b64926',
						'emColor' => '#ffc79a',
						'slices' => [
							'bg' => [
								'#B64926','#468966','#FFF0A5','#FFB03B',
								'#B64926','#468966','#FFF0A5','#FFB03B',
								'#B64926','#468966','#FFF0A5','#FFB03B',
								'#B64926','#468966','#FFF0A5','#FFB03B',
								'#B64926','#468966','#FFF0A5','#FFB03B',
								'#B64926','#468966','#FFF0A5','#FFB03B',
							],
							'fg' => ['#fff','#fff','#b64926','#fff','#fff','#fff','#b64926','#fff','#fff','#fff','#b64926','#fff','#fff','#fff','#b64926','#fff','#fff','#fff','#b64926','#fff','#fff','#fff','#b64926','#fff']
						],
						'wheel' => '#fff','dots' => '#C8775C',
						'error' => '#ffafaf',
					],
					[
						'id' => 'alt-blue',
						'title' => 'Alternative Blue',
						'preview' => Config_Manager::$url . 'admin/img/wheel-alt-blue.png',
						'bgcolor' => '#012d41',
						'fgcolor' => '#fff',
						'pointerColor' => '#9ad0e6',
						'buttonBg' => '#9ad0e6',
						'buttonFg' => '#012d41',
						'emColor' => '#9ad0e6',
						'slices' => [
							'bg' => [
								'#FF404E','#012D41','#1BA5B8','#DAECF3',
								'#FF404E','#012D41','#1BA5B8','#DAECF3',
								'#FF404E','#012D41','#1BA5B8','#DAECF3',
								'#FF404E','#012D41','#1BA5B8','#DAECF3',
								'#FF404E','#012D41','#1BA5B8','#DAECF3',
								'#FF404E','#012D41','#1BA5B8','#DAECF3',
							],
							'fg' => ['#fff','#fff','#fff','#012d41','#fff','#fff','#fff','#012d41','#fff','#fff','#fff','#012d41','#fff','#fff','#fff','#012d41','#fff','#fff','#fff','#012d41','#fff','#fff','#fff','#012d41']
						],
						'wheel' => '#fff','dots' => '#F199A0',
						'error' => '#ffafaf',
					],
					[
						'id' => 'deep-purple',
						'title' => 'Deep Purple',
						'preview' => Config_Manager::$url . 'admin/img/wheel-deep-purple.png',
						'bgcolor' => '#680052',
						'fgcolor' => '#fff',
						'pointerColor' => '#ecabe4',
						'buttonBg' => '#ecabe4',
						'buttonFg' => '#680052',
						'emColor' => '#ecabe4',
						'slices' => [
							'bg' => [
								'#680052','#360541','#863297','#DB9BE4',
								'#680052','#360541','#863297','#DB9BE4',
								'#680052','#360541','#863297','#DB9BE4',
								'#680052','#360541','#863297','#DB9BE4',
								'#680052','#360541','#863297','#DB9BE4',
								'#680052','#360541','#863297','#DB9BE4',
							],
							'fg' => ['#fff','#fff','#fff','#680052','#fff','#fff','#fff','#680052','#fff','#fff','#fff','#680052','#fff','#fff','#fff','#680052','#fff','#fff','#fff','#680052','#fff','#fff','#fff','#680052']
						],
						'wheel' => '#fff','dots' => '#CD9CD8',
						'error' => '#ffafaf',
					],
					[
						'id' => 'vintage',
						'title' => 'Vintage',
						'preview' => Config_Manager::$url . 'admin/img/wheel-vintage.png',
						'bgcolor' => '#575a54',
						'fgcolor' => '#fff',
						'pointerColor' => '#ffb09d',
						'buttonBg' => '#ffb09d',
						'buttonFg' => '#2F343B',
						'emColor' => '#ffb09d',
						'slices' => [
							'bg' => [
								'#C77966','#2F343B','#7E827A','#E3CDA4',
								'#C77966','#2F343B','#7E827A','#E3CDA4',
								'#C77966','#2F343B','#7E827A','#E3CDA4',
								'#C77966','#2F343B','#7E827A','#E3CDA4',
								'#C77966','#2F343B','#7E827A','#E3CDA4',
								'#C77966','#2F343B','#7E827A','#E3CDA4',
							],
							'fg' => ['#fff','#fff','#fff','#2F343B','#fff','#fff','#fff','#2F343B','#fff','#fff','#fff','#2F343B','#fff','#fff','#fff','#2F343B','#fff','#fff','#fff','#2F343B','#fff','#fff','#fff','#2F343B']
						],
						'wheel' => '#fff','dots' => '#9EA0A3',
						'error' => '#ffafaf',
					],
					[
						'id' => 'alt-green',
						'title' => 'Alternate Green',
						'preview' => Config_Manager::$url . 'admin/img/wheel-alt-green.png',
						'bgcolor' => '#052635',
						'fgcolor' => '#fff',
						'pointerColor' => '#47e1b7',
						'buttonBg' => '#47e1b7',
						'buttonFg' => '#052635',
						'emColor' => '#47e1b7',
						'slices' => [
							'bg' => [
								'#133544','#37B290','#007E91','#83B8CF',
								'#133544','#37B290','#007E91','#83B8CF',
								'#133544','#37B290','#007E91','#83B8CF',
								'#133544','#37B290','#007E91','#83B8CF',
								'#133544','#37B290','#007E91','#83B8CF',
								'#133544','#37B290','#007E91','#83B8CF',
							],
							'fg' => ['#fff','#fff','#fff','#052635','#fff','#fff','#fff','#052635','#fff','#fff','#fff','#052635','#fff','#fff','#fff','#052635','#fff','#fff','#fff','#052635','#fff','#fff','#fff','#052635']
						],
						'wheel' => '#fff','dots' => '#89E7CD',
						'error' => '#ff8577',
					],
				],
				'seasonal_themes' => [
					[
						'id' => 'halloween',
						'title' => 'Halloween',
						'preview' => Config_Manager::$url . 'admin/img/wheel-halloween.png',
						'bgcolor' => '#000',
						'fgcolor' => '#fff',
						'pointerColor' => '#dd734a',
						'buttonBg' => '#c15125',
						'buttonFg' => '#fff',
						'emColor' => '#c15125',
						'slices' => [
							'bg' => [
								'#C15125','#232323','#353535','#848383',
								'#C15125','#232323','#353535','#848383',
								'#C15125','#232323','#353535','#848383',
								'#C15125','#232323','#353535','#848383',
								'#C15125','#232323','#353535','#848383',
								'#C15125','#232323','#353535','#848383',
							],
							'fg' => ['#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff','#fff']
						],
						'wheel' => '#fff','dots' => '#EC8962',
						'error' => '#ffafaf',
					],
					[
						'id' => 'christmas',
						'title' => 'Winter Wonderland',
						'preview' => Config_Manager::$url . 'admin/img/wheel-christmas.png',
						'bgcolor' => '#6cd1fd',
						'fgcolor' => '#02285a',
						'pointerColor' => '#02285a',
						'buttonBg' => '#02285a',
						'buttonFg' => '#fff',
						'emColor' => '#02285a',
						'slices' => [
							'bg' => [
								'#02285A','#A3D9FD','#4A76B7','#9FCCFF',
								'#02285A','#A3D9FD','#4A76B7','#9FCCFF',
								'#02285A','#A3D9FD','#4A76B7','#9FCCFF',
								'#02285A','#A3D9FD','#4A76B7','#9FCCFF',
								'#02285A','#A3D9FD','#4A76B7','#9FCCFF',
								'#02285A','#A3D9FD','#4A76B7','#9FCCFF',
							],
							'fg' => ['#fff','#02285a','#fff','#02285a','#fff','#02285a','#fff','#02285a','#fff','#02285a','#fff','#02285a','#fff','#02285a','#fff','#02285a','#fff','#02285a','#fff','#02285a','#fff','#02285a','#fff','#02285a']
						],
						'wheel' => '#fff','dots' => '#93ADC2',
						'error' => '#dd0000',
					],
				]
			];
		}

		public static function get_theme($theme_id){
			$monochromatic = Enumerable::from(self::get_themes()['monochromatic_themes'])->firstOrDefault(function($x) use ($theme_id) {
				return $x['id' ] === $theme_id;
			});
			if($monochromatic != null)
				return $monochromatic;

			$colorized = Enumerable::from(self::get_themes()['colorized_themes'])->firstOrDefault(function($x) use ($theme_id) {
				return $x['id' ] === $theme_id;
			});
			if($colorized != null)
				return $colorized;

			$seasonal = Enumerable::from(self::get_themes()['seasonal_themes'])->firstOrDefault(function($x) use ($theme_id) {
				return $x['id' ] === $theme_id;
			});
			if($seasonal != null)
				return $seasonal;

			return null;
		}

	}

}