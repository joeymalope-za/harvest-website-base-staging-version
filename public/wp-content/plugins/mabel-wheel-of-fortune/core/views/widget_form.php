<?php

/** @var \MABEL_WOF\Core\Common\Managers\Widget_Options_Manager $option_manager */

?>

<div class="widget-options">
	<?php
		foreach($option_manager->options as $option)
		{
			/** @var \MABEL_WOF\Core\Models\Option $option */
			echo '<p>';
				echo '<label>' .$option->title. '</label>';
				echo '<div>';
					$option_manager->display_field( ['option' => $option] );
				echo '</div>';
			echo '</p>';
		}
	?>
</div>
