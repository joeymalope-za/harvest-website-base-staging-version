<?php
/** @var \MABEL_WOF\Core\Models\Text_Option $option */
use MABEL_WOF\Core\Common\Managers\Config_Manager;
if(!defined('ABSPATH')){
	die;
}

?>

<?php if(!$option->is_textarea) { ?>

	<input
		class="widefat mabel-form-element"
		type="text"
		name="<?php echo $option->name === null ? $option->id : $option->name; ?>"
		value="<?php echo htmlspecialchars($option->value);?>"
		placeholder="<?php echo $option->placeholder; ?>"
	    <?php echo !empty($option->dependency) ? 'data-wof-dependency="' . htmlspecialchars(json_encode($option->dependency,ENT_QUOTES)) . '"':''; ?>
		<?php echo $option->get_extra_data_attributes(); ?>
	/>
<?php }else { ?>
	<textarea
		style="height: 150px;"
		class="widefat mabel-form-element"
		name="<?php echo $option->name === null ? $option->id : $option->name; ?>"
		placeholder="<?php echo $option->placeholder; ?>"
		<?php echo !empty($option->dependency) ? 'data-wof-dependency="' . htmlspecialchars(json_encode($option->dependency,ENT_QUOTES)) . '"':''; ?>
		<?php echo $option->get_extra_data_attributes(); ?>
	><?php echo htmlspecialchars($option->value);?></textarea>
<?php
}
	$option->display_help();
	if(isset($option->extra_info))
		echo '<div class="p-t-1 extra-info">' . $option->extra_info .'</div>';
?>
