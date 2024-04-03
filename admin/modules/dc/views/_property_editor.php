<?php
if(is_array($editor_panel_data))
{
	foreach ($editor_panel_data as $editor_panel_key => $editor_panel_value)
	{
		?> 
		<div class="wt_pklist_dc_sidebar_property_group_block" data-property-group="<?php echo $editor_panel_key;?>">
			<?php
			if(isset($editor_panel_value['title']) && $editor_panel_value['title']!="")
			{
			?>
				<div class="wt_pklist_dc_sidebar_property_hd"><?php echo $editor_panel_value['title'];?></div>
			<?php
			}
			?>
			<div class="wt_pklist_dc_sidebar_property_container">
				<?php
				if(isset($editor_panel_value['fields']) && is_array($editor_panel_value['fields']))
				{
					foreach ($editor_panel_value['fields'] as $field_key => $field_value)
					{
						$container_width=(isset($field_value['width']) ? 'width:'.$field_value['width'].';' : '');
						$container_float=(isset($field_value['float']) ? 'float:'.$field_value['float'].';' : '');
						$property_slug=(isset($field_value['slug']) ? $field_value['slug'] : ''); /* some side specific properties need this */
						$field_type=(isset($field_value['field_type']) ? $field_value['field_type'] : '');
						
						$property_specific_css_for_label='wt_pklist_dc_property_editor_label_for_'.$field_key;
						$field_type_specific_css_for_label='wt_pklist_dc_property_editor_label_for_input_'.$field_type;
						?>
						<div class="wt_pklist_dc_sidebar_property_block" data-property-slug="<?php echo $property_slug;?>" data-property="<?php echo $field_key;?>" style="<?php echo $container_width.$container_float;?>">
							<label class="wt_pklist_dc_sidebar_property_label <?php echo $property_specific_css_for_label.' '.$field_type_specific_css_for_label;?>"><?php echo $field_value['label'];?></label>
							<?php
							$property_specific_css='wt_pklist_dc_property_editor_input_for_'.$field_key;
							if($field_type!="")
							{
								if($field_value['field_type']=='textarea')
								{
									?>
									<textarea class="wt_pklist_dc_property_editor_input wt_pklist_dc_keyup"></textarea>
									<?php
								}elseif($field_value['field_type']=='color')
								{
									$dc->color_picker($field_key);

								}elseif($field_value['field_type']=='four_side_text') /* this is for margin/padding */
								{
									$field_sub_type=(isset($field_value['field_sub_type']) ? $field_value['field_sub_type'] : '');
									$sides=array('top', 'right', 'bottom', 'left');
									$dropdown_items=(isset($field_value['items']) ? $field_value['items'] : array()); /* for select inputs */
									$dc->prepare_four_side_input($field_sub_type, $field_key, 'wt_pklist_dc_four_side_prop wt_pklist_dc_four_side_prop_main', $dropdown_items);
									?>
									<div class="wt_pklist_dc_four_side_prop_checkbox_conatiner">
										<input type="checkbox" class="wt_pklist_dc_four_side_prop_all_sides"> <label><?php _e('All sides', 'wf-woocommerce-packing-list');?></label>
									</div>
									<div style="float:left; width:100%; clear:both;"></div>
									<div class="wt_pklist_dc_four_side_prop_container">
										<?php
										foreach($sides as $side)
										{	
											?>
											<div style="float:left; width:25%">
												<?php
												$dc->prepare_four_side_input($field_sub_type, $field_key, 'wt_pklist_dc_four_side_prop wt_pklist_dc_four_side_prop_sub', $dropdown_items);
												?>
												<label style="float:left; width:100%; text-align:center;"><?php _e(ucfirst($side), 'wf-woocommerce-packing-list');?></label>
											</div>
											<?php
										}
										?>
									</div>
									<?php
								}elseif($field_value['field_type']=='dropdown')
								{
									$dropdown_items=(isset($field_value['items']) && is_array($field_value['items']) ? $field_value['items'] : array());
									?>
									<select class="wt_pklist_dc_property_editor_input wt_pklist_dc_change" style="line-height:14px;">
									<?php
									foreach ($dropdown_items as $val=> $label)
									{
										?>
										<option value="<?php echo $val;?>"><?php echo $label;?></option>
										<?php
									}
									?>
									</select>
									<?php
								}elseif($field_value['field_type']=='button_group')
								{
									$items=(isset($field_value['items']) && is_array($field_value['items']) ? $field_value['items'] : array());
									?>
									<div class="wt_pklist_dc_sidebar_property_button_group">
										<?php
										foreach($items as $item_field_key => $item_field_value)
										{
											$item_field_type=(isset($item_field_value['field_type']) ? $item_field_value['field_type'] : 'button_radio');
											$item_field_label=(isset($item_field_value['label']) ? $item_field_value['label'] : ucfirst($item_field_key));
											if($item_field_type=='button_checkbox')
											{
												$icon=(isset($item_field_value['icon']) ? $item_field_value['icon'] : $item_field_label);
												$values=(isset($item_field_value['values']) && is_array($item_field_value['values']) ? $item_field_value['values'] : array());
												?>
												<span class="wt_pklist_dc_sidebar_property_button_group_btn wt_pklist_button_checkbox" data-values="<?php echo implode('|', $values);?>" data-main-value="<?php echo $values[0];?>" data-value="" style="margin-right:5px;" data-property="<?php echo $item_field_key;?>"><?php echo $icon;?></span>
												<?php
											}
											elseif($item_field_type=='button_radio')
											{
												$dc->button_radio($item_field_key, $item_field_value);								
											}	
										}
										?>
									</div>
									<?php

								}elseif($field_value['field_type']=='button_radio')
								{
									$dc->button_radio($field_key, $field_value);
								}
								elseif($field_value['field_type']=='custom_preset')
								{
									$preset_arr=(isset($field_value['preset_arr']) && is_array($field_value['preset_arr']) ? $field_value['preset_arr'] : array());
									?>
									<div style="float:left; width:49%;">
										<input type="text" class="wt_pklist_dc_property_editor_input wt_pklist_dc_change wt_pklist_dc_keyup">
									</div>
									<div style="float:left; width:49%;">
										<select class="wt_pklist_dc_preset_input">
											<?php
											foreach($preset_arr as $preset_key => $preset_value)
											{
												?>
												<option value="<?php echo $preset_key;?>"><?php echo $preset_value;?></option>
												<?php
											}
											?>
										</select>
									</div>
									<?php
								}
								elseif($field_value['field_type']=='image-uploader')
								{
									?>
							        <button type="button" class="wt_pklist_dc_btn wt_pklist_dc_btn_primary wt_pklist_dc_file_uploader_btn" value="" /><?php _e('Select image', 'wf-woocommerce-packing-list'); ?></button>
									<?php
								}
								else
								{
									?>
									<input type="text" name="" class="wt_pklist_dc_property_editor_input wt_pklist_dc_keyup <?php echo $property_specific_css;?>" value="">
									<?php	
								}
							}else
							{
								?>
								<input type="text" name="" class="wt_pklist_dc_property_editor_input wt_pklist_dc_keyup <?php echo $property_specific_css;?>" value="">
								<?php
							}
							?>
						</div>
						<?php
					}
				}
				?>
			</div>
		</div>
		<?php
	}
}
?>