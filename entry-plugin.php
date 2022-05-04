<?php

/**
* Plugin Name: Custom Entry Plugin
* Description: This plugin contains all of my awesome custom functions.
* Author: indybytes
* Version: 0.1
*/


/**
 * activation hook.
 */
function my_plugin_activate() {
 global $wpdb;

if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

    $table_name = $wpdb->prefix . 'wpforms_import_data';
    $sql = "CREATE TABLE $table_name (
    id mediumint(9)  NOT NULL AUTO_INCREMENT,
    form_id varchar(50) NOT NULL,
    form_name varchar(50) NOT NULL,
    form_slug varchar(50) NOT NULL,
    form_entry_id varchar(6000) NULL,
    timestamp varchar(50) NOT NULL,
    PRIMARY KEY  (id),
    UNIQUE KEY (form_id)
    );";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
    add_option( 'Activated_Plugin', 'Plugin-Slug' );
}
  
}
register_activation_hook( __FILE__, 'my_plugin_activate' );


/**
 * Deactivation hook.
 */
function my_plugin_deactivate() {

     do_action( 'my_plugin_activate' );
}
register_activation_hook( __FILE__, 'my_plugin_deactivate' );



function my_admin_footer_function() {
    global $wpdb; 
    if(isset($_GET['page']) && $_GET['page']=="wpforms-entries"){
        $table_name = $wpdb->prefix . 'wpforms_import_data';
        $args = array(
            'post_type' => 'wpforms',
            'posts_per_page'=> -1
        );
        $query1 = new WP_Query( $args );
        // The Loop
        while ( $query1->have_posts() ) {
            $query1->the_post();
            $fetch_datas = $wpdb->get_results("SELECT * FROM ".$table_name." WHERE form_id = ".get_the_ID());
            if(!empty($fetch_datas)) {
                if(isset($fetch_datas[0]->form_id)){
                    ?>
                    <script type="text/javascript">
                        jQuery("#wpforms-dash-widget-forms-list-table tr").each(function( index ) {
                            var form_id = jQuery(this).attr("data-form-id");
                            if(form_id=="<?php echo get_the_ID() ?>"){
                                jQuery(this).find(">:first-child").append('<div class="row-actions"><span class="edit"><a href ="#" class ="remove_post" form-id= '+form_id+' >Delete post type</a></span></div>'  );
                            }
                        });
                    </script>
                    <?php
                }
            }else{
                ?>
                <script type="text/javascript">
                    jQuery("#wpforms-dash-widget-forms-list-table tr").each(function( index ) {
                        var form_id = jQuery(this).attr("data-form-id");
                        if(form_id=="<?php echo get_the_ID() ?>"){
                            jQuery(this).find(">:first-child").append('<div class="row-actions"><span class="edit"><a href ="#" class ="import-entry-data" form-id= '+form_id+' >Create post type</a></span></div>'  );
                        }
                    });
                </script>
                <?php
            }
        }
        wp_reset_postdata();
    }
    if(isset($_GET['page'] ) && $_GET['page']=="wpforms-entries" && $_GET['view']=="list"){
        global $wpdb;
        $form_id = $_GET['form_id'];
        $table_name = $wpdb->prefix . 'wpforms_import_data';
        $fetch_datas = $wpdb->get_results("SELECT * FROM $table_name WHERE form_id = $form_id");
        foreach ($fetch_datas as $key => $fetch_data) {
            $form_entry_ids = explode(",",$fetch_data->form_entry_id);
            // print_r($form_entry_ids);
            foreach ($form_entry_ids as $key => $form_entry_id) {
                // print_r($form_entry_id);
                ?>
                <script type="text/javascript">
                    jQuery("#the-list tr .indicators.column-indicators a.indicator-star.star").each(function( index ) {
                        var data_id =jQuery(this).attr("data-id");
                        if(data_id == "<?php echo $form_entry_id; ?>"){
                            jQuery(this).parent().append("<span class='post_data_imported'>Data imported</span>");
                        }
                    });
                </script>
                <?php
            }
        }

    }
    if(isset($_GET['page']) && $_GET['page']=="wpforms-entries"){
     ?>
        <script type="text/javascript">
        jQuery("#bulk-action-selector-top").append('<option value="importdata" class ="import-data">Import data</option>');
    </script>
        <?php
    } ?>
    <script type="text/javascript">

        // jQuery(".wpforms-dash-widget-form-title").parents("tr").each(function( index ) {
        //     var form_id = jQuery(this).attr("data-form-id");

        //     jQuery(this).find(".wpforms-dash-widget-form-title").parents("td").append('<a href ="#" class ="import-entry-data" form-id= '+form_id+' >Create post type</a>'  );
        // });
        jQuery(document ).on("click", ".import-entry-data",function(event) {
            event.preventDefault()
            var jQuery_this =jQuery(this);
            var form_id = jQuery(this).attr("form-id");
            var data_insert = "action=ajax_my_admin_footer_function&form-id=" +form_id ;
            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : "<?php echo admin_url('admin-ajax.php'); ?>",
                data : data_insert ,
                success: function(response) {
                    if(response.type == "success") {
                        jQuery_this.html('Delete post type');
                        jQuery_this.addClass('remove_post').removeClass("import-entry-data");
                        location.reload();
                    }
                }
            });
        });
          jQuery(document ).on("click", ".remove_post",function(event) {
            event.preventDefault()
            var jQuery_this =jQuery(this);
            var form_id = jQuery(this).attr("form-id");
            var data_delete = "action=ajax_remove_post_type&form-id=" +form_id ;
            jQuery.ajax({
                type : "post",
                dataType : "json",
                url : "<?php echo admin_url('admin-ajax.php'); ?>",
                data : data_delete ,
                success: function(response) {
                    if(response.type == "success") {
                        jQuery_this.html('Create post type');
                        jQuery_this.addClass('import-entry-data').removeClass("remove_post");
                        location.reload();
                    }
                }
            });
        });
        jQuery(document).on("click",".bulkactions #doaction",function(event){
            var importdata = jQuery("#bulk-action-selector-top").val();
            if(importdata == "importdata"){
                event.preventDefault();
                var form_id = jQuery("input[name='form_id']").val();
                var entry_id = [];
                jQuery('input[name="entry_id[]"]:checked').each(function(i){
                  entry_id[i] = jQuery(this).val();
                });
                var import_entry_data = "action=import_entry_data&form-id=" +form_id+"&entry_id="+entry_id ;
                jQuery.ajax({
                    type : "post",
                    dataType : "json",
                    url : "<?php echo admin_url('admin-ajax.php'); ?>",
                    data : import_entry_data ,
                    success: function(response) {
                        if(response.type == "success") {
                            if(response.already=="already"){
                                alert("Data already imported");
                            }else{
                                location.reload();
                            }
                        }

                    }
                });
            }else{
                return true;
            }
        });
    </script>
    <style type="text/css">
td.indicators.column-indicators span.post_data_imported:hover {
background-color: #156e15;
}
td.indicators.column-indicators span.post_data_imported {
padding: 5px 5px 7px;
color: #fff;
background-color: #178b17;
display: block;
font-weight: 500;
font-size: 11px;
}
#wpforms-entries-list .wp-list-table .column-indicators {
width: 85px;
text-align: center;
}
/* -------------------------graph in dashboard---------------------------- */

canvas#myChart {
    width: 100% !important;
    max-width: 500px;
    max-height: 500px;
    background-color: #fff;
    padding: 20px;
    border-radius: 5px;
    margin: 20px 0 30px;
    box-shadow: 0 0 10px #eee;
    float: left;
}

/* ----------------------table dashboard---------------------------- */

table.judges_table_voting,
.judges_voting_post table {
    float: left;
    margin: 20px 0px 30px 20px;
    border-spacing: 0;
    border-collapse: collapse;
    width: 100%;
    max-width: 400px;
}

table.judges_table_voting tr th,
table.judges_table_voting tr td,
.judges_voting_post table tr th,
.judges_voting_post table tr td {
    width: 50%;
    text-align: left;
    padding: 10px;
    border: 1px solid #dddd;
}

table.judges_table_voting tr th,
.judges_voting_post table tr th {
    background-color: #ebebeb;
}

table.judges_table_voting tr td,
.judges_voting_post table tr td {
    background-color: #ffffff;
}

table.judges_table_voting tr td a,
.judges_voting_post table tr td a {
    text-decoration: none;
    color: #000;
    font-weight: 500;
    text-transform: capitalize;
}

table.judges_table_voting tr:hover td,
.judges_voting_post table tr:hover td {
    background-color: #ebebeb0f;
}

.judges_voting_post table {
    max-width: 600px;
}

.judges_voting_post table tr th,
.judges_voting_post table tr td {
    width: auto;
    margin: 20px 0px 30px 0px;
}
table.judges_table_voting td a.restore_voting {
    padding: 5px 0;
    color: #af1a1a!important;
    display: inline-block;
    width: 110px;
    font-size: 12px;
}

table.judges_table_voting tr td a.restore_voting:hover {
    text-decoration: underline;
}
.wrap_voting_result .show_voting_result {
    display: inline-block;
}

.wrap_voting_result .show_voting_result table.judges_table_voting {
    margin-left: 0;
}

.wrap_voting_result nav.sw-pagination .page-numbers {
    font-size: 14px;
    color: #000;
    background-color: #fff;
    border-radius: 50px;
    padding: 6px 11px;
    min-width: 30px;
    height: 30px;
    display: inline-block;
    box-sizing: border-box;
    text-decoration: none;
    margin: 0 0px;
    font-weight: 600;
    box-shadow: 0 0 5px #ddd;
}

