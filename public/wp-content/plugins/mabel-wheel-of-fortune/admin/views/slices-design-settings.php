<table class="form-table">
	<?php
	foreach($data['options'] as $o)
	{
		echo '<tr>';
		if(!empty($o->title))
			echo '<th scope="row">'.$o->title.'</th>';
		echo '<td>';
		\MABEL_WOF\Core\Common\Html::option($o);
		echo '</td></tr>';
	}
	?>
</table>
<table class="slice-colors">
	<thead>
		<tr>
			<th></th>
			<th><?php _e('Background color','mabel-wheel-of-fortune');?></th>
			<th><?php _e('Text color','mabel-wheel-of-fortune');?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			for($i = 1;$i<=24;$i++) {
				?>
				<tr data-slice="<?php echo $i; ?>" style="display: none;">
					<td><b>Slice <?php echo $i; ?></b></td>
					<td>
						<?php
							\MABEL_WOF\Core\Common\Html::option(new \MABEL_WOF\Core\Models\ColorPicker_Option('bgcolor_slice_'.$i,'',''));
						?>
					</td>
					<td>
						<?php
						\MABEL_WOF\Core\Common\Html::option(new \MABEL_WOF\Core\Models\ColorPicker_Option('fgcolor_slice_'.$i,'',''));
						?>
					</td>
				</tr>
				<?php
			}
		?>
	</tbody>
</table>