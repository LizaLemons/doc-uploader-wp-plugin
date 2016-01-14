<?php
/*
	Plugin Name: Document Uploader for Pages & CPTs in WP
	Plugin URI: http://isda.org
	Description: Plugin adds a document uploader for specified CPTs
	Version: 1.0
	Author: Liza Ramo
	Author URI: http://www.lizaramo.com
	License: GPL3
*/

/* Note: wp-editor must be present on CPTs to work */

/////////////////////////////
///// Setup /////////////////
/////////////////////////////

function doc_uploader_activation() {
}
register_activation_hook(__FILE__, 'doc_uploader_activation');

function doc_uploader_deactivation() {
}
register_deactivation_hook(__FILE__, 'doc_uploader_deactivation');

// Scripts & styles
function doc_uploader_scripts() {
  global $post;
  wp_enqueue_script('jquery');
  wp_enqueue_script('jquery-ui-sortable');
  wp_enqueue_script('jquery-ui-draggable');
  wp_enqueue_script('jquery-ui-droppable');
  wp_enqueue_script('jquery-ui-mouse');
  wp_register_script('doc_uploader_core_js', plugin_dir_url( __FILE__ ) . 'lr-doc-uploader-scripts.js');
  wp_enqueue_script('doc_uploader_core_js');
  wp_register_style('doc_uploader_styles', plugins_url('styles.css', __FILE__));
  wp_enqueue_style('doc_uploader_styles');
}
add_action('admin_enqueue_scripts', 'doc_uploader_scripts');

// add 'menu order' option to attachments
add_action( 'admin_init', 'attachments_menu_order' );
function attachments_menu_order() {
  add_post_type_support( 'attachment', 'page-attributes' );
}

/////////////////////////////
///// Settings page /////////
/////////////////////////////
add_action('admin_menu', 'doc_uploader_plugin_settings');
function doc_uploader_plugin_settings() {
  //create new menu item under Settings
  add_options_page(
		'Doc Uploader Settings',
		'Doc Uploader Settings',
		'administrator',
		'doc_uploader_settings',
		'doc_uploader_settings_form_fxn'
	);
}

function doc_uploader_settings_form_fxn() {
	// get previously selected locations
	$selectedLocations = get_option(doc_uploader_options);

  // Create list of all options
  $locationsOpsArr = ['pages', 'posts'];

  // get all CPTs of the site
	$args = array(
		'public'   => true,
		'_builtin' => false
	);
	$post_types = get_post_types( $args, 'names' );

  // push CPTs into arr of options
  foreach ( $post_types as $post_type ) {
    $cptObj = get_post_type_object( $post_type );
    $cptName = $cptObj->name;
    array_push( $locationsOpsArr, $cptName );
  }
  // print_r($locationsOpsArr);
	?>

	<div class="wrap">
  	<form method="post" name="options" action="options.php">
			<?php wp_nonce_field('update-options') ?>
			<h2>Add the Document Uploader to these locations:</h2>

      <em>*Note: wp-editor/TinyMCE must be present on CPTs for the document uploader to work</em><br><br>

      <?php foreach ($locationsOpsArr as $option) {
        // print_r($selectedLocations);
        if ( in_array($option, $selectedLocations ) ) {
          $isChecked = 'checked';
        } else {
          $isChecked = '';
        } ?>

        <input type="checkbox" name='doc_uploader_options[]' value="<?php echo $option ?>" <?php echo $isChecked ?>/>
        <label><?php echo $option ?></label><br>
      <?php } ?>

			<div class="submit">
				<input type="hidden" name="action" value="update" />
				<input type="hidden" name="page_options" value="doc_uploader_options" />
				<input type="submit" name="Submit" value="Update" />
			</div>
		</form>
	</div>
	<?php
} // end settings page

///////////////////////////
///// Meta boxes /////////
/////////////////////////

// add meta boxes only to the locations that the user selected in settings
function add_docs_custom_meta_boxes() {
  // get selected locations
  $selectedLocations = get_option(doc_uploader_options);
  foreach($selectedLocations as $location) {
    add_meta_box(
      'doc-uploader-div',
      'Upload Documents',
      'doc_uploader_markup',
      $location,
      'normal'
    );
    add_meta_box(
      'attached-docs-div',
      'Attached Documents',
      'attached_documents_markup',
      $location,
      'normal'
    );
  }
}
add_action('add_meta_boxes', 'add_docs_custom_meta_boxes');

// markup for Doc Uploader meta box
function doc_uploader_markup() {
  wp_nonce_field(plugin_basename(__FILE__), 'upload_docs_nonce');
  global $post;
  ?>
  <input id="upload-button" type="button" class="button" value="Upload Documents" />
  <div id="uploaded-docs-div"></div>
  <input id="done-btn" type="submit" value="Done" />
  <?php
}