.wrap_voting_result nav.sw-pagination .page-numbers.current {
    background-color: #bdbdbd;
}
    </style>
    <?php
}
add_action('admin_footer', 'my_admin_footer_function');
add_action("wp_ajax_import_entry_data", "import_entry_data");
add_action("wp_ajax_nopriv_import_entry_data", "import_entry_data");
function import_entry_data(){
    $form_id = $_REQUEST['form-id'];
    $entry_id = $_REQUEST['entry_id'];
    global $wpdb;
    $data_already = ""; 
    $table_name = $wpdb->prefix . 'wpforms_import_data';
    $fetch_datas = $wpdb->get_results("SELECT * FROM $table_name WHERE form_id = $form_id");
    foreach ($fetch_datas as $key => $fetch_data) {
        $wpforms_import_data_id = $fetch_data->id;
        $entry_id_array = explode(",",$entry_id);
        $fetch_data_form_entry_id = explode(",",$fetch_data->form_entry_id);
        $update_enty_ids = $fetch_data->form_entry_id.",".$entry_id;
        $fetch_data_form_entry_id_acunt = count($entry_id_array) + count($fetch_data_form_entry_id);
        $array_unique_count = count(array_unique(array_merge($entry_id_array, $fetch_data_form_entry_id)));
        if($array_unique_count < $fetch_data_form_entry_id_acunt){
          $data_already = "already";
            $result['already'] = "already";
        }
        if(!empty($fetch_data)) {
            $form_slug = $fetch_data->form_slug;
        }
    }
    if($data_already == ""){
        $table_name_wpforms_entries = $wpdb->prefix . 'wpforms_entries';
        $fetch_datas = $wpdb->get_results("SELECT * FROM $table_name_wpforms_entries WHERE entry_id IN ($entry_id) AND form_id= $form_id");
        foreach ($fetch_datas as $key => $fetch_data) {
            // print_r($fetch_data);
            $my_post = array(
                'post_title'    => $fetch_data->entry_id,
                'post_status'   => 'publish',
                'post_type' => $form_slug,
                'post_author'   => 1,
            );
            $i=1;
            $post_id =wp_insert_post( $my_post );
            $fetch_data_fields = json_decode($fetch_data->fields);
            foreach ($fetch_data_fields as $key => $field) {
                if(!empty($post_id)){
                    if($i==1){
                        wp_update_post(array('ID'=>$post_id,'post_title'=>$field->value));
                    }
                    update_post_meta($post_id,str_replace(")","",str_replace("(","",str_replace(" ","_",strtolower($field->name."_".$field->id)))),$field->value);
                    update_post_meta($post_id,'entry_id',$fetch_data->entry_id);
                }
                $i++;
            }
        }
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET form_entry_id='$update_enty_ids' WHERE id = $wpforms_import_data_id"));
    }

    $result['type'] = "success";
    $result = json_encode($result);
    echo $result;   
    die();
}
function role_update_custom_roles() {
    if ( get_option( 'custom_roles_judge_version' ) < 1 ) {
        add_role( 'judge', 'Judge', array( 'read' => true, 'level_0' => true ) );
        update_option( 'custom_roles_judge_version', 1 );
    }
}
add_action( 'init', 'role_update_custom_roles' );
add_action("wp_ajax_ajax_remove_post_type", "ajax_remove_post_type");
add_action("wp_ajax_nopriv_ajax_remove_post_type", "ajax_remove_post_type");
function ajax_remove_post_type(){
    global $wpdb; 
    $table_name = $wpdb->prefix . 'wpforms_import_data'; 
    $delete_post = $wpdb->delete( $table_name, array( 'form_id' => $_REQUEST['form-id'] ) );
    if($delete_post){
        $result['type'] = "success";
        $result = json_encode($result);
        echo $result;
    }
    die();   
}
add_action("wp_ajax_ajax_my_admin_footer_function", "ajax_my_admin_footer_function");
add_action("wp_ajax_nopriv_ajax_my_admin_footer_function", "ajax_my_admin_footer_function");
function ajax_my_admin_footer_function(){
    global $wpdb; 
    $table_name = $wpdb->prefix . 'wpforms_import_data';  
    $form_id =  $_REQUEST['form-id'];
    // $result['slug'] = $slug = get_post_field( 'post_name', $_REQUEST['form-id'] );
    // $result['form_name'] = $form_name = get_the_title($_REQUEST['form-id']);
    $form_name = get_the_title( $_REQUEST['form-id']) ;
    $form_slug = get_post_field( 'post_name',  $_REQUEST['form-id']) ;
    $time_stamp =time();  
    $format = array('%s','%d');
    $insert_data = $wpdb->insert('wp_wpforms_import_data', array( 'id' =>'NULL', 'form_id' => $form_id, 'form_name' => $form_name, 'form_slug' => $form_slug, 'timestamp' => $time_stamp)); 
    if ( is_wp_error($insert_data ) ) {
    $error_string = $insert_data->get_error_message();
    echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';
}
    if($wpdb->insert_id){
        $result['type'] = "success";
        $result = json_encode($result);
        echo $result;
    }
    die();
}


// code for top level menu 

// add_action( 'admin_menu', 'wporg_options_page' );
//add_action('admin_menu', 'my_menu_pages');

// code for delete level menu.
function books_ref_page_callback(){
    
    $post_id = end(explode("-",$_REQUEST['page']));
    $exampleListTable = new Example_List_Table();
    $exampleListTable->prepare_items();
    $content_post = get_post($post_id);
    $content = $content_post->post_content; 
    //$new_show = $content_post->id ;
    // echo "<pre>";
   // print_r(json_decode($content)->fields);
    
    $content_fields = json_decode($content)->fields;
    foreach($content_fields as  $content_field){

       // print_r($content_field);
        if($content_field->type=="textarea"){
            echo $content_field->label;
            echo "<textarea></textarea><br>";
        }elseif ($content_field->type=="file-upload") {
            echo $content_field->label;
            echo "<input type =file><br>";
        }elseif ($content_field->type=="file-upload") {
            echo $content_field->label;
            echo "<select>
            <option></option>
            <option></option>
            <option></option>
            <option></option>
            </select><br>";
        }elseif($content_field->type=="radio"){
            echo $content_field->label."<br>";
            $content_radios = $content_field->choices;
            foreach($content_radios as  $content_radio){
            echo $content_radio->label;
            echo "<input type =".$content_field->type."><br>";
            }
        }else{
            echo $content_field->label;
            echo "<input type =".$content_field->type."><br>";
        }
    }
     // echo "</pre>";
}

function wporg_custom_post_type() {
    global $wpdb; 
    $table_name = $wpdb->prefix . 'wpforms_import_data';
    $fetch_datas = $wpdb->get_results( "SELECT * FROM $table_name");
    foreach ($fetch_datas as $key => $fetch_data) {
        if(!empty($fetch_data)) {
            $post_type = $fetch_data->form_name;
            $post_slug = $fetch_data->form_slug;
                $labels = [
                    "name" => __( $post_type, "uncode" ),
                    "singular_name" => __( $post_slug, "uncode" ),
                ];

                $args = [
                    "label" => __( $post_type, "uncode" ),
                    "labels" => $labels,
                    "description" => "",
                    "public" => true,
                    "publicly_queryable" => true,
                    "show_ui" => true,
                    "show_in_rest" => true,
                    "rest_base" => "",
                    "rest_controller_class" => "WP_REST_Posts_Controller",
                    "has_archive" => false,
                    "show_in_menu" => true,
                    "show_in_nav_menus" => true,
                    "delete_with_user" => false,
                    "exclude_from_search" => false,
                    "capability_type" => "post",
                    "map_meta_cap" => true,
                    "hierarchical" => false,
                    "rewrite" => [ "slug" => "test", "with_front" => true ],
                    "query_var" => true,
                    "supports" => [ "title" ],
                    "show_in_graphql" => false,
                ];

                register_post_type( $post_slug, $args );
        }
    }

}
add_action('init', 'wporg_custom_post_type');

function wporg_add_custom_box() {
    global $wpdb; 
     $table_name = $wpdb->prefix . 'wpforms_import_data';
    $fetch_datas = $wpdb->get_results( "SELECT * FROM $table_name");
    foreach ($fetch_datas as $key => $fetch_data) {
        // print_r($fetch_data);
        if(!empty($fetch_data)) {
            // $screens = [ $fetch_data->form_name, $fetch_data->form_slug ];
            // print_r($fetch_data->form_slug);
            // foreach ( $screens as $screen ) {
                // add_meta_box( 'meta-box-id', __( 'My Meta Box', 'textdomain' ), 'wporg_custom_box_html', "$fetch_data->form_slug" );
                add_meta_box('wporg_box_id', 'Custom Meta Box Title','wporg_custom_box_html',$fetch_data->form_slug);
            // }
        }
    }
}
add_action( 'add_meta_boxes', 'wporg_add_custom_box' );
function wporg_custom_box_html( $post ) {
    wp_enqueue_script('jquery');
// This will enqueue the Media Uploader script
wp_enqueue_media();
    ?>
        <!-- <div> -->


<!-- </div> -->
<script type="text/javascript">
jQuery(document).ready(function($){
    $('#upload-btn').click(function(e) {
        e.preventDefault();
        var image = wp.media({ 
            title: 'Upload Image',
            // mutiple: true if you want to upload multiple files at once
            multiple: true
        }).open()
        .on('select', function(e){
            // This will return the selected image from the Media Uploader, the result is an object
            var uploaded_image = image.state().get('selection').first();
            // We convert uploaded_image to a JSON object to make accessing it easier
            // Output to the console uploaded_image
            console.log(image.state().get('selection').toJSON());
            var img_array = image.state().get('selection').toJSON();
            var img_url="";
            img_url_html = "";
            $.each(img_array,function( index, value ) {
              img_url_html += "<p><img src='"+value.url+"'> <a href=''class='remove_this_img' url='"+value.url+"'>remove img</a></p>"; 
              img_url += value.url+" ";
            });
            console.log(img_url);
            var image_url= $('#image_url').val();
            $('.img_url_change').html(img_url_html);
            // $('.old_img').remove();
            // var image_url = uploaded_image.toJSON().url;
            // Let's assign the url value to the input field
            
            $('#image_url').val(image_url+" "+img_url);
        });
        
    });
    function removeClass(arr) {
    var what, a = arguments, L = a.length, ax;
    while (L > 1 && arr.length) {
        what = a[--L];
        while ((ax= arr.indexOf(what)) !== -1) {
            arr.splice(ax, 1);
        }
    }
    return arr;
}
$( document ).on( "click", ".remove_this_img", function(event) {
        event.preventDefault();
        $(this).parent().remove();
        var p_tag_html = jQuery(this).attr('url');
        var image_url= $('#image_url').val();
        // var myArr = image_url.split("https://");
        // console.log(myArr);
        // $.each(myArr, function( index, value ) {
        //     // alert(value);
        //     if(p_tag_html.indexOf(value) > -1){
        //         myArr.splice(index, 1);
        //     }
        // });
        // removeClass(myArr,p_tag_html);
        
        // var replase = myArr.toString();
        //myArr.splice($.inArray(p_tag_html, myArr),1)
        var myurl_data = "";
        $('.remove_this_img').each(function( index ) {
            myurl_data += $( this ).attr('url');
        });
        console.log(myurl_data);
        $('#image_url').val(myurl_data);
    });

});
</script>
    <div class="hcf_box">
        <style>
            .wpforms-submit-container {
                display: none;
            }
            /*.hcf_box{
                display: grid;
                grid-template-columns: max-content 1fr;
                grid-row-gap: 10px;
                grid-column-gap: 20px;
            }
            .hcf_field{
                display: contents;
            }*/
            .hcf_field {
                display: grid;
                width: 100%;
                font-size: 14px;
            }
            .hcf_field label {
                display: block;
                margin-bottom: 5px;
                line-height: 1.2;
            }
            .hcf_box h2 {
                padding-left: 0 !important;
                font-weight: bold !important;
                font-size: 16px !important;
            }
            p.hcf_box{
                font-size: 13px !important;
                line-height: 3.5 !important;
                margin: 1em 0 !important;
            }
            .hcf_box textarea {
                min-height: 140px;
            }
            .hcf_field input {
                font-size: 15px;
                line-height: 1.4;
            }

.image p.old_img,
.image p.img_url_change p {
    text-align: center;
    width: 100px;
    margin-right: 15px;
    display: inline-block;
}

.image p.old_img img,
.image p.img_url_change p img {
    width: 100px;
    display: block;
    height: 100px;
    object-fit: contain;
    background-color: #fff;
    border: 1px solid #dddd;
}

.image p a.remove_this_img,
.image p.img_url_change p a {
    margin-top: 5px;
    display: block;
    text-transform: capitalize;
    color: #a31a1a;
}
        </style>
    <?php
    global $wpdb; 
    $table_name = $wpdb->prefix . 'wpforms_import_data';
    $fetch_datas = $wpdb->get_results( "SELECT * FROM $table_name WHERE form_slug ='".get_post_type()."'");
    $form_id = $fetch_datas[0]->form_id;
    $content_post = get_post($form_id);
    $content = $content_post->post_content; 
    $form_field = json_decode($content);
    $fields = $form_field->fields ;
    // echo "<pre>";
    // print_r($form_field);
    // echo "</pre>";
    foreach($fields as  $field){
        $post_meta_data = get_post_meta( get_the_ID(),str_replace(")","",str_replace("(","",str_replace(" ","_",strtolower($field->label."_".$field->id)))), true);
        if($field->type=="textarea"){
           echo '<p class="meta-options hcf_field">
            <label>'.$field->label.'</label>';
           echo "<textarea name='".str_replace(")","",str_replace("(","",str_replace(" ","_",strtolower($field->label."_".$field->id))))."'>".$post_meta_data."</textarea></p>";
        }elseif ($field->type=="file-upload") {
           echo '<p class="meta-options hcf_field">
            <label>'.$field->label.'</label><div class="image">';
            // echo $post_meta_data;
            $post_meta_datas = explode("http",$post_meta_data);
            if(!empty($post_meta_datas)){
                $img_url = "";
                echo "<p class='img_url_change'>";
                foreach ($post_meta_datas as $key => $post_meta_data) {
                    if($post_meta_data!=""){
                        $check_pdf = count(explode(".pdf",$post_meta_data));
                        if($check_pdf>1){
                            // if(!empty($post_meta_data)){
                                echo '<p class="old_img"><a target="_blank" href="http'.$post_meta_data.'"  class="pdf_url_with_icon"><i class="fa fa-file-pdf-o" style="font-size:36px"></i></a> <a href="" class="remove_this_img" url="http'.$post_meta_data .'"">remove img</a></p>'; 
                            // }
                        }else{
                            // if(!empty($post_meta_data)){
                               echo "<p class='old_img'><img src='http".$post_meta_data."'> <a href=''class='remove_this_img' url='http".$post_meta_data ."'>remove img</a></p>"; 
                            // }
                        }
                        $img_url .= "http".$post_meta_data." ";
                        // echo  "<p class='old_img'><img src='http".$post_meta_data ."'> <a href=''class='remove_this_img' url='http".$post_meta_data ."'>remove img</a></p>";
                    }
                }
                echo "</p>";
                echo '<input type="hidden" name="'.str_replace(")","",str_replace("(","",str_replace(" ","_",strtolower($field->label."_".$field->id)))).'" id="image_url" class="regular-text" value="'.$img_url.'">
    <input type="button" name="upload-btn" id="upload-btn" class="button-secondary" value="Upload Image">';
                echo '<span class="tooltipster-content">Please use the default <a href="'.home_url().'/wp-admin/upload.php">WordPress Media</a> interface to remove this file.</span></div>';
            }else{
                echo "<input type='file' name='".str_replace(")","",str_replace("(","",str_replace(" ","_",strtolower($field->label."_".$field->id))))."' ></p>";
            }

        }elseif ($field->type=="select") {
           echo '<p class="meta-options hcf_field">
            <label>'.$field->label.'</label>';
           echo '<select id="wporg_field" class="postbox">
           <option></option>
           <option></option>
           <option></option>
           <option></option>
           </select></p>';
        }elseif($field->type=="radio"){
           echo '<h2>'.$field->label.'</h2></p>';
            $content_radios = $field->choices;
               foreach($content_radios as  $content_radio){
                    $checked = "";
                    if($content_radio->label == $post_meta_data){ 
                        $checked ='checked';
                    }
                    // echo $post_meta_data;
                    echo '<p class="meta-options hcf_field"><label>'.$content_radio->label.'</label>';
                    echo "<input type ='radio' name='".str_replace(")","",str_replace("(","",str_replace(" ","_",strtolower($field->label."_".$field->id))))."' value='".$content_radio->label."' $checked></p>";
               }
        }elseif($field->type=="checkbox"){
           echo '<h2>'.$field->label.'</h2></p>';
            $content_radios = $field->choices;
               //var_dump( $post_meta_data );
               foreach($content_radios as  $content_radio){
                    $checked = '';
                    if(is_array($post_meta_data)){
                        if( in_array( $content_radio->label, $post_meta_data ) ){ $checked ='checked';}
                    }else{
                        if( strpos($post_meta_data, $content_radio->label) || $content_radio->label == $post_meta_data ){ $checked ='checked';}
                    }
                    echo '<p class="meta-options hcf_field"><label>'.$content_radio->label.'</label>';
                    //echo "<input type ='checkbox' name='wpforms_fields_".$field->id."[]' value='".$content_radio->label."' $checked></p>";
                    echo "<input type ='checkbox' name='".str_replace(")","",str_replace("(","",str_replace(" ","_",strtolower($field->label."_".$field->id))))."[]' value='".$content_radio->label."' $checked></p>";
               }
        }else{
            if($field->type != 'html'){
               echo '<p class="meta-options hcf_field">
                <label>'.$field->label.'</label>';
               echo "<input type =".$field->type." name='".str_replace(")","",str_replace("(","",str_replace(" ","_",strtolower($field->label."_".$field->id))))."' value='".$post_meta_data."'></p>";
           }
        }
    }
    ?>
        
    </div>
  
    <?php
   
}
function wporg_save_postdata( $post_id ) {
    global $wpdb; 
    $table_name = $wpdb->prefix . 'wpforms_import_data';
    $fetch_datas = $wpdb->get_results( "SELECT * FROM $table_name WHERE form_slug ='".get_post_type($post_id)."'");
    $form_id = $fetch_datas[0]->form_id;
    $content_post = get_post($form_id);
    $content = $content_post->post_content; 
    $form_field = json_decode($content);
    $fields = $form_field->fields ;
    foreach($fields as  $field){
        // print_r($_POST);
        update_post_meta($post_id,str_replace(")","",str_replace("(","",str_replace(" ","_",strtolower($field->label."_".$field->id)))),$_POST[str_replace(")","",str_replace("(","",str_replace(" ","_",strtolower($field->label."_".$field->id))))]);
    }
}
add_action( 'save_post', 'wporg_save_postdata' );



// shortcode for wpforms-entries

function shortcodes_init(){
 add_shortcode( 'wpforms_entry', 'wp_form_entries' );
}
add_action('init', 'shortcodes_init');


function wp_form_entries(){
    ob_start();
    if(!is_user_logged_in()){
        wp_redirect(home_url());
        die();
    }
    $filter = 'filter';
    $level = "";
    $category = "";
    $request_level = "";
    $request_category = "";
    $meta_query = array();
    if(isset($_REQUEST['level'])){
        $level = "&level=".$_REQUEST['level'];
        $request_level = $_REQUEST['level'];
        $meta_query[] = array( 'key' => 'multiple_choice_33' ,'value' =>$_REQUEST['level'], 'compare' => 'LIKE');
    }
    if(isset($_REQUEST['category'])){
        $category = "&category=".$_REQUEST['category'];
        $request_category = $_REQUEST['category'];
        $meta_query[] = array( 'key' => 'category:_21' ,'value' =>$_REQUEST['category'], 'compare' => 'LIKE');
    }
    $user_id = get_current_user_id();
    if(isset($_REQUEST['status'])){
        $status = "&status=".$_REQUEST['status'];
        $request_status = $_REQUEST['status'];
        
        if($request_status=='done') {
            $meta_query[] = array( 'key' => 'mauro_porcini_done_'.$user_id ,'value' =>'done', 'compare' => '=');
        }else{
            $meta_query[] = array( 'key' => 'mauro_porcini_done_'.$user_id ,'value' =>'done', 'compare' => 'NOT EXISTS');
        }
    }else{
        $request_status = 'not-done';
            $meta_query[] = array( 'key' => 'mauro_porcini_done_'.$user_id ,'value' =>'done', 'compare' => 'NOT EXISTS');
        }
    // The Query
    // $page = ( get_query_var( 'meta_query' ) ) ? get_query_var( 'meta_query' ) : 1;
    $paged = ( get_query_var( 'paged' ) ) ? absint( get_query_var( 'paged' ) ) : 1;
    // print_r(array( 'post_type' => 'submit-your-recipe','post_status'   => 'publish','orderby' => 'ID','order'   => 'DESC', 'posts_per_page' => 5,'paged' => $paged, 'meta_query' => $meta_query));
    $the_query = new WP_Query(array( 'post_type' => 'submit-your-recipe','post_status'   => 'publish','orderby' => 'ID','order'   => 'DESC', 'posts_per_page' => 5,'paged' => $paged, 'meta_query' => $meta_query));
     // echo"<pre>";
     // print_r($the_query);
     // echo "</pre>";
    // The Loop
    echo "<div class='show_wpforms_data' id='showentries'>";
    ?>
    <div class="designer_data_filter">
        <h2>FILTER</h2>
        <ul>
            <li class="titleBlack">
                <ul class="sub_filter_list">
                    <li class='<?php if (!isset($_REQUEST['filter'])) echo 'active'; ?>'>
                        <a href="<?php echo home_url(); ?>/design-challenge/voting-portal/">See All</a>
                    </li>
                </ul>
            </li>
            <li>
                <span>Category</span>
                
                <ul class="sub_filter_list">
                    <li class='<?php if ($request_category == 'cooking') echo 'active'; ?>'><a href="<?php echo home_url(); ?>/design-challenge/voting-portal/?filter=<?php echo $filter.$level.$status; ?>&category=cooking#showentries">Cooking</a></li>
                    <li class='<?php if ($request_category == 'eating') echo 'active'; ?>'><a href="<?php echo home_url(); ?>/design-challenge/voting-portal/?filter=<?php echo $filter.$level.$status; ?>&category=eating#showentries">Eating</a></li>
                    <li class='<?php if ($request_category == 'sitting') echo 'active'; ?>'><a href="<?php echo home_url(); ?>/design-challenge/voting-portal/?filter=<?php echo $filter.$level.$status; ?>&category=sitting#showentries">Sitting</a></li>
                </ul>
            </li>
            <li>
                <span>Level</span>
                <ul class="sub_filter_list">
                    <li class='<?php if ($request_level == 'professional') echo 'active'; ?>'><a href="<?php echo home_url(); ?>/design-challenge/voting-portal/?filter=<?php echo $filter.$category.$status; ?>&level=professional#showentries">Professional</a></li>
                    <li class='<?php if ($request_level == 'student') echo 'active'; ?>'><a href="<?php echo home_url(); ?>/design-challenge/voting-portal/?filter=<?php echo $filter.$category.$status; ?>&level=student#showentries">Student</a></li>
                </ul>
            </li>
            <li>
                <span>Status</span>
                <ul class="sub_filter_list">
                    <li class='<?php if ($request_status == 'done') echo 'active'; ?>'><a href="<?php echo home_url(); ?>/design-challenge/voting-portal/?filter=<?php echo $filter.$category.$level; ?>&status=done#showentries">Done</a></li>
                    <li class='<?php if ($request_status == 'not-done') echo 'active'; ?>'><a href="<?php echo home_url(); ?>/design-challenge/voting-portal/?filter=<?php echo $filter.$category.$level; ?>&status=not-done#showentries">Not Done</a></li>
                </ul>
            </li>
        </ul>
    </div>
    <div class='designer_data'> 
        <h2>ENTRIES</h2>
        <div class="designer_main_data">
    <?php
    if ( $the_query->have_posts() ) {
        while ( $the_query->have_posts() ) {
            $the_query->the_post(); 
            $default_done = get_post_meta( get_the_ID(),'mauro_porcini_done_'.$user_id, true);
            //print_r($default_done)
            $class = 'not-done';
            if($default_done == 'done') $class = 'done' ; ?>
            <div class ="main <?php echo $class ; ?>">
                
                    <div class ="Level"> 
                        <label>Level<label>
                        <p><?php 
                           echo get_post_meta( get_the_ID(),'multiple_choice_33', true);
                          ?>
                        </p> 
                    </div>
                    <div class ="designer"> 
                        <label>Designer</label>
                        <p><?php 
                          echo get_the_title();
                          echo " ".get_post_meta( get_the_ID(),'last_name_3', true);
                        ?></p>
                    </div>
                    <div class ="Product-Type"> 
                        <label>Product Type<label>
                        <p><?php 
                          echo get_post_meta( get_the_ID(),'product_type_25', true);
                        ?></p> 
                    </div>
                <a class="click_link" href="<?php echo home_url();?>/entry/?entry_id=<?php echo get_the_ID(); ?>"></a>
                <a class="icone_single_link" href="<?php echo home_url();?>/entry/?entry_id=<?php echo get_the_ID(); ?>">Designer single page</a>
            </div><?php
        }
        
    } else {
       echo "<p>NO Data Found</p>";
    }

    echo "</div>";
    if(isset($_REQUEST['filter'])){
        $filter = 'yes';
    }
   
    echo "<nav class=\"sw-pagination\">";
    $big = 999999999; // need an unlikely integer
    echo paginate_links( array(
        'base' => home_url()."/design-challenge/voting-portal" . '%_%'.'#showentries',
        'format' => '/page/%#%',
        'current' => max( 1, get_query_var('paged') ),
        'total' => $the_query->max_num_pages
    ) );
    echo "</nav>";
    echo "</div>";
    echo "</div>";

    /* Restore original Post Data */
    wp_reset_postdata();
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($){
        $('filter').click(function() {
          $('#row-unique-8').show();
        });
    });
    jQuery(document ).on("click", "#load_more",function(event) {
        event.preventDefault() 
        var show_count = jQuery(this).attr("show_count");
        var page = jQuery(this).attr("page");
        var category = jQuery(this).attr("category");
        var level = jQuery(this).attr("level");
        var status = jQuery(this).attr("status");
        var show_total = jQuery(this).attr("show_total");
        var form_data = "action=get_wpforms_entry_fields&show_total="+show_total+"&show_count="+show_count+"&page="+page+"&category="+category+"&level="+level+"&status="+status;
        jQuery.ajax({
            type : "post",
            dataType : "json",
            url : "<?php echo admin_url('admin-ajax.php'); ?>",
            data : form_data,
            success: function(response) {
                if(response.type == "success") {
                    jQuery(".designer_main_data").append(response.html);
                    jQuery("."+response.load_more_section).remove();
                    jQuery("#load_more").attr("show_total",response.show_total);
                    jQuery("#load_more").attr("page",response.paged);
                    // alert("Data save");
                }
             }
        });
    }); 
</script>
    <?php
    $html = ob_get_clean();
    return $html;
}


add_action("wp_ajax_get_wpforms_entry_fields", "get_wpforms_entry_fields");
add_action("wp_ajax_nopriv_get_wpforms_entry_fields", "get_wpforms_entry_fields");
function get_wpforms_entry_fields(){
        $show_total = $_REQUEST['show_total']+$_REQUEST['show_count'];
        $show_count = $_REQUEST['show_count'];
        $paged = $_REQUEST['page']+1;
         global $wpdb;
        ob_start();
        $meta_query = array();
        if(isset($_REQUEST['level']) && $_REQUEST['level']!=""){
            // echo $_REQUEST['level'];
            $level = "&level=".$_REQUEST['level'];
            $meta_query[] = array( 'key' => 'multiple_choice_33' ,'value' =>$_REQUEST['level'], 'compare' => 'LIKE');
        }
        if(isset($_REQUEST['category']) && $_REQUEST['category']!=""){
            $category = "&category=".$_REQUEST['category'];
             $meta_query[] = array( 'key' => 'category:_21' ,'value' =>$_REQUEST['category'], 'compare' => 'LIKE');
        }
        if(isset($_REQUEST['status']) && $_REQUEST['status']!=""){
            $status = "&status=".$_REQUEST['status'];
            $request_status = $_REQUEST['status'];
            $user_id = get_current_user_id();
            if($request_status=='done'){
                $meta_query[] = array( 'key' => 'mauro_porcini_done_'.$user_id ,'value' =>'done', 'compare' => '=');
            }else{
                $meta_query[] = array( 'key' => 'mauro_porcini_done_'.$user_id ,'value' =>'done', 'compare' => 'NOT EXISTS');
            }
        }else{
            $meta_query[] = "'relation' => 'OR',".array( 'key' => 'mauro_porcini_done_'.$user_id ,'value' =>'done', 'compare' => '=');
            $meta_query[] = array( 'key' => 'mauro_porcini_done_'.$user_id ,'value' =>'done', 'compare' => 'NOT EXISTS');
        }
        // print_r($meta_query);
        $the_query = new WP_Query(array( 'post_type' => 'submit-your-recipe','post_status'   => 'publish', 'posts_per_page' => 5,'paged'=> $paged, 'meta_query' => $meta_query) );
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post(); ?>
                <div class ="main">
                  <div class ="designer"> 
                    <label>Designer</label>
                    <p><?php 
                      echo get_the_title();
                      echo " ".get_post_meta( get_the_ID(),'last_name_3', true);   
                    ?></p>
                  </div>
                  <div class ="Level"> 
                    <label>Level<label>
                    <p><?php 
                       echo get_post_meta( get_the_ID(),'multiple_choice_33', true);
                      ?>
                    </p> 
                  </div>
                  <div class ="Project-Name"> 
                    <label>Project Name<label>
                    <p><?php 
                      echo get_post_meta( get_the_ID(),'project_name_copy_30', true);
                    ?></p> 
                  </div>
                  <div class ="Product-Type"> 
                    <label>Product Type<label>
                    <p><?php 
                      echo get_post_meta( get_the_ID(),'product_type_25', true);
                    ?></p> 
                  </div>
                  <a href="<?php echo home_url();?>/entry/?entry_id=<?php echo get_the_ID(); ?>">Designer single page</a>
                </div><?php
            }
        }
        $result['load_more_section_count'] = $the_query->found_posts;
        if($the_query->found_posts<=$show_total  ){
            $result['load_more_section'] = "load_more_section";
        }
        /* Restore original Post Data */
        wp_reset_postdata();
        $html = ob_get_clean();
        // print_r($meta_query);
        $result['type'] = "success";
        $result['show_total'] = $show_total;
        $result['paged'] = $paged;
        $result['html'] = $html;
        $result = json_encode($result);
        echo $result;
    die();
}


// shortcode for single entry

function shortcodes_init_single_entry(){
 add_shortcode( 'wpforms_single_entry', 'wp_form_single_entry' );
}
add_action('init', 'shortcodes_init_single_entry');

