<?php
$template_arr=array(
	array(
		'id'=>'template1',
		'title'=>__('WL-875(10x3)', 'wf-woocommerce-packing-list'),
	),
	array(
		'id'=>'template2',
		'title'=>__('WL-125(5X2)', 'wf-woocommerce-packing-list'),
	),
	array(
		'id'=>'template3',
		'title'=>__('WL-150(3X2)', 'wf-woocommerce-packing-list'),
	),
	array(
		'id'=>'template4',
		'title'=>__('WL-100(7X2)', 'wf-woocommerce-packing-list'),
	),
	array(
		'id'=>'template5',
		'title'=>__('WL-75(10X2)', 'wf-woocommerce-packing-list'),
	),
	array(
		'id'=>'template6',
		'title'=>__('WL-25(20X4)', 'wf-woocommerce-packing-list'),
	),
	array(
		'id'=>'template7',
		'title'=>__('WL-5195(15X4)', 'wf-woocommerce-packing-list'),
	),
	array(
		'id'=>'template8',
		'title'=>__('WL-8275(10X3)', 'wf-woocommerce-packing-list'),
	),
	array(
		'id'=>'template9',
		'title'=>__('WL-800(6X3)', 'wf-woocommerce-packing-list'),
	),
	array(
		'id'=>'template10',
		'title'=>__('WL-175(1X1)', 'wf-woocommerce-packing-list'),
	),
);
if(!function_exists('wt_pklist_address_label_generate_preview_html'))
{
	function wt_pklist_address_label_generate_preview_html($html)
	{
		$row_count=floor(Wf_Woocommerce_Packing_List_Addresslabel::get_template_html_attr_vl($html,'data-rows'));
		$col_count=floor(Wf_Woocommerce_Packing_List_Addresslabel::get_template_html_attr_vl($html,'data-columns'));
		$h=170/$row_count-1;
		$out='<div style="display:none;" class="wfte_hidden_template_html">'.$html.'</div>
		<table style="width:inherit;" cellpadding="0" cellspacing=".5">';
		for($i=0; $i<$row_count; $i++)
		{
			$out.='<tr>';
			for($j=0; $j<$col_count; $j++)
			{
				$out.='<td style="border:solid 1px #ccc; box-sizing:border-box; height:'.$h.'px;"></td>';
			}
			$out.='</tr>';
		}
		$out.='</table>';
		return $out;
	}
}
foreach($template_arr as &$template_v)
{	
	ob_start();
	include 'data.'.$template_v['id'].'.php';
	$html=ob_get_clean();
	$template_v['preview_html']=wt_pklist_address_label_generate_preview_html($html);
}