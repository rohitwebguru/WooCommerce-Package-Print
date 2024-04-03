<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!-- style start ****************************************-->
<style>
    table {
    border-collapse: collapse;
    border-spacing: 0;
    width: 100%;
    border: 1px solid #ddd;
    }

    th, td {
    text-align: left;
    padding: 8px;
    }
    .preparation_instruction{
        margin:20px auto;
    }
    .table_container table thead tr:first-child {
        background-color: #dcdcde;
    }

    .table_container {
        max-height: 390px;
        overflow-y: auto;
    }
    tr:nth-child(even){background-color: #f2f2f2}
    .wp-instructor-header{
        display:flex;
        justify-content:space-between;
    }
    .wp-instructor-header .heading{
        font-size:24px;
        font-weight:600;
    }
    .table_container table tbody .btn {
        display: inline-block;
        font-weight: 400;
        color: #212529;
        text-align: center;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        cursor: pointer;
    }
    .table_container table tbody .btn-danger {
        color: #fff;
        background-color: #dc3545;
        border-color: #dc3545;
    }
    .table_container table tbody .btn-danger:hover {
        color: #fff;
        background-color: #c82333;
        border-color: #bd2130;
    }
    .btn-primary {
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
    }
    .btn-primary:hover {
        color: #fff;
        background-color: #0069d9;
        border-color: #0062cc;
    }

    #show_instruction_table{
        display:none;
    }

    /* model style start */
    .models_header{
        display:flex;
        justify-content:space-between;
    }
    .preparation_forms_field,.packing_forms_field{
        margin:0 auto;
        display:flex;
        flex-wrap:wrap;
        gap:40px;
    }
    .preparation_forms_field .form-group,.packing_forms_field .form-group{
        /* width:25%;
        max-width: 50%; */
        display:flex;
        justify-content:space-between;
        align-items:center;
        /* border:1px solid red; */
        gap:10px
    }
    .model_form .model_form_save{
        display:flex;
        justify-content:start;
        margin:20px 0px;
    }
    .model_form .model_form_save .btn {
        display: inline-block;
        font-weight: 400;
        color: #212529;
        text-align: center;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        cursor: pointer;
    }

    .model_form .model_form_save .btn-primary {
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
    }
    .model_form .model_form_save .btn-primary:hover {
        color: #fff;
        background-color: #0069d9;
        border-color: #0062cc;
    }
    .packing_instruction{
        margin:20px 0px;
    }
    .show_tables_buttons{
        display:flex;
        gap:10px;
    }
    .show_tables_buttons .buttonPrimary{
        display: inline-block;
        font-weight: 400;
        color: #212529;
        text-align: center;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        cursor: pointer;
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
    }
    .show_tables_buttons .buttonDanger{
        display: inline-block;
        font-weight: 400;
        color: #212529;
        text-align: center;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        cursor: pointer;
        color: #fff;
        background-color: #dc3545;
        border-color: #dc3545;
    }
    .delete-condition{
        display: inline-block;
        font-weight: 400;
        color: #212529;
        text-align: center;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        cursor: pointer;
        color: #fff;
        background-color: #dc3545;
        border-color: #dc3545;
    }
    /* model style end */
</style>
<!-- style end ****************************************-->


<!-- HTML Content start ****************************************-->


<div class="wf-tab-content" data-id="<?php echo $target_id; ?>">
    <div class="preparation_instruction">
        <div class="wp-instructor-header">
            <div><h1 class="heading">Preparation Instruction</h1></div>
            <div><button id="open-modal-preparation-instruction" class="button-primary"><?php _e('Add Preparation Instruction', 'wf-woocommerce-packing-list');?></button></div>
        </div>
        <!-- main table preparation -->
        <div style="overflow-x:auto;" class="table_container">
            <table>
                <thead>
                    <tr>
                        <th>Instruction ID</th>
                        <th>Instruction type</th>
                        <th>Instruction text</th>
                        <th>Instruction file</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                        global $wpdb;
                        $table_name = $wpdb->prefix . 'packing_instructions';
                        $instructions = $wpdb->get_results("SELECT * FROM $table_name WHERE instruction_type = 'preparation_instruction'", ARRAY_A);

                        if (!empty($instructions)) {
                            foreach ($instructions as $instruction) {
                                echo "<tr>";
                                echo "<td>" . $instruction['id'] . "</td>";
                                echo "<td>" . $instruction['instruction_type'] . "</td>";
                                echo "<td>" . $instruction['text_instruction'] . "</td>";
                                // echo "<td>" . $instruction['file_instruction'] . "</td>";
                                if (!empty($instruction['file_instruction'])) {
                                echo '<td><img src="' . $instruction['file_instruction'] . '" alt="Instruction Image" width="100px"></td>';
                                }else{
                                    echo "<td>" . $instruction['file_instruction'] . "</td>";
                                }
                                echo '<td class="show_tables_buttons">';
                                echo '<button class="buttonPrimary add-condition-btn" data-instruction-id="' . $instruction['id'] . '">Add Condition</button>';
                                echo '<button class="buttonDanger delete-instruction-btn" data-instruction-id="' . $instruction['id'] . '">Delete Instruction</button>';
                                echo '<button class="buttonPrimary show-condition" data-instruction-id="' . $instruction['id'] . '">Show Conditions</button>';
                                echo '</td>';
                                echo "</tr>";
                                echo '<tr id="show_instruction_table_' . $instruction['id'] . '" class="condition-row" style="display:none;"><td colspan="5">';

                                // Fetch data from wp_packing_condition table based on the instruction ID
                                $condition_table_name = $wpdb->prefix . 'packing_conditions';
                                $conditions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $condition_table_name WHERE instruction_id = %d", $instruction['id']), ARRAY_A);

                                if (!empty($conditions)) {
                                    // Output condition data
                                    echo '<table>';
                                    echo '<tr>';
                                    echo '<th>Condition ID</th>';
                                    echo '<th>Parameter1</th>';
                                    echo '<th>Parameter2</th>';
                                    echo '<th>Parameter3</th>';
                                    echo '<th>Parameter4</th>';
                                    echo '<th>Parameter5</th>';
                                    echo '<th>Action</th>';
                                    echo '</tr>';
                                    foreach ($conditions as $condition) {
                                        echo '<tr>';
                                        echo '<td>' . $condition['id'] . '</td>';
                                        echo '<td>' . $condition['p_parameter1'] . '</td>';
                                        echo '<td>' . $condition['p_parameter2'] . '</td>';
                                        echo '<td>' . $condition['p_parameter3'] . '</td>';
                                        echo '<td>' . $condition['p_parameter4'] . '</td>';
                                        echo '<td>' . $condition['p_parameter5'] . '</td>';
                                        // Add delete button
                                        echo '<td><button class=" delete-condition" data-condition-id="' . $condition['id'] . '">Delete</button></td>';
                                        echo '</tr>';
                                    }
                                    echo '</table>';
                                } else {
                                    echo 'No conditions found for this instruction.';
                                }

                                echo '</td></tr>';
                                }
                            } else {
                                // No data found message
                                echo "<tr><td colspan='5'>No data found</td></tr>";
                            }
                            ?>

                </tbody>
            </table>
        </div>

    </div>
    <div class="packing_instruction">
        <div class="wp-instructor-header">
            <div><h1 class="heading">Packing Instruction</h1></div>
            <div><button id="open-modal-packing-instruction" class="button-primary"><?php _e('Add Packing Instruction', 'wf-woocommerce-packing-list');?></button></div>
        </div>

        <div style="overflow-x:auto;" class="table_container">
            <table id="instruction-table">
                <thead>
                    <tr>
                        <th>Instruction ID</th>
                        <th>Instruction type</th>
                        <th>Instruction text</th>
                        <th>Instruction file</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php

                        global $wpdb;
                        $table_name = $wpdb->prefix . 'packing_instructions';
                        $instructions = $wpdb->get_results("SELECT * FROM $table_name WHERE instruction_type = 'packing_instruction'", ARRAY_A);

                        if (!empty($instructions)) {
                            foreach ($instructions as $instruction) {
                                echo "<tr>";
                                echo "<td>" . $instruction['id'] . "</td>";
                                echo "<td>" . $instruction['instruction_type'] . "</td>";
                                echo "<td>" . $instruction['text_instruction'] . "</td>";
                                // echo "<td>" . $instruction['file_instruction'] . "</td>";
                                if (!empty($instruction['file_instruction'])) {
                                echo '<td><img src="' . $instruction['file_instruction'] . '" alt="Instruction Image" width="100px"></td>';
                                }else{
                                    echo "<td>" . $instruction['file_instruction'] . "</td>";
                                }
                                echo '<td class="show_tables_buttons">';
                                echo '<button class="buttonPrimary add-condition-btn" data-instruction-id="' . $instruction['id'] . '">Add Condition</button>';
                                echo '<button class="buttonDanger delete-instruction-btn" data-instruction-id="' . $instruction['id'] . '">Delete Instruction</button>';
                                echo '<button class="buttonPrimary show-condition" data-instruction-id="' . $instruction['id'] . '">Show Conditions</button>';
                                echo '</td>';
                                echo "</tr>";
                                echo '<tr id="show_instruction_table_' . $instruction['id'] . '" class="condition-row" style="display:none;"><td colspan="5">';

                                // Fetch data from wp_packing_condition table based on the instruction ID
                                $condition_table_name = $wpdb->prefix . 'packing_conditions';
                                $conditions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $condition_table_name WHERE instruction_id = %d", $instruction['id']), ARRAY_A);

                                if (!empty($conditions)) {
                                    // Output condition data
                                    echo '<table>';
                                    echo '<tr>';
                                    echo '<th>Condition ID</th>';
                                    echo '<th>Parameter1</th>';
                                    echo '<th>Parameter2</th>';
                                    echo '<th>Parameter3</th>';
                                    echo '<th>Parameter4</th>';
                                    echo '<th>Parameter5</th>';
                                    echo '<th>Action</th>';
                                    echo '</tr>';
                                    foreach ($conditions as $condition) {
                                        echo '<tr>';
                                        echo '<td>' . $condition['id'] . '</td>';
                                        echo '<td>' . $condition['p_parameter1'] . '</td>';
                                        echo '<td>' . $condition['p_parameter2'] . '</td>';
                                        echo '<td>' . $condition['p_parameter3'] . '</td>';
                                        echo '<td>' . $condition['p_parameter4'] . '</td>';
                                        echo '<td>' . $condition['p_parameter5'] . '</td>';
                                        // Add delete button
                                        echo '<td><button class=" delete-condition" data-condition-id="' . $condition['id'] . '">Delete</button></td>';
                                        echo '</tr>';
                                    }
                                    echo '</table>';
                                } else {
                                    echo 'No conditions found for this instruction.';
                                }

                                echo '</td></tr>';
                                }
                            } else {
                                // No data found message
                                echo "<tr><td colspan='5'>No data found</td></tr>";
                            }
                            ?>

                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- HTML Content End ************************************************ -->





<!-- JQuery start -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script>


    jQuery(document).ready(function($) {


        // show_instruction_conditions
        $('.show-condition').click(function() {
            var instructionId = $(this).data('instruction-id');
            var conditionRowId = '#show_instruction_table_' + instructionId;

            //   Change text
            var button_text =  $( this ).text();
            $('.show-condition').text( 'Show Conditions' );

            if(button_text == 'Show Conditions'){
                $( this ).text( 'Hide Conditions' );
            }else{
                $( this ).text( 'Show Conditions' );
            }

            $('.condition-row').not(conditionRowId).hide();
            $(conditionRowId).toggle();
        });

        // preparation-instruction show hide model
        $('#open-modal-preparation-instruction').click(function() {
            $('#preparation-instruction').show();
        });
        $('#close-modal-preparation-instruction').click(function() {
            $('#preparation-instruction').hide();
        });

         // packing-instruction show hide model
         $('#open-modal-packing-instruction').click(function() {
            $('#packing-instruction').show();
        });
        $('#close-modal-packing-instruction').click(function() {
            $('#packing-instruction').hide();
        });

        //preparation text-instruction show hide
        $('#instruction_text').change(function() {
            if ($(this).is(':checked')) {
                $('#text_instruction').show();
                $('#file_instruction').hide();
            }
        });

        $('#instruction_file').change(function() {
            if ($(this).is(':checked')) {
                $('#text_instruction').hide();
                $('#file_instruction').show();
            }
        });

         //packing text-instruction show hide
         $('#p_instruction_text').change(function() {
            if ($(this).is(':checked')) {
                $('#p_text_instruction').show();
                $('#p_file_instruction').hide();
            }
        });

        $('#p_instruction_file').change(function() {
            if ($(this).is(':checked')) {
                $('#p_text_instruction').hide();
                $('#p_file_instruction').show();
            }
        });


         // data save packing-instruction

         $('#p_save_button').click(function(e) {

            e.preventDefault();
            $('#p_save_button').hide();
            $('.spinner').css('visibility', 'visible');

            if ($('#p_parameter1').val()==="" || $('#p_parameter2').val()==="" || $('#p_parameter4').val()==="" || $('#p_parameter5').val()==="") {
                alert("All Fields are required");
                $('#p_save_button').show();
                $('.spinner').css('visibility', 'hidden');
                return;
            }



            var instructionOption = $('input[name="p_instruction_option"]:checked').val();
            var form_data = new FormData();

            if (instructionOption === 'text') {
                if ($('#p_text_instruction_input').val()==="") {
                    alert("All Fields are required");
                    $('#p_save_button').show();
                    $('.spinner').css('visibility', 'hidden');
                    return;
                }
                form_data.append('text_instruction', $('#p_text_instruction_input').val());
            } else if (instructionOption === 'file') {
            // var fileInput = $('#file_instruction_input').prop('files')[0];
            var fileInput = $('#p_file_instruction_input')[0].files[0];
                // console.log(fileInput);

                if (fileInput) {
                    if (!fileInput.type.match('image.*')) {
                        alert('Please select an image file.');
                        $('#preparation_save_button').show();
                        $('.spinner').css('visibility', 'hidden');
                        return;
                    }
                    form_data.append('file', fileInput);
                    form_data.append('file_instruction', fileInput);
                } else {
                    alert('Please select a file.');
                    $('#p_save_button').show();
                    $('.spinner').css('visibility', 'hidden');
                    return;
                }
            }

            form_data.append('instruction_type', 'packing_instruction');
            form_data.append('p_parameter1', $('#p_parameter1').val());
            form_data.append('p_parameter2', $('#p_parameter2').val());
            // form_data.append('p_parameter3', $('#p_parameter3').val());
            form_data.append('p_parameter4', $('#p_parameter4').val());
            form_data.append('p_parameter5', $('#p_parameter5').val());
            form_data.append('action', 'wt_pklist_save_intructions');

            var p_parameter2_value = $('#p_parameter2').val();
            if (p_parameter2_value === 'quantity_in_the_product' || p_parameter2_value === 'quantity_in_each_product_of_category') {
                form_data.append('p_parameter3', $('#p_parameter3').val());
            }
            // Add other form fields to formData


            console.log(form_data);

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                type: 'POST',
                data: form_data,
                contentType: false,
                processData: false,
                success: function(data) {
                    console.log(data);
                    $('#p_save_button').show();
                    $('.spinner').css('visibility', 'hidden');
                    // clear all input fields
                    $('#packing-instruction').hide();
                    window.location.reload();
                    $('#p_text_instruction_input, #p_file_instruction_input, #p_parameter3, #p_parameter5').val('');
                }
            });
        });


        // data save preparation-instruction

        $('#preparation_save_button').click(function(e) {
            e.preventDefault();
            $('#preparation_save_button').hide();
            $('.spinner').css('visibility', 'visible');
            if ($('#parameter1').val()==="" || $('#parameter2').val()==="" || $('#parameter4').val()==="" || $('#parameter5').val()==="") {
                alert("All Fields are required");
                $('#preparation_save_button').show();
                    $('.spinner').css('visibility', 'hidden');
                return;
            }

            var instructionOption = $('input[name="instruction_option"]:checked').val();
            console.log(instructionOption);

            // var formData = '';
            var form_data = new FormData();


            if (instructionOption === 'text') {
                if ($('#text_instruction_input').val()==="") {
                    alert("All Fields are required");
                    $('#preparation_save_button').show();
                    $('.spinner').css('visibility', 'hidden');
                    return;
                }
                form_data.append('text_instruction', $('#text_instruction_input').val());

            } else if (instructionOption === 'file') {
                // var fileInput = $('#file_instruction_input').prop('files')[0];
                var fileInput = $('#file_instruction_input')[0].files[0];
                // console.log(fileInput);

                if (fileInput) {
                    if (!fileInput.type.match('image.*')) {
                        alert('Please select an image file.');
                        $('#preparation_save_button').show();
                        $('.spinner').css('visibility', 'hidden');
                        return;
                    }
                    form_data.append('file', fileInput);
                    form_data.append('file_instruction', fileInput);
                } else {
                    alert('Please select a file.');
                    $('#preparation_save_button').show();
                    $('.spinner').css('visibility', 'hidden');
                    return;
                }
            }
            console.log($('#parameter3').val())

            form_data.append('instruction_type', 'preparation_instruction');
            form_data.append('p_parameter1', $('#parameter1').val());
            form_data.append('p_parameter2', $('#parameter2').val());
            // form_data.append('p_parameter3', $('#parameter3').val());
            form_data.append('p_parameter4', $('#parameter4').val());
            form_data.append('p_parameter5', $('#parameter5').val());
            form_data.append('action', 'wt_pklist_save_intructions');

            var p_parameter2_value = $('#parameter2').val();
            if (p_parameter2_value === 'quantity_in_the_product' || p_parameter2_value === 'quantity_in_each_product_of_category') {
                form_data.append('p_parameter3', $('#parameter3').val());
            }

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                type: 'POST',
                data: form_data,
                contentType: false,
                processData: false,
                success: function(data) {
                    console.log(data);
                    $('#preparation_save_button').show();
                    $('.spinner').css('visibility', 'hidden');
                    // Clear all input fields
                    $('#preparation-instruction').hide();
                    window.location.reload();
                    $('#text_instruction_input, #file_instruction_input, #parameter3, #parameter5').val('');
                }
            });


        });


        // deletion delete-instruction-btn
        $(".delete-instruction-btn").click(function() {
            var instructionId = $(this).data("instruction-id");
            var confirmation = confirm("Are you sure you want to delete this instruction?");
            var form_data = new FormData();
            form_data.append('action', 'wt_pklist_delete_instruction');
            form_data.append('instruction_id', instructionId);
            console.log(confirmation);
            if (confirmation) {
                jQuery.ajax({
                    url: "<?php echo admin_url('admin-ajax.php') ?>",
                    type: "POST",
                    data: form_data,
                    contentType: false,
                processData: false,
                    success: function(response) {

                        console.log("Instruction deleted successfully: " + instructionId);
                        window.location.reload();

                    }
                });
            }
        });


         // deletion delete-condition
         $(".delete-condition").click(function() {
            var conditionId = $(this).data("condition-id");
            var confirmation = confirm("Are you sure you want to delete this Condition?");
            var form_data = new FormData();
            form_data.append('action', 'wt_pklist_delete_condition');
            form_data.append('condition_id', conditionId);
            console.log(confirmation);
            if (confirmation) {
                jQuery.ajax({
                    url: "<?php echo admin_url('admin-ajax.php') ?>",
                    type: "POST",
                    data: form_data,
                    contentType: false,
                    processData: false,
                    success: function(response) {

                        console.log("condition deleted successfully: " + conditionId);
                        window.location.reload();

                    }
                });
            }
        });


        //Add condition
        $(".add-condition-btn").click(function(){
            var instructionId = $(this).data('instruction-id');
        // Set the instruction ID in the hidden input field
        $('#instruction_id').val(instructionId);
            $("#add-condition-data").css('display', 'block');
        });
        $("#close-modal-condition").click(function(){
            $("#add-condition-data").css('display', 'none');
        });


        // data save condition-instruction

        $('#add_preparation_condition').click(function(e) {

            e.preventDefault();
            $('#add_preparation_condition').hide();
            $('.spinner').css('visibility', 'visible');

            var form_data = new FormData();

            if ($('#condition1').val()==="" || $('#condition2').val()==="" || $('#condition3').val()==="" || $('#condition4').val()==="" || $('#condition5').val()==="") {
                alert("All Fields are required");
                $('#add_preparation_condition').show();
                $('.spinner').css('visibility', 'hidden');
                return;
            }

            form_data.append('instruction_id', $('#instruction_id').val());
            form_data.append('p_parameter1', $('#condition1').val());
            form_data.append('p_parameter2', $('#condition2').val());
            // form_data.append('p_parameter3', $('#condition3').val());
            form_data.append('p_parameter4', $('#condition4').val());
            form_data.append('p_parameter5', $('#condition5').val());
            form_data.append('action', 'wt_pklist_save_condition');

            var p_parameter2_value = $('#condition2').val();
            if (p_parameter2_value === 'quantity_in_the_product' || p_parameter2_value === 'quantity_in_each_product_of_category') {
                form_data.append('p_parameter3', $('#condition3').val());
            }

            // Add other form fields to formData


            console.log(form_data);

            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                type: 'POST',
                data: form_data,
                contentType: false,
                processData: false,
                success: function(data) {
                    console.log(data);
                    $('#add_preparation_condition').show();
                    $('.spinner').css('visibility', 'hidden');
                    // clear all input fields
                    window.location.reload();
                    $(' #condition3, #condition5').val('');
                }
            });
        });


        //parameter3 hide show  packing instruction
        $('#p_parameter2').change(function(){
            var selectedOption = $(this).val();
            if(selectedOption!=="quantity_in_the_product" && selectedOption!=="quantity_in_each_product_of_category"){
                $('#form_parameter3').hide();
            }else{
                $('#form_parameter3').show();
            }
        });

         //parameter3 hide show  preparation instruction
        $('#parameter2').change(function(){
            var selectedOption = $(this).val();
            if(selectedOption!=="quantity_in_the_product" && selectedOption!=="quantity_in_each_product_of_category"){
                $('#p_parameter3_hide_show').hide();
            }else{
                $('#p_parameter3_hide_show').show();
            }
        });
         //parameter3 hide show  condition instruction
        $('#condition2').change(function(){
            var selectedOption = $(this).val();
            if(selectedOption!=="quantity_in_the_product" && selectedOption!=="quantity_in_each_product_of_category"){
                $('#condition3_show_hide').hide();
            }else{
                $('#condition3_show_hide').show();
            }
        });
    });

</script>


<!-- models ************************************************************************************** -->

<!-- add modal packing-instruction  -->
<div id="packing-instruction" style="display: none; position: fixed; z-index: 999; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
    <div style="background-color: white; width: 50%; margin: 100px auto; padding: 20px;">
        <div class="models_header">
            <h2>Add New Packing Instruction</h2>
            <div>
                <button id="close-modal-packing-instruction" class="button"><?php _e('Close', 'wf-woocommerce-packing-list');?></button>
            </div>
        </div>
        <div id="packing-instruction-form" class="model_form">
            <div class="packing_forms_field">
                <div class="form-group">
                    <label for="p_parameter1">Parameter 1:</label>
                    <select name="p_parameter1" id="p_parameter1" class="form-control">
                        <option value="AND">AND</option>
                        <!-- <option value="OR">OR</option> -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="p_parameter2">Parameter 2:</label>
                    <select name="p_parameter2" id="p_parameter2" class="form-control">
                        <option value="quantity_in_the_product">Quantity in the product</option>
                        <option value="quantity_in_each_product_of_category">Quantity in each product of Category</option>
                        <option value="order_total_amount">Order total amount</option>
                        <option value="order_total_weight">Order total weight</option>
                        <option value="order_total_number_of_items">Order total number of items</option>
                        <option value="order_total_number_of_total_different_items">Order total number of different items (lines)</option>
                        <option value="order_shipping_method">Order shipping method</option>
                    </select>
                </div>
                <div class="form-group" id="form_parameter3">
                    <label for="p_parameter3">Parameter 3:</label>
                    <!-- <input type="text"name="p_parameter3" placeholder="Enter Parameter 3" id="p_parameter3" > -->
                    <select name="p_parameter3" id="p_parameter3" class="form-control">
                        <?php
                            // Retrieve all products
                            $products = wc_get_products(array(
                                'status' => 'publish',
                                'limit' => -1,
                            ));
                            foreach ($products as $product) {
                                $title = $product->get_name();
                                $sku = $product->get_sku();
                                if (!empty($sku)) {
                                    echo '<option value="' . esc_attr($sku) . '">' . esc_html($title) . '</option>';
                                }
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="p_parameter4">Parameter 4:</label>
                    <select name="p_parameter4" id="p_parameter4" class="form-control">
                        <option value="=">=</option>
                        <option value=">=">>=</option>
                        <option value="<="><=</option>
                        <option value="<>"><></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="p_parameter5">Parameter 5:</label>
                    <input type="text" id="p_parameter5"name="p_parameter5" placeholder="Enter Parameter 5" >
                </div>
                <div class="form-group">
                    <label for="instruction">Instruction:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="p_instruction_option" id="p_instruction_text" value="text" checked>
                        <label class="form-check-label" for="P_instruction_text">Enter Text Instruction</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="p_instruction_option" id="p_instruction_file" value="file">
                        <label class="form-check-label" for="p_instruction_file">Upload File</label>
                    </div>
                </div>

                <div id="p_text_instruction" class="form-group">
                    <label for="p_text_instruction_input">Text Instruction:</label>
                    <input type="text" name="text_instruction" id="p_text_instruction_input" placeholder="Enter Instruction">
                </div>

                <div id="p_file_instruction" class="form-group" style="display: none;">
                    <label for="p_file_instruction_input">Upload Instruction File:</label>
                    <input type="file" name="file_instruction" id="p_file_instruction_input" accept=".png, .jpeg, .jpg">
                </div>
            </div>

            <div class="model_form_save">
                <button class="btn btn-primary" id="p_save_button">Save</button>
                <span class="spinner" style="margin-top: 11px;"></span>

            </div>
        </div>
    </div>
</div>

<!-- add modal preparation-instruction  -->
<div id="preparation-instruction" style="display: none; position: fixed; z-index: 999; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
    <div style="background-color: white; width: 50%; margin: 100px auto; padding: 20px;">
        <div class="models_header">
            <h2>Add New Preparation Instruction</h2>
            <div>
                <button id="close-modal-preparation-instruction" class="button"><?php _e('Close', 'wf-woocommerce-packing-list');?></button>
            </div>
        </div>

        <div id="preparation-instruction-form" class="model_form">
            <div class="preparation_forms_field">
                <div class="form-group">
                    <label for="parameter1">Parameter 1:</label>
                    <select name="parameter1" id="parameter1" class="form-control">
                        <option value="AND">AND</option>
                        <!-- <option value="OR">OR</option> -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="parameter2">Parameter 2:</label>
                    <select name="parameter2" id="parameter2" class="form-control">
                        <option value="quantity_in_the_product">Quantity in the product</option>
                        <option value="quantity_in_each_product_of_category">Quantity in each product of Category</option>
                        <option value="order_total_amount">Order total amount</option>
                        <option value="order_total_weight">Order total weight</option>
                        <option value="order_total_number_of_items">Order total number of items</option>
                        <option value="order_total_number_of_total_different_items">Order total number of different items (lines)</option>
                        <option value="order_shipping_method">Order shipping method</option>
                    </select>
                </div>
                <div class="form-group" id="p_parameter3_hide_show">
                    <label for="parameter3">Parameter 3:</label>
                    <!-- <input type="text"name="parameter3" placeholder="Enter Parameter 3" id="parameter3"> -->
                    <select name="parameter3" id="parameter3" class="form-control">
                        <?php
                            // Retrieve all products
                            $products = wc_get_products(array(
                                'status' => 'publish',
                                'limit' => -1,
                            ));
                            foreach ($products as $product) {
                                $title = $product->get_name();
                                $sku = $product->get_sku();
                                if (!empty($sku)) {
                                    echo '<option value="' . esc_attr($sku) . '">' . esc_html($title) . '</option>';
                                }
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="parameter4">Parameter 4:</label>
                    <select name="parameter4" id="parameter4" class="form-control">
                        <option value="=">=</option>
                        <option value=">=">>=</option>
                        <option value="<="><=</option>
                        <option value="<>"><></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="parameter5">Parameter 5:</label>
                    <input type="text"name="parameter5" id="parameter5" placeholder="Enter Parameter 5">
                </div>
                <div class="form-group">
                    <label for="instruction">Instruction:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="instruction_option" id="instruction_text" value="text" checked>
                        <label class="form-check-label" for="instruction_text">Enter Text Instruction</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="instruction_option" id="instruction_file" value="file">
                        <label class="form-check-label" for="instruction_file">Upload File</label>
                    </div>
                </div>
                <div class="form-group">
                    <div id="text_instruction" class="form-group">
                        <label for="p_parameter6">Text Instruction:</label>
                        <input type="text" name="text_instruction" id="text_instruction_input" placeholder="Enter Instruction">
                    </div>

                    <div id="file_instruction" class="form-group" style="display: none;">
                        <label for="file_instruction">Upload Instruction File:</label>
                        <input type="file" name="file_instruction" id="file_instruction_input"accept=".png, .jpeg, .jpg">
                    </div>
                </div>
            </div>

            <div class="model_form_save">
                <button class="btn btn-primary" id="preparation_save_button">Save</button>
                <span class="spinner" style="margin-top: 11px;"></span>
            </div>
        </div>
    </div>
</div>


<!-- add  modal add condition -->

<div id="add-condition-data" style="display: none; position: fixed; z-index: 999; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
    <div style="background-color: white; width: 50%; margin: 100px auto; padding: 20px;">
    <div class="models_header">
            <h2>Add New Condition</h2>
            <div>
                <button id="close-modal-condition" class="button"><?php _e('Close', 'wf-woocommerce-packing-list');?></button>
            </div>
        </div>

        <div id="condition-preparation-instruction-form" class="model_form">
            <div class="preparation_forms_field">
                <input type="hidden" id="instruction_id" name="instruction_id" value="">
                <div class="form-group">
                    <label for="parameter1">Parameter 1:</label>
                    <select name="parameter1" id="condition1" class="form-control">
                        <option value="AND">AND</option>
                        <option value="OR">OR</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="parameter2">Parameter 2:</label>
                    <select name="parameter2" id="condition2" class="form-control">
                        <option value="quantity_in_the_product">Quantity in the product</option>
                        <option value="quantity_in_each_product_of_category">Quantity in each product of Category</option>
                        <option value="order_total_amount">Order total amount</option>
                        <option value="order_total_weight">Order total weight</option>
                        <option value="order_total_number_of_items">Order total number of items</option>
                        <option value="order_total_number_of_total_different_items">Order total number of different items (lines)</option>
                        <option value="order_shipping_method">Order shipping method</option>
                    </select>
                </div>
                <div class="form-group" id="condition3_show_hide">
                    <label for="parameter3">Parameter 3:</label>
                    <!-- <input type="text"name="parameter3" placeholder="Enter Parameter 3" id="condition3"> -->
                    <select name="parameter3" id="condition3" class="form-control">
                        <?php
                            // Retrieve all products
                            $products = wc_get_products(array(
                                'status' => 'publish',
                                'limit' => -1,
                            ));
                            foreach ($products as $product) {
                                $title = $product->get_name();
                                $sku = $product->get_sku();
                                if (!empty($sku)) {
                                    echo '<option value="' . esc_attr($sku) . '">' . esc_html($title) . '</option>';
                                }
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="parameter4">Parameter 4:</label>
                    <select name="parameter4" id="condition4" class="form-control">
                        <option value="=">=</option>
                        <option value=">=">>=</option>
                        <option value="<="><=</option>
                        <option value="<>"><></option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="parameter5">Parameter 5:</label>
                    <input type="text"name="parameter5" id="condition5" placeholder="Enter Parameter 5">
                </div>
            </div>

            <div class="model_form_save">
                <button class="btn btn-primary" id="add_preparation_condition">Save</button>
                <span class="spinner" style="margin-top: 11px;"></span>
            </div>
        </div>
    </div>
</div>