function wp_form_single_entry($atts){
  extract(shortcode_atts(array(
        'post_type'  => 'submit-your-recipe',
        'form_id'   => '51428',
      ), $atts));
      ob_start();
        if(!is_user_logged_in()){
            wp_redirect(home_url());
            die();
        }
        $entry_id = $_GET['entry_id'];
        // The Query
        $the_previous_post = new WP_Query( array( 'post_type' =>'submit-your-recipe','post_status'   => 'publish', 'posts_per_page' => -1,'orderby' => 'ID','order'   => 'DESC')); 
        $i = 1;
        $previous_empty_id = "";
        if ( $the_previous_post->have_posts() ) {
            while ( $the_previous_post->have_posts() ) {
                $the_previous_post->the_post();
                if(get_the_ID()<$entry_id){
                    if($i==1){
                        $previous_empty_id =  get_the_ID();
                        $i++;
                    }
                }
            }
        }
        wp_reset_postdata();
         $the_next_post = new WP_Query( array( 'post_type' =>'submit-your-recipe','post_status'   => 'publish', 'posts_per_page' => -1,'orderby' => 'ID','order'   => 'ASC')); 
        $i = 1;
        $next_empty_id = "";
        if ( $the_next_post->have_posts() ) {
            while ( $the_next_post->have_posts() ) {
                $the_next_post->the_post();
                if(get_the_ID()>$entry_id){
                    if($i==1){
                        $next_empty_id =  get_the_ID();
                        $i++;
                    }
                }
            }
        }
        wp_reset_postdata();
        $the_query = new WP_Query( array( 'post_type' =>'submit-your-recipe','post_status'   => 'publish', 'posts_per_page' => 1,'p' => $entry_id )); 
        // The Loop
        // print_r($the_query);
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();

                ?>
                <div class="form_single_data designer_level">
                    <div class="designer">
                      <label>Designer</label>
                      <p><?php 
                        $last_name_3 = get_post_meta( get_the_ID(),'last_name_3', true);
                       echo get_the_title();
                       if (!empty($last_name_3)) {
                            echo " ".$last_name_3;
                        }
                       ?></p>
                    </div>
                    <div class="level">
                      <label>Level</label>
                      <p><?php 
                        $multiple_choice_33 = get_post_meta( get_the_ID(),'multiple_choice_33', true);
                        if (!empty($multiple_choice_33)) {
                            echo $multiple_choice_33;
                        }
                       ?>
                      </p>
                    </div>
                    <div class="category">
                        <label>Category</label>
                        <p><?php 
                        $category_21 = get_post_meta( get_the_ID(),'category:_21', true);
                        if (!empty($category_21)) {
                            echo explode(" ",$category_21)[0];
                        }
                       ?>
                        </p>
                      </div>
                      <div class="product">
                        <label>Product Type</label>
                        <p><?php 
                        $product_type_25 = get_post_meta( get_the_ID(),'product_type_25', true);
                        if (!empty($product_type_25)) {
                            echo $product_type_25;
                        }
                       ?>
                        </p>
                    </div>
                </div>
                <div class="form_single_entry">
                    <div class="form_single_entry_data">
                      <div class="form_single_data product_description_theme">
                        <div class="product_description">
                          <label>Product Description</label>
                          <p><?php 
                           $project_description_16 = get_post_meta( get_the_ID(),'project_description_16', true);
                           if (!empty($project_description_16)) {
                            echo $project_description_16;
                            }
                           ?>
                          </p>
                        </div>
                        <div class="theme">
                          <label>Theme</label>
                          <p><?php 
                          $theme_31 = get_post_meta( get_the_ID(),'theme_31', true);
                          if (!empty($theme_31)) {
                            echo $theme_31;
                          }
                           ?>
                          </p>
                        </div>
                      </div>
                      <div class="form_single_data product_images">
                        <label>Product Images</label>
                        
                        <?php
                            $images = explode("http",get_post_meta( get_the_ID(),'upload_your_photos_jpeg_or_pdf_file_formats_only_11', true));
                            if (!empty($images)) {
                                foreach ($images as $key => $image) {
                                    $check_pdf = count(explode(".pdf",$image));
                                    if($check_pdf>1){
                                        if(!empty($image)){
                                            echo '<a target="_blank" href="http'.$image.'"  class="pdf_url_with_icon"><i class="fa fa-file-pdf-o" style="font-size:36px"></i></a>'; 
                                        }
                                    }else{
                                        if(!empty($image)){
                                            echo '<img src = "http'.$image.'" alt="Girl in a jacket" >'; 
                                        }
                                    }
                                }
                            }
                            
                          //Product Images
                          // $Product_Images = $wpdb->get_results( "SELECT * FROM wp_wpforms_entry_fields WHERE entry_id = $entry_id AND form_id = $form_ID AND field_id IN (11)" );
                          // foreach(json_decode($row->fields) as $entry_field){              
                          //   foreach($entry_field->value_raw as $img_val){ 
                          //     echo '<img src = "'.$img_val->value.'" alt="Girl in a jacket" >'; 
                          //   }  
                          // }
                        ?>
                      </div>
                    </div>
                    <div class="form_judges_voting">
                        <form method="post" action="" >
                            <div class="form_main_fist">
                                <div class="main_child_fist main_child">
                                    <div class="judges_voting_comments form_fist">
                                        <div class="judges_voting form_fist_inner">
                                            <h3>Judge</h3>
                                        </div>
                                        <div class="judges_voting form_fist_inner">
                                            <h3 class="fede">Number of Votes</h3>
                                            <p>(max 5 votes per entry)</p>
                                        </div>
                                    </div>
                                    <div class="mauro_porcini form_fist">
                                        <div class="judges_voting form_fist_inner user_name">
                                            <?php 
                                            $wp_get_current_user = wp_get_current_user();
                                            // print_r(expression)
                                            ?>
                                            <h3><?php echo ucfirst($wp_get_current_user->display_name); ?></h3>
                                        </div>
                                        <div class="mauro_porcini form_fist_inner">
                                            <ol>
                                                <?php
                                                $user_id = get_current_user_id();
                                                $mauro_porcini_count = get_post_meta($_REQUEST['entry_id'],'mauro_porcini_count_'.$user_id,true);
                                                ?>
                                                <li class="mauro_porcini_count <?php if (1<= $mauro_porcini_count) echo 'active'; ?>" count="1"><span></span></li>
                                                <li class="mauro_porcini_count <?php if (2<= $mauro_porcini_count) echo 'active'; ?>" count="2"><span></span></li>
                                                <li class="mauro_porcini_count <?php if (3<= $mauro_porcini_count) echo 'active'; ?>" count="3"><span></span></li>
                                                <li class="mauro_porcini_count <?php if (4<= $mauro_porcini_count) echo 'active'; ?>" count="4"><span></span></li>
                                                <li class="mauro_porcini_count <?php if (5<= $mauro_porcini_count) echo 'active'; ?>" count="5"><span></span></li>
                                            </ol>

                                        </div>
                                    </div>
                                    <div class="judges_voting_comments form_fist">
                                        <!-- <div class="judges_voting_comments form_fist note">
                                            <div class="judges_voting form_fist_inner">
                                                <h3>Number of votes:</h3>
                                                <p>(max 5 votes per entry)</p>
                                            </div>
                                        </div> -->
                                        <div class="comments form_fist_inner">
                                            <input type="submit" name="save_judges_voting" value="Submit" class="save_judges_voting">
                                        </div>
                                    </div>
                                </div>
                                <div class="main_child_secand main_child">
                                    <div class="comments form_fist_inner">
                                        <?php 
                                        $mauro_porcini_count = get_post_meta($_REQUEST['entry_id'],'mauro_porcini_count_'.$user_id,true);
                                        ?>
                                        <input type="hidden" name="mauro_porcini_count" class="mauro_porcini_count" value="<?php echo $mauro_porcini_count; ?>">
                                        <textarea name="mauro_porcini" class="mauro_porcini_value comments" placeholder="Comment (Optional)" value="<?php echo get_post_meta($_REQUEST['entry_id'],'mauro_porcini_comment_'.$user_id,true);  ?>"></textarea>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="form_single_entry_link_back_pre_nex">
                        <?php 
                        $desable = "";
                        if($previous_empty_id==""){
                            $desable = "disabled";
                        }

                      ?>
                      <div class="form_single_entry_link_pre">
                        <a href="/entry/?entry_id=<?php echo $previous_empty_id;  ?>" class="<?php echo $desable; ?>">Previous Entry</a>
                      </div>
                      <div class="form_single_entry_link_back">
                        <a href="<?php  echo home_url(); ?>/design-challenge/voting-portal/">INDEX</a>
                      </div>
                      <?php 
                        if($next_empty_id==""){
                            $desable = "disabled";
                        }
                        ?>
                      <div class="form_single_entry_link_nex">
                        <a href="/entry/?entry_id=<?php echo $next_empty_id;  ?>"  class="<?php echo $desable; ?>">Next Entry</a>
                      </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo  "<p>NO data found</p>";
        }
        /* Restore original Post Data */
        wp_reset_postdata();
        $user_id = get_current_user_id();
        $the_query = new WP_Query(array( 'post_type' => 'submit-your-recipe','post_status'   => 'publish','orderby' => 'ID','order'   => 'ASC', 'posts_per_page' => -1,'meta_query' => array(array( 'key' => 'mauro_porcini_done_'.$user_id,'value' => 'done','compare' => 'NOT EXISTS'))));
        $i=1;
        $next_empty_id = "";
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                if(get_the_ID()>$entry_id){
                    if($i==1){
                        $next_empty_id =  get_the_ID();
                        $i++;
                    }
                }
            }
        }
        wp_reset_postdata();
        if($next_empty_id==""){
            $next_empty_url = "/design-challenge/voting-portal/"; 
        }else{
            $next_empty_url = "/entry/?entry_id=".$next_empty_id;
        }
    ?>
    <script type="text/javascript">
        jQuery(document).on("click",".mauro_porcini_count ",function(event){
            var judges_voting_count = jQuery(this).attr('count');
            jQuery(this).parent().children("li").removeClass("active");    
            jQuery(this).parent().children("li").each(function() {
               var li_count = jQuery( this ).attr( "count" );
                if(li_count <=judges_voting_count){
                    jQuery( this ).addClass("active");
                }
            });
            jQuery(".mauro_porcini_count").val(judges_voting_count);
        });
        jQuery(document).on("click",".save_judges_voting ",function(event){
            event.preventDefault()
            var mauro_porcini_count = jQuery(".mauro_porcini_count").val();
            var mauro_porcini_value = jQuery(".mauro_porcini_value").val();
            var form_data = "action=judges_voting_comments&mauro_porcini_value="+mauro_porcini_value+"&mauro_porcini_count="+mauro_porcini_count+"&entry_id=<?php echo $_REQUEST['entry_id']; ?>";
            jQuery.ajax({
                type : "post",
                dataType : "html",
                url : "<?php echo admin_url('admin-ajax.php'); ?>",
                data : form_data,
                success: function(response){
                    if(response!=""){
                        window.location.replace("<?php echo home_url().$next_empty_url; ?>");
                    }
                }
            });
        });
        jQuery("body").addClass("custom_entry_page")
    </script>
    <?php
      $output = ob_get_clean();
    return $output;
  //  add_filter('body_class', 'my_plugin_body_class');
}
function my_plugin_body_class($classes) {
    $classes[] = 'custom_entry_page';
    return $classes;
}
function get_previous_post_id( $post_id ) {
    // Get a global post reference since get_adjacent_post() references it
    global $post;

    // Store the existing post object for later so we don lose it
    $oldGlobal = $post;

    // Get the post object for the specified post and place it in the global variable
    $post = get_post( $post_id );

    // Get the post object for the previous post
    $previous_post = get_previous_post();

    // Reset our global object
    $post = $oldGlobal;

    if ( '' == $previous_post ) {
        return 0;
    }

    return $previous_post->ID;
}

add_action("wp_ajax_judges_voting_comments", "judges_voting_comments");
add_action("wp_ajax_nopriv_judges_voting_comments", "judges_voting_comments");
function judges_voting_comments(){
    $user_id = get_current_user_id();
    global $wpdb;
    // $comments_meta_kye = $_REQUEST['comments_meta_kye']."_".$user_id;
    // $comments_meta_value = $_REQUEST['comments_meta_value'];
    $post_id= $_REQUEST['entry_id'];
    update_post_meta($post_id,'mauro_porcini_comment_'.$user_id,$_REQUEST['mauro_porcini_value']);
    update_post_meta($post_id,'mauro_porcini_count_'.$user_id,$_REQUEST['mauro_porcini_count']);
    update_post_meta($post_id,'mauro_porcini_done_'.$user_id,'done');
    
    $judges_voting = get_post_meta($post_id,'judges_voting',true);
    $total_judges_voting_count = get_post_meta($post_id,'total_judges_voting_count',true);
    if(!empty($judges_voting)){
        if(!in_array($user_id, $judges_voting)){
            array_push($judges_voting,$user_id);
            // $total_judges_vot_count = $total_judges_voting_count-$_REQUEST['mauro_porcini_value'];
            update_post_meta($post_id,'judges_voting',$judges_voting);
            
        }
    }else{
        // $total_judges_vot_count = $total_judges_voting_count+$_REQUEST['mauro_porcini_value'];
        update_post_meta($post_id,'judges_voting',array($user_id));
        
    }
    $post_loop = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id = ".$post_id." AND meta_key LIKE '%mauro_porcini_count%'");
    $mauro_porcini_count = array(0);
    foreach ($post_loop as $key => $value) {
        $mauro_porcini_count[] = $value->meta_value;
    }
    $total_judges_vot_count = array_sum($mauro_porcini_count);
    update_post_meta($post_id,'total_judges_voting_count',$total_judges_vot_count);
    $user_judges_voting = get_user_meta( $user_id , "judges_voting",teue);
    if(!empty($user_judges_voting)){
        if(!in_array($post_id, $user_judges_voting)){
            array_push($user_judges_voting,$post_id);
            update_user_meta($user_id ,"judges_voting",$user_judges_voting);
        }
    }else{
        update_user_meta($user_id ,"judges_voting", array($post_id));
        
    }

    // print_r(get_user_meta( $user_id , "judges_voting",teue));
    // echo $comments_meta_kye.get_post_meta($post_id,$comments_meta_kye,true);
    echo "Data Save";
    die();
}
// end

function my_restrict_wpadmin_access() {
    if ( ! defined('DOING_AJAX') || ! DOING_AJAX ) {
        $user = wp_get_current_user();

        if ( isset( $user->roles ) && is_array( $user->roles ) ) {
            if ( in_array('judge', $user->roles) ) {
                wp_redirect( home_url().'/design-challenge/voting-portal/' );
                die();
            }
        }
    }
}
add_action( 'admin_init', 'my_restrict_wpadmin_access' );

add_action('admin_menu', 'wpdocs_register_my_custom_submenu_page'); 
function wpdocs_register_my_custom_submenu_page() {
    add_submenu_page(
        'wpforms-overview',
        'Judges Voting',
        'Judges Voting',
        'manage_options',
        'judges-voting',
        'wpdocs_my_custom_submenu_page_callback' );
    add_submenu_page(
        'wpforms-overview',
        'Voting Results',
        'Voting Results',
        'manage_options',
        'voting-results',
        'wpdocs_my_custom_submenu_page_callback_voting_results' );
}
 
function wpdocs_my_custom_submenu_page_callback() {
    if(!isset($_REQUEST['user_id']) || $_REQUEST['user_id']==""){
        echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script><div class="wrap"><div id="icon-tools" class="icon32"></div>';
            echo '<h2>Judges Voting</h2>
                <canvas id="myChart" class="judges_chart_voting" width="150" height="150"></canvas>';
            $blogusers = get_users( array( 'role__in' => array(  'judge' ) ) );
            // Array of WP_User objects.
            $judge_name = array(); 
            $user_judges_voting = array();
            echo "<table class='judges_table_voting'>
                    <tr><th>Name</th><th>Total Voting on post</th><th>Restart</th></tr>";
            foreach ( $blogusers as $user ) {
                $judge_name[] =$user->display_name;
                // get_user_meta( $user->ID , "judges_voting",teue);
                $total = get_user_meta( $user->ID , "judges_voting",true);
                // print_r($total);
                if($total==""){
                    $count = 0;
                }else{
                    $count = count($total);
                }
                $user_judges_voting[] =$count;
                echo "<tr><td><a href='".home_url()."/wp-admin/admin.php?page=judges-voting&user_id=".$user->ID."'>".$user->display_name."</a></td><td>".$count."</td><td><a href='' class='restore_voting' voting='single_user' user_id=".$user->ID.">Restart user Voting</a></td></tr>";
                // <td><a href='' class='restore_voting'>Restart user Voting</a></td>
            }
            echo "<tr><td colspan='2'><a href='' class='restore_voting' voting='all' user_id=''>Restart Voting all</a><td></tr>";
            echo "</table>";
            ?>
            <script>
                jQuery(document).on('click','.restore_voting',function(event){
                    event.preventDefault()
                    var voting = jQuery(this).attr('voting');
                    var user_id = jQuery(this).attr('user_id');
                    var form_data = "action=judges_voting_restore&voting="+voting+"&user_id="+user_id;
                    jQuery.ajax({
                        type : "post",
                        dataType : "html",
                        url : "<?php echo admin_url('admin-ajax.php'); ?>",
                        data : form_data,
                        success: function(response){
                            if(response!=""){
                              location.reload();
                            }
                        }
                    });
                    // delete_post_meta($post_id,$meta_key )
                    // delete_user_meta($user_id,$meta_key )
                });
            var ctx = document.getElementById('myChart').getContext('2d');
            var myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Total post','<?php echo implode("','",$judge_name); ?>'],
                    datasets: [{
                        label: '# of Votes',
                        data: [<?php echo wp_count_posts('submit-your-recipe')->publish; ?>,'<?php echo implode("','",$user_judges_voting); ?>'],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(255, 159, 64, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)',
                            'rgba(255, 159, 64, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            </script>
            <?php
        echo '</div>';
    }else{
        $user_id = get_user_by('id', $_REQUEST['user_id']);
        // print_r($user_id->data->display_name);
        $post_status_need_edit = "";
        if(isset($_REQUEST['delete_all'])) {
            $post_status_need_edit = "post_status_need_edit";
            $post_status_need_all = "post_status_need_all";
        }
        if (isset($_REQUEST['draft_post_id'])) {
            $post_status_need_edit = "post_status_need_edit";
        }
        if($post_status_need_edit!="" && $post_status_need_edit == "post_status_need_edit"){
            // print_r($_REQUEST['submit_your_recipe']);
            if($post_status_need_all == "post_status_need_all" ){
                $submit_your_recipe = $_REQUEST['submit_your_recipe'];
                foreach ($submit_your_recipe as $key => $submit_your_recipe_value) {
                    // echo $submit_your_recipe_value."<br>";
                    wp_update_post( array('ID' =>$submit_your_recipe_value,'post_status' => 'draft'));
                }
            }else{
                wp_update_post( array('ID' =>$_REQUEST['draft_post_id'],'post_status' => 'draft'));
            }
            wp_redirect( home_url() ."/wp-admin/admin.php?page=judges-voting&user_id=".$_REQUEST['user_id']); 
            die();
        }
        ?>
        <div class="judges_voting_post">
        <h2><?php echo ucfirst($user_id->data->display_name); ?></h2>
        <h3>Judges voting on post</h3>
        <form action="" method="post">
            <table>
                <tr>
                    <th>S.no</th>
                    <th>Designer name</th>
                    <th>Designer rating</th>
                    <th>Comments</th>
                    <th></th>
                </tr>
                <?php
                $the_query = new WP_Query(array( 'post_type' => 'submit-your-recipe', 'post_status'   => 'publish','posts_per_page' => -1,'paged'=> $paged, 'meta_query' => array(array( 'key' => 'mauro_porcini_done_'.$_REQUEST['user_id'] ,'value' =>'done', 'compare' => '='))) );
                if ( $the_query->have_posts() ) {
                    $i=1;
                    while ( $the_query->have_posts() ) {
                        $the_query->the_post(); 
                         $mauro_porcini_count = get_post_meta(get_the_ID(),'mauro_porcini_count_'.$_REQUEST['user_id'],true);
                         $total_judges_voting_count = get_post_meta(get_the_ID(),'total_judges_voting_count',true);
                        echo "<tr>
                            <td>".$i." <input id='cb-select-".get_the_ID()."' type='checkbox' name='submit_your_recipe[]' value='".get_the_ID()."'></td>
                            <td>".get_the_title()." ".get_the_ID()."</td>
                            <td>".get_post_meta(get_the_ID(),'mauro_porcini_count_'.$_REQUEST['user_id'],true)."</td>
                            <td>".get_post_meta(get_the_ID(),'mauro_porcini_comment_'.$_REQUEST['user_id'],true)."</td>
                            <td><a href='".home_url() ."/wp-admin/admin.php?page=judges-voting&user_id=".$_REQUEST['user_id']."&draft_post_id=".get_the_ID()."' delete-id='".get_the_ID()."' >Delete</a></td>
                            </tr>";
                        $i++;
                    }
                }else{
                    echo "<tr><th colspan='5'>No data found</th></tr>";
                }
                 /* Restore original Post Data */
                wp_reset_postdata();
                ?>
                <tr><td colspan='4'>It is not empty. Other Ids already Deleted</td><td><button name="delete_all">Delete all selected</button></td></tr>
            </table>
        </form>
        </div>
        <?php
    }
}
add_action("wp_ajax_judges_voting_restore", "judges_voting_restore");
add_action("wp_ajax_nopriv_judges_voting_restore", "judges_voting_restore");
function judges_voting_restore(){
    global $wpdb;
    if($_REQUEST['voting'] == "all"){
        $the_query = new WP_Query(array( 'post_type' => 'submit-your-recipe','post_status'   => 'publish', 'posts_per_page' => -1, 'meta_key' => 'total_judges_voting_count','orderby' => 'meta_value','order' => 'DESC',) );
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post(); 
                $blogusers = get_users( array( 'role__in' => array(  'judge' ) ) );
                foreach ( $blogusers as $user ) {
                    delete_user_meta($user->ID,'judges_voting' );
                    delete_post_meta_by_key( 'mauro_porcini_comment_'.$user->ID );
                    delete_post_meta_by_key( 'mauro_porcini_count_'.$user->ID );
                    delete_post_meta_by_key( 'mauro_porcini_done_'.$user->ID );
                    $post_loop = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id = ".get_the_ID()." AND meta_key LIKE '%mauro_porcini_count%'");
                    $mauro_porcini_count = array(0);
                    foreach ($post_loop as $key => $value) {
                        $mauro_porcini_count[] = $value->meta_value;
                    }
                    $total_judges_vot_count = array_sum($mauro_porcini_count);
                    update_post_meta(get_the_ID(),'total_judges_voting_count',$total_judges_vot_count);

                }
                delete_post_meta_by_key( 'total_judges_voting_count');
            }
        }
    }
    if($_REQUEST['voting']=="single_user"){
        $the_query = new WP_Query(array( 'post_type' => 'submit-your-recipe','post_status'   => 'publish', 'posts_per_page' => -1, 'meta_key' => 'total_judges_voting_count','orderby' => 'meta_value','order' => 'DESC',) );
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post(); 
                delete_user_meta($_REQUEST['user_id'],'judges_voting' );
                // $total_judges_voting_count= get_post_meta(get_the_ID(),'total_judges_voting_count',true)
                delete_post_meta_by_key( 'mauro_porcini_comment_'.$_REQUEST['user_id'] );
                delete_post_meta_by_key( 'mauro_porcini_count_'.$_REQUEST['user_id'] );
                delete_post_meta_by_key( 'mauro_porcini_done_'.$_REQUEST['user_id'] );
                $post_loop = $wpdb->get_results("SELECT * FROM $wpdb->postmeta WHERE post_id = ".get_the_ID()." AND meta_key LIKE '%mauro_porcini_count%'");
                $mauro_porcini_count = array(0);
                foreach ($post_loop as $key => $value) {
                    $mauro_porcini_count[] = $value->meta_value;
                }
                $total_judges_vot_count = array_sum($mauro_porcini_count);
                update_post_meta(get_the_ID(),'total_judges_voting_count',$total_judges_vot_count);
            
            }
        }
    }
    echo "Done";
    die();
}
function wpdocs_my_custom_submenu_page_callback_voting_results(){
    echo '<div class="wrap_voting_result"><div id="icon-tools" class="icon32"></div>';
          
            ?>
        <div class="show_voting_result">
            <h2>Voting results</h2>
            <!-- <pre> -->
            <table class='judges_table_voting'>
                <thead>
                    <th>Sr. no</th>
                    <!--<th>Post title with ID</th>
                    <th>Post voting</th>-->
                    <th>Name With ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Level</th>
                    <th>Category</th>
                    <th>Project Name</th>
                    <th>Project Type</th>
                    <th></th>
                    <th>Export data</th>
                </thead>
                <tbody>
                    <?php
                    if(!isset($_REQUEST['paged'])){
                        $paged = 1; 
                    }else{
                        $paged = $_REQUEST['paged'];
                    }
                    $the_query = new WP_Query(array( 'post_type' => 'submit-your-recipe','post_status'   => 'publish', 'posts_per_page' => 90, 'meta_key' => 'total_judges_voting_count','orderby' => 'meta_value','order' => 'DESC',) );
                    $max_count = array();
                   /* if ( $the_query->have_posts() ) {

                        while ( $the_query->have_posts() ) {
                            $the_query->the_post(); 
                            if (isset($_REQUEST['user_id'])) {
                            	echo 'sdfdfgdfsdfsf';
                            	$mauro_porcini_count = get_post_meta(get_the_ID(),'mauro_porcini_count_'.$_REQUEST['user_id'],true);
                            }
                             

                            $total_judges_voting_count = get_post_meta(get_the_ID(),'total_judges_voting_count',true);
                            $max_count[] = $total_judges_voting_count;
                        }
                    }*/
                  //  $the_query = new WP_Query(array( 'post_type' => 'submit-your-recipe', 'post_status' => 'publish', 'posts_per_page' => 90,'paged'=> $paged, 'meta_key' => 'total_judges_voting_count','orderby' => 'meta_value','order' => 'DESC',) );
                    global $wpdb;

                    
                     $queryw = $wpdb->get_results("SELECT * FROM wp_posts where post_type = 'submit-your-recipe' and post_status = 'publish'");
                      //echo '<pre>'; print_r($query);
                    if(!empty($queryw)) {
                        $i=1;
                        
                        //while ( $query->have_posts() ) {
                         //   $query->the_post();
                          foreach($queryw as $rows){ 
                          	 
                          
                           $post_ids = $rows->ID;
                           $name_in_post = get_post_meta($post_ids, 'name_2', true);
                           $lastname_in_post = get_post_meta($post_ids, 'last_name_3', true);
                           $level_in_post = get_post_meta($post_ids, 'multiple_choice_33', true);
                           $category_in_post = get_post_meta($post_ids, 'category:_21', true);
                           $projectname_in_post = get_post_meta($post_ids, 'project_name_28', true);
                           $projecttype_in_post = get_post_meta($post_ids, 'product_type_25', true);
                           //echo $max_count;

                             
							if(isset($_REQUEST['user_id'])){
	                            $mauro_porcini_count = get_post_meta($post_ids, 'mauro_porcini_count_'.$_REQUEST['user_id'], true);
	                        }
                             $total_judges_voting_count = get_post_meta($post_ids, 'total_judges_voting_count', true);
                             $max_count[] = $total_judges_voting_count;
                             $entry_id = get_post_meta($post_ids, 'entry_id', true);
                            // echo '<pre>'; print_r($max_count);
                             if($total_judges_voting_count == 0){
                                $winner = "";   
                             }else if($max_count == $total_judges_voting_count) {
                                $winner = "winner";
                             }else{
                                $winner = "";
                             }
                             //max($max_count)

                             // echo $max_count;
                             //remove td ara commented
                             //<td> <a href='".admin_url()."admin.php?page=wpforms-entries&view=details&entry_id=".$entry_id."'>".get_the_title().' ('.get_the_ID().")</a></td>
                                //<td>".$total_judges_voting_count."</td>

                            echo "<tr>
                                <td>".$i."</td>
                                <td>".$name_in_post." (".$post_ids.")</td>
                                <td>".$name_in_post."</td>
                                <td>".$lastname_in_post."</td>
                                <td>".$level_in_post."</td>
                                <td>".$category_in_post."</td>
                                <td>".$projectname_in_post."</td>
                                <td>".$projecttype_in_post."</td>
                                <td>".$winner."</td>
                                <td ><a href='".admin_url()."?export_all_posts=export_all_posts&postid=".$post_ids."'>Export</a></td>
                                </tr>";
                            $i++;
                            
                            
                        }
                    }else{
                        echo "<tr><th colspan='9'>No data found</th>";
                    }
                    /* Restore original Post Data */
                  //  wp_reset_postdata();
                    // print_r($all_post_data);
                    ?>
                </tbody>
                <tfoot>
                	<tr>
                		<th colspan="9">
                			Export All:
                		</th>
                		<td class="export_data">
                			<a href="<?php echo admin_url(); ?>?export_all_posts=export_all_posts">Export data</a> 
                		</td>
                	</tr>
                </tfoot>
            </table>
            <!-- </pre> -->
        </div>
      <!--   <script type="text/javascript">
        	jQuery(document ).on("click", ".export_data",function(event) {
	            event.preventDefault()
	            // var jQuery_this =jQuery(this);
	            // var form_id = jQuery(this).attr("form-id");
	            var data_insert = "action=func_export_all_posts" ;
	            jQuery.ajax({
	                type : "post",
	                dataType : "json",
	                url : "<?php //echo admin_url('admin-ajax.php'); ?>",
	                data : data_insert ,
	                success: function(response) {
	                }
	            });
	        });
        </script> -->
            <?php
          
            echo "<nav class=\"sw-pagination\">";
            $big = 999999999; // need an unlikely integer
            echo paginate_links( array(
                'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                'format' => '?paged=%#%',
                'current' => max( 1, $paged ),
                'total' => $the_query->max_num_pages
            ) );               
            echo "</nav>";
        echo '</div>';
}
function your_function() {
    if(!is_user_logged_in()){
    ?>
        <style type="text/css">
            #menu-item-52453{
                display: none;
            }
        </style>
    <?php
    }
}

add_action( 'wp_footer', 'your_function' );
add_action("wp_ajax_func_export_all_posts", "func_export_all_posts");
add_action("wp_ajax_nopriv_func_export_all_posts", "func_export_all_posts");
function func_export_all_posts() {
	 if(isset($_GET['export_all_posts'])) {
       // $id = get_the_id();
        // print_r("$id");
        if(isset($_GET["postid"])){

            
            $the_query_csv = new WP_Query(array( 'post_type' => 'submit-your-recipe', 'post__in' => array( $_GET["postid"]), 'post_status'   => 'publish', 'posts_per_page' => -1) );
          //  print_r($the_query_csv);
            
        }else{
            $the_query_csv = new WP_Query(array( 'post_type' => 'submit-your-recipe','post_status'   => 'publish', 'posts_per_page' => -1) );
        }
  		$posts = $the_query_csv->posts;
  		
  		foreach($posts as $post) {
  			// echo $post->ID."<br>";
  			$post_arry_values = array();
  			$meta_arry_values = array();
		    $post_values = get_post( $post->ID );
          	foreach ($post_values as $key => $post_value) {
              	$post_arry_values[] = $post_value;
              }
              $meta_values = get_post_meta( $post->ID );
            foreach ($meta_values as $key => $meta_value) {
          	$meta_arry_values[] = $meta_value[0];
            }
          $meta_array_keys[] = array_keys($meta_values);
          $all_post_data[] = array_merge($post_arry_values,$meta_arry_values);

		}
  //       echo "<pre>";
		// print_r($all_post_data);
  //       echo "</pre>";
  //       die();

           header('Content-type: text/csv');
             header('Content-Disposition: attachment; filename="Post Dat.csv"');
             header('Pragma: no-cache');
            header('Expires: 0');
  
            $file = fopen('php://output', 'w');
          
            fputcsv($file, array_merge(array("ID","post_author","post_date","post_date_gmt","post_content","post_title","post_excerpt","post_status","comment_status","ping_status","post_password","post_name","to_ping","pinged","post_modified","post_modified_gmt","post_content_filtered","post_parent","guid","menu_order","post_type","post_mime_type","comment_count","filter"),array_unique($meta_array_keys)[0]));

           
            foreach ($all_post_data as $all_post) {
              setup_postdata($all_post);
              
                $data_values = array($all_post[0],$all_post[1],$all_post[2],$all_post[3],$all_post[4],$all_post[5],$all_post[6],$all_post[7],$all_post[8],$all_post[9],$all_post[10],$all_post[11],$all_post[12],$all_post[13],$all_post[14],$all_post[15],$all_post[16],$all_post[17],$all_post[18],$all_post[19],$all_post[20],$all_post[21],$all_post[22],$all_post[23],$all_post[24],$all_post[25],$all_post[26],$all_post[27],$all_post[28],$all_post[29],$all_post[30],$all_post[31],$all_post[32],$all_post[33],$all_post[34],$all_post[35],$all_post[36],$all_post[37],$all_post[38],$all_post[39]);
                
                  fputcsv($file, $data_values); 
           }
                
  
            exit();

    }       

}

add_action( 'init', 'func_export_all_posts' );