/* Save edits from Upload Docs meta box */
add_action('save_post', 'save_uploaded_docs');
function save_uploaded_docs($post_id) {
  // save 'Text to Display' as attachment's post title
  foreach( $_POST['text-to-display'] AS $key => $value ) {
    if ( isset($_POST['text-to-display'][$key]) ) {
      $attachmentID = $key;
      $textToDisplay = $value;
      $attachment_post = array(
        'ID'          => $attachmentID,
        'post_title'  => $textToDisplay
      );
      wp_update_post( $attachment_post );
    }
  }
  // save menu order
  foreach( $_POST['updated-menu-order'] AS $key => $value ) {
    if ( isset($_POST['updated-menu-order'][$key]) ) {
      $attachmentID = $key;
      $menuOrder = $value;
      $attachment_post = array(
        'ID'          => $attachmentID,
        'menu_order'  => $menuOrder
      );
      wp_update_post( $attachment_post );
      update_post_meta($attachmentID, 'add_to_rss', 'checked');
    }
  }
} // end

// markup for Attached Docs meta box
function attached_documents_markup() {
  wp_nonce_field(plugin_basename(__FILE__), 'attached_docs_nonce');
  global $post;
  // retrieve all attachments with a parent ID of the current post
  $args = array(
    'post_parent'    =>   $post->ID,
    'post_type'      =>   'attachment',
    'orderby'        =>   'menu_order',
    'order'          =>   'ASC',
    'posts_per_page' =>   - 1
  );
  $attachments = get_posts( $args );
  ?>
  <div class="assets">
    <ul class="draggable-list">
      <?php foreach ( $attachments as $att ) {
        $aID = $att->ID;
        $file_url = wp_get_attachment_url( $aID );
        $filetypeArr = wp_check_filetype( $file_url );
        $filetype = $filetypeArr['ext'];
        $postTitle = $att->post_title;
        $attMetaDataArr= wp_get_attachment_metadata($aID, true);
        $fileName = basename ( get_attached_file( $aID ) );
      ?>

      <li id="<?php echo $att->ID; ?>" class="individual-doc-li">
        <p>attachment ID: <?php echo $att->ID; ?></p>

        <div class="drag-controls pull-right">
          <em>Drag to reorder</em><br>
          <span class="dashicons dashicons-arrow-up"></span>
          <span class="dashicons dashicons-arrow-down"></span>
        </div>
        <div class="doc-info">
          <b>Display text:</b><br>
          <input type="text" name="texttodisplay[<?php echo $att->ID; ?>]"
                 value="<?php echo $postTitle; ?>"
                 size=60 placeholder="Example: November 30, 2011 (Update)" class="init-disabled"><br><br>

          <b>Document name:</b><br>
          <span><?php echo $fileName; ?></span><br><br>
          <b>URL of document:</b><br>
          <a href="<?php echo $file_url ?>" target="_blank"><?php echo $file_url ?></a><br><br>

          <b>Add to RSS feed?</b>
          <input type="hidden" name="add_to_rss[<?php echo $att->ID; ?>]" value="no">
          <input type="checkbox" name="add_to_rss[<?php echo $att->ID; ?>]"
                 class="" value="yes"
                 <?php if( get_post_meta( $att->ID, 'add_to_rss', true ) == 'yes') {
                   echo 'checked="checked"';
                 } ?> >

          <br>
          <span class="hidden-info-span">
            Delete this post?
            <input type="checkbox" name="hidden_delete_val[<?php echo $att->ID; ?>]"
                   value="<?php echo $att->ID; ?>" class="hidden_delete_val"><br>
            Order num:
            <input type="text" name="hidden_order_val[<?php echo $att->ID; ?>]" size=10
                   value="<?php echo print_r($att->menu_order, true); ?>"
                   class="hidden-menu-order">
            <p>Menu order (db): <?php echo print_r($att->menu_order, true); ?></p>
          </span>
          <span class="delete-doc-btn">Delete<span class="dashicons dashicons-trash"></span></span>
        </div>
        <br><br><hr><br>
      </li>
      <?php } ?>
    </ul>
  </div>
  <?php
}

/* Save edits from Attached Docs meta box */
add_action('save_post', 'save_attached_docs_edits');
function save_attached_docs_edits($post_id) {

  // var_dump($_POST['add_to_rss']);
  // wp_die();


  //* update post title with 'Text to Display' *//
  foreach( $_POST['texttodisplay'] AS $key => $value ) {
    if ( isset($_POST['texttodisplay'][$key]) ) {
      $attachmentID = $key;
      $textToDisplay = $value;
      $attachment_post = array(
        'ID'          => $attachmentID,
        'post_title'  => $textToDisplay
      );
      wp_update_post( $attachment_post );
    }
  }

  //* update menu order of attachments *//
  foreach( $_POST['hidden_order_val'] AS $key => $value ) {
    $menu_order_updates = array(
      'ID'          => $key,
      'menu_order'  => $value
    );
    wp_update_post( $menu_order_updates );
  }

  //* save 'add to rss feed' checkbox *//
  foreach( $_POST['add_to_rss'] as $key => $value ) {
    update_post_meta( $key, 'add_to_rss', $value );
  }

  //* delete the selected attachments *//
  foreach( $_POST['hidden_delete_val'] as $key => $value ) {
    if ( isset($_POST['hidden_delete_val'][$key]) ) {
      $attachmentID = $key;
      wp_delete_post( $attachmentID );
    }
  }
}
