<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<style type="text/css">
.wt_pklist_dc_dragover::after{ content:"<?php _e('Drop here', 'wf-woocommerce-packing-list');?>"; position:absolute; top:0px; left:0px; width:100%; height:100%; background:rgba(232, 236, 245, 0.88); color:#8199c9; display:flex; align-items:center; justify-content:center; } 
.wt_pklist_dc_empty_column:hover:after{ content:'<?php _e("Empty column drag elements", "wf-woocommerce-packing-list");?>'; position:absolute; z-index:10; left:0; top:0; bottom:0; right:0; margin-top:50px; font-size:12px; color:#fff; text-align:center; line-height:0px; padding:15px; text-shadow:1px 0px 1px #666; border:solid 1px #efefef; border-radius:3px; background:#333;}
</style>
<div class="wt_pklist_dc">

	<!-- Assests HTML: start -->
	<div class="wt_pklist_dc_assets_html">
		<div class="wt_pklist_dc_assets_codeview_html">
			<?php echo $assets_codeview_html;?>
		</div>
		<div class="wt_pklist_dc_assets_designview_html">
			<?php echo $assets_html;?>
		</div>
	</div>
	<!-- Assests HTML: end -->

	
	<!-- Property editor elements: start --> 
	<div class="wt_pklist_dc_property_editor_contents">
		<?php include_once plugin_dir_path( __FILE__ )."_property_editor.php"; ?>
	</div>
	<!-- Property editor elements: end --> 

	
	<!-- New sub item popup: start -->
	<div class="wt_pklist_dc_add_new_sub_item wf_pklist_popup">
		<div class="wf_pklist_popup_hd">
			<span style="line-height:40px;" class="dashicons dashicons-plus-alt"></span>
			<span class="wt_pklist_dc_add_new_sub_item_popup_title"></span>
			<div class="wf_pklist_popup_close">X</div>
		</div>
		<div class="wt_pklist_dc_add_new_sub_item_wrn_box">
			<div class="wt_pklist_dc_add_new_sub_item_wrn"> 
			</div>
		</div>
		<div class="wt_pklist_dc_add_new_sub_item_popup_content">
			
		</div>
		<div class="wf_pklist_popup_footer wt_pklist_dc_add_new_sub_item_popup_footer">
			<button type="button" name="" class="button-secondary wf_pklist_popup_cancel">
				<?php _e('Cancel','wf-woocommerce-packing-list');?> 
			</button>
			<button type="button" name="" class="button-primary wt_pklist_dc_add_new_sub_item_submit_btn">
				<?php _e('Save','wf-woocommerce-packing-list');?> 
			</button>	
		</div>
	</div>
	<!-- New sub item popup: end -->


	<!-- Code editor popup: start -->
	<div class="wt_pklist_dc_html_editor wf_pklist_popup">
		<div class="wf_pklist_popup_hd">
			<span style="line-height:40px;" class="dashicons dashicons-editor-code"></span>
			<span class="wt_pklist_dc_html_editor_popup_title"></span>
			<div class="wf_pklist_popup_close">X</div>
		</div>
		<div class="wt_pklist_dc_html_editor_popup_content">
			<textarea id="pklist_dc_code_editor"></textarea>
		</div>
		<div class="wf_pklist_popup_footer wt_pklist_dc_html_editor_popup_footer">
            <div class="wt_pklist_dc_popup_footer_wrn">
                <?php _e('Altering HTML code may reduce the editing compatibility on visual editor', 'wf-woocommerce-packing-list');?>
            </div>
			<button type="button" name="" class="button-secondary wf_pklist_popup_cancel">
				<?php _e('Cancel','wf-woocommerce-packing-list');?> 
			</button>
			<button type="button" name="" class="button-primary wt_pklist_dc_html_editor_submit_btn">
				<?php _e('Apply changes','wf-woocommerce-packing-list');?>
                <!-- we are not saving the template so using `Save` as button title will confuse the user -->
			</button>	
		</div>
	</div>
	<!-- Code editor popup: end -->


	<!-- Add new row popup: start -->
	<div class="wt_pklist_dc_layout_preview_box" tabindex="-1">
		<div class="wt_pklist_dc_layout_preview_box_hd"><?php _e('Adding Row', 'wf-woocommerce-packing-list'); ?></div>
        <div class="wt_pklist_dc_layout_preview_box_info"><?php _e('Select the type of row you want', 'wf-woocommerce-packing-list'); ?></div>
		<?php
		foreach($layout_items as $layout_title=> $layout_data)
		{
			?>
			<div class="wt_pklist_dc_layout_preview_box_inner">
				<div class="wfte_row wfte_padding_left_right float_left clearfix">
					<?php
					$ttl_col=count($layout_data);
					$i=0;
					foreach ($layout_data as $layout_value)
					{
						$i++;
						$css_float_class=($i==$ttl_col ? 'float_right' : 'float_left');
						?>
						<div class="wfte_col-<?php echo $layout_value;?> <?php echo $css_float_class;?>"></div>
						<?php
					}
					?>
				</div>
				<div class="wt_pklist_dc_layout_preview_title"><?php echo $layout_title;?></div>
			</div>
			<?php
		}
		?>
	</div>
	<!-- Add new row popup: end -->

	<!-- Dropdown menu: start --> 
	<div class="wt_pklist_dc_dropdown_menu" tabindex="-1">
		<ul>
			<li class="wt_pklist_dc_activate_theme"><?php _e('Activate', 'wf-woocommerce-packing-list');?></li>
			<li class="wt_pklist_dc_delete_theme"><?php _e('Delete', 'wf-woocommerce-packing-list');?></li>
			<li class="wt_pklist_dc_new_template"><?php _e('Create new', 'wf-woocommerce-packing-list');?></li>
			<li class="wt_pklist_dc_my_templates"><?php _e('My templates', 'wf-woocommerce-packing-list');?></li>
			<?php
			$enable_pdf_preview=apply_filters('wf_pklist_intl_customizer_enable_pdf_preview', false, $template_type);
			if($enable_pdf_preview && class_exists('WC_Order_Query'))
			{
				$query = new WC_Order_Query( array(
				    'limit' => 1,
				    'orderby' => 'date',
				    'order' => 'DESC',
				    'parent'=>0,
				    //'return' => 'ids',
				) );

				$orders = $query->get_orders();
				if(count($orders)>0)
				{
					$order=$orders[0];
					$order_number=$order->get_order_number();
			?>
				<li class="wt_pklist_dc_preview_pdf" data-order-id="<?php echo $order_number;?>"><?php _e('Generate PDF preview', 'wf-woocommerce-packing-list');?></li>
			<?php
				}
			}
			?>
            <li class="wt_pklist_dc_full_code_editor" title="<?php _e('Edit document HTML', 'wf-woocommerce-packing-list');?>"><?php _e('Code editor', 'wf-woocommerce-packing-list');?></li>
		</ul>
	</div>
	<!-- Dropdown menu: end -->

	<!-- Template name prompt on saving: start -->
	<div class="wt_pklist_dc_template_name wf_pklist_popup">
		<div class="wf_pklist_popup_hd">
			<span style="line-height:40px;" class="dashicons dashicons-edit"></span>  <?php _e('Enter a name for your template','wf-woocommerce-packing-list');?>
			<div class="wf_pklist_popup_close">X</div>
		</div>
		<div class="wt_pklist_dc_warn_box">
			<div class="wt_pklist_dc_warn wt_pklist_dc_template_name_wrn">
				<?php _e('Please enter name','wf-woocommerce-packing-list');?> 
			</div>
		</div>
		<div class="wt_pklist_dc_template_name_box">
			<input type="text" style="width:100%;" class="wf_pklist_text_field wt_pklist_dc_template_name_field">			
		</div>
		<div class="wt_pklist_dc_popup_footer">
			<button type="button" style="float:right; margin-right:0px;" class="wt_pklist_dc_btn wt_pklist_dc_btn_primary wt_pklist_dc_template_create_btn">
				<?php _e('Save','wf-woocommerce-packing-list');?> 
			</button>
			<button type="button" style="float:right;" class="wt_pklist_dc_btn wt_pklist_dc_btn_secondary wf_pklist_popup_cancel">
				<?php _e('Cancel','wf-woocommerce-packing-list');?> 
			</button>				
		</div>
	</div>
	<!-- Template name prompt on saving: end -->


	<!-- Default list: start -->
	<div class="wt_pklist_dc_default_template_list wf_pklist_popup">
		<div class="wt_pklist_dc_default_template_list_hd wf_pklist_popup_hd">
			<span style="line-height:40px;" class="dashicons dashicons-admin-appearance"></span> <?php _e('Choose a layout.','wf-woocommerce-packing-list');?>
			<div class="wf_pklist_popup_close">X</div>
		</div>
		<div class="wt_pklist_dc_default_template_list_main wf_pklist_popup_body">
			<div class="wt_pklist_dc_warn_box">
				<div class="wt_pklist_dc_warn" style="line-height:26px;">
					<?php _e('All unsaved changes will be lost upon switching to a new layout.','wf-woocommerce-packing-list');?>
					<br />
					<span class="wt_pklist_dc_new_template_wrn_sub"><?php _e('Save before you proceed.','wf-woocommerce-packing-list');?> 
						<span class="wt_pklist_dc_save_theme_sub_loading"><?php _e('Saving...','wf-woocommerce-packing-list');?></span>
						<button class="button button-secondary wt_pklist_dc_save_btn_sub"><?php _e('Save','wf-woocommerce-packing-list');?></button> 
					</span>
				</div>
			</div>
			<?php
			$def_template_id=0;
			foreach($def_template_arr as $def_template)
			{
				?>
				<div class="wt_pklist_dc_default_template_list_item" data-id="<?php echo $def_template_id;?>">
					<span class="wt_pklist_dc_default_template_list_item_hd"><?php echo $def_template['title'];?></span>
						<div class="wt_pklist_dc_default_template_list_item_inner">
						<?php
						if(isset($def_template['preview_img']) && $def_template['preview_img']!="")
						{
							$template_url=(isset($def_template['template_url']) ? $def_template['template_url'] : $def_template_url);
							?>
							<img src="<?php echo $template_url.$def_template['preview_img'];?>">	
							<?php
						}elseif(isset($def_template['preview_html']) && $def_template['preview_html']!="")
						{
							echo $def_template['preview_html'];
						}
						?>
						</div>		
						<span class="wt_pklist_dc_default_template_list_btn_main">
							<!--
							<button type="button" name="" class="button-primary">
								<?php _e('Use','wf-woocommerce-packing-list');?>
							</button>
							-->
						</span>				
				</div>
				<?php
				$def_template_id++;
			}
			?>
		</div>
	</div>
	<!-- Default list: end -->


	<!-- My template list: start -->
	<div class="wt_pklist_dc_my_template wf_pklist_popup">
		<div class="wf_pklist_popup_hd">
			<span style="line-height:40px;" class="dashicons dashicons-list-view"></span> <?php _e('Templates','wf-woocommerce-packing-list');?>
			<div class="wf_pklist_popup_close">X</div>
		</div>
		<div class="wt_pklist_dc_my_template_main wf_pklist_popup_body">
			<div style="float:left; box-sizing:border-box; width:100%; padding:0px 5px; margin-bottom:5px;">
				<input placeholder="<?php _e('Type template name to search', 'wf-woocommerce-packing-list');?>" type="text" class="wt_pklist_dc_my_template_search">
			</div>
			<div class="wt_pklist_dc_my_template_list">		
				
			</div>
		</div>
	</div>
	<!-- My template list: end -->


	<!-- PDF preview: start -->
	<div class="wt_pklist_dc_pdf_preview wf_pklist_popup">
		<div class="wf_pklist_popup_hd">
			<span style="line-height:40px;" class="dashicons dashicons-pdf"></span> <?php _e('PDF preview','wf-woocommerce-packing-list');?>
			<div class="wf_pklist_popup_close">X</div>
		</div>
		<div class="wt_pklist_dc_pdf_preview_content wf_pklist_popup_body">
			
		</div>
	</div>
	<!-- PDF preview: end -->


	<div class="wt_pklist_dc_main">
		
		<!-- Customizer header: start -->
		<div class="wt_pklist_dc_head">
            <div class="wt_pklist_dc_template_compatibility_wrn"><?php _e("Full functionality of the customizer will not work on this template.", 'wf-woocommerce-packing-list'); ?></div>
			<div class="wt_pklist_dc_head_page_title">
				
			</div>
			<div class="wt_pklist_dc_head_btn_left">
				<button class="wt_pklist_dc_btn wt_pklist_dc_btn_white wt_pklist_dc_undo_btn wt_pklist_dc_btn_inactive" title="<?php _e('Undo', 'wf-woocommerce-packing-list');?>">
					<span class="dashicons dashicons-undo"></span> 
				</button>
				<button class="wt_pklist_dc_btn wt_pklist_dc_btn_white wt_pklist_dc_redo_btn wt_pklist_dc_btn_inactive" title="<?php _e('Redo', 'wf-woocommerce-packing-list');?>">
					<span class="dashicons dashicons-redo"></span>
				</button>
			</div>
			<div class="wt_pklist_dc_head_btn_right">
				<button class="wt_pklist_dc_btn wt_pklist_dc_btn_white" style="margin-right: 5px; color:#777;" onclick="window.location.reload(true);">
					<span class="dashicons dashicons-no-alt"></span> <?php _e('Cancel', 'wf-woocommerce-packing-list');?>
				</button>
				<button class="wt_pklist_dc_btn wt_pklist_dc_btn_white wt_pklist_dc_save_btn">
					<span class="dashicons dashicons-saved"></span> <?php _e('Save', 'wf-woocommerce-packing-list');?>
				</button>
				<button class="wt_pklist_dc_btn wt_pklist_dc_btn_white wt_pklist_dc_options_btn" style="margin-right:0px;">
					<span class="dashicons dashicons-menu"></span>
				</button>
			</div>
		</div>
		<!-- Customizer header: end -->

		<!-- Customizer body: start -->
		<div class="wt_pklist_dc_body">
			<div class="wt_pklist_dc_visual_editor"></div>
			<div class="wt_pklist_dc_code_editor"></div>
		</div>
		<!-- Customizer body: end -->

		<!-- Customizer sidebar: start -->
		<div class="wt_pklist_dc_sidebar">
			<div class="wt_pklist_dc_sidebar_tabhead">
				<div class="wt_pklist_dc_sidebar_tab_btn wt_pklist_active_tab" data-tab-target="wt-pklist-dc-sidebar-page"><?php _e('Page', 'wf-woocommerce-packing-list');?></div>
				<div class="wt_pklist_dc_sidebar_tab_btn" data-tab-target="wt-pklist-dc-sidebar-block"><?php _e('Block', 'wf-woocommerce-packing-list');?></div>
				<div class="wt_pklist_dc_sidebar_tab_btn" data-tab-target="wt-pklist-dc-sidebar-assets"><?php _e('Assets', 'wf-woocommerce-packing-list');?></div>
			</div>
			<div class="wt_pklist_dc_sidebar_tabcontainer">
				
				<!-- Sidebar page property editor: start -->
				<div class="wt_pklist_dc_sidebar_tabcontent" data-tab-id="wt-pklist-dc-sidebar-page">
					<div class="wt_pklist_dc_sidebar_property_block"></div>
				</div>
				<!-- Sidebar page property editor: end -->

				<!-- Sidebar block property editor: start -->
				<div class="wt_pklist_dc_sidebar_tabcontent" data-tab-id="wt-pklist-dc-sidebar-block">			
				</div>
				<!-- Sidebar block property editor: end -->

				<!-- Sidebar assets: start -->
				<div class="wt_pklist_dc_sidebar_tabcontent" data-tab-id="wt-pklist-dc-sidebar-assets">				
					<div class="wt_pklist_dc_sidebar_top_panel wt_pklist_dc_assets_top_panel">
						<input type="text" name="" placeholder="<?php _e('Search assets', 'wf-woocommerce-packing-list');?>" class="wt_pklist_dc_assets_search">
					</div>
					<?php
					if(is_array($assets))
					{
						foreach ($assets as $key => $value)
						{
							$is_single_item=false;
							$is_accordian=false;
							if(isset($value['type']) && ($value['type']=="element_group" || $value['type']=="group"))
							{
								$is_accordian=true;
							}else
							{
								$is_single_item=true;
							}
							$slug=(isset($value['slug']) ? $value['slug'] : '');
							$title=(isset($value['title']) ? $value['title'] : '...');
							?>
							<div class="wt_pklist_dc_sidebar_tabaccord wt_pklist_dc_sidebar_tabaccord_asset <?php echo ($is_single_item ? 'wfte_draggable wt_pklist_dc_asset_item' : ''); ?>" <?php echo ($is_single_item ? 'draggable="true" data-slug="'.$slug.'"' : ''); ?>>
								<div class="wt_pklist_dc_sidebar_tabaccord_hd noselect">
									<?php
									if($is_accordian)
									{
										?>
										<div class="wt_pklist_dc_sidebar_tabaccord_btn wt_pklist_dc_sidebar_tabaccord_accord"><span class="dashicons dashicons-arrow-down-alt2"></span></div>
										<?php
									}
									?>
									
									<?php echo $title; ?>
								</div>
								<?php
								if($is_accordian)
								{
									$drag_item_tooltip=__('Drag item to editor', 'wf-woocommerce-packing-list');
								?>
									<div class="wt_pklist_dc_sidebar_tabaccord_content">
										<?php
										if($value['type']=='group')
										{
											$sub_items=isset($value['sub_items']) && is_array($value['sub_items']) ? $value['sub_items'] : array();
											foreach($sub_items as $sub_item)
											{
												?>
												<div class="wt_pklist_dc_asset_item wfte_draggable" title="<?php echo $drag_item_tooltip;?>" draggable="true" data-slug="<?php echo $sub_item['slug'];?>"><?php echo (isset($sub_item['title']) ? $sub_item['title'] : '..'); ?></div>
												<?php
											}
										}elseif($value['type']=='element_group')
										{
											$elements=isset($value['elements']) && is_array($value['elements']) ? $value['elements'] : array();
											if(!isset($elements[$slug])) /* check main item exists as element. If not then add */
											{
												$elements=array_merge(array($slug=>$title), $elements);
											}
											foreach($elements as $element_slug=>$element_title)
											{
												/** 
                                                 * same copy of below code in JS section too, Please consider there also, if making any changes.
                                                 *  File : pklist_dc_ajax.js
                                                 *  Method : add_new_order_meta
                                                */

                                                $checkbox_css_class='wt_pklist_dc_asset_element_group_checkbox';
												$item_css_class='wt_pklist_dc_asset_element_group_sub';
												if($element_slug==$slug)
												{
													$checkbox_css_class='wt_pklist_dc_asset_element_group_checkbox_main';
													$item_css_class='wt_pklist_dc_asset_element_group_main';
												}              
												?>
												<div class="wt_pklist_dc_asset_item wfte_draggable <?php echo $item_css_class;?>" title="<?php echo $drag_item_tooltip;?>" draggable="true" data-slug="<?php echo $element_slug;?>" data-parent-slug="<?php echo $slug;?>">
													<input type="checkbox" class="<?php echo $checkbox_css_class;?>" checked="checked" data-slug="<?php echo $element_slug;?>">
													<?php echo $element_title; ?>
												</div>
												<?php
											}
                                            if(isset($assets_add_new_item[$slug]) && isset($assets_add_new_item[$slug]['add_to_assets']) && $assets_add_new_item[$slug]['add_to_assets'])
                                            {
                                                ?>
                                                <div class="wt_pklist_dc_assets_add_new_panel" data-slug="<?php echo $slug;?>"> <a class="wt_pklist_dc_btn wt_pklist_dc_btn_primary wt_pklist_dc_sidebar_assets_add_new" data-slug="<?php echo $slug;?>"><?php _e('Add new', 'wf-woocommerce-packing-list');?></a> </div>
                                                <?php
                                            }
										}
										?>
									</div>
								<?php
								}else
								{
									
								}
								?>
							</div>
							<?php
						}
					}
					?>
				</div>
				<!-- Sidebar assets: end -->

			</div>
		</div>
		<!-- Customizer sidebar: end -->

	</div>
</div>