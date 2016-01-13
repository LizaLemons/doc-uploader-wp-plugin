jQuery(function($) {
  // hide the editor for CPTs
  if ( $('body').hasClass('post-type-netting') || $('body').hasClass('post-type-collateral') || $('body').hasClass('post-type-us_fcm_cmro') ||   $('body').hasClass('post-type-p2p_cmro') || $('body').hasClass('post-type-p2p_cro') ) {
    $('.postarea.wp-editor-expand').hide();
  }

  // upon pg load:
  $('#done-btn').hide();
  $('.edit-doc-btn').hide();
  $('.done-doc-btn').hide();

  // WP Media Uploader
  var mediaUploader;
  $('#upload-button').click(function(e) {
    e.preventDefault();
    // If the uploader object has already been created, reopen the dialog
    if (mediaUploader) {
      mediaUploader.open();
      return;
    }

    // Extend the wp.media object
    mediaUploader = wp.media.frames.file_frame = wp.media({
      title: 'Choose File',
      button: {
        text: 'Choose File'
      },
      multiple: true
    });

    // All this takes place in "Upload Docs" metabox
    mediaUploader.on('select', function() {
      // hide 'Upload Documents' btn
      $('#upload-button').hide();
      $('#done-btn').show();

      // all uploaded docs
      // menu order: count li's on page:
      var numExistingDocs = $('li.individual-doc-li').length;
      var selectionArr = mediaUploader.state().get('selection').toJSON();

      for (var i = 0; i < selectionArr.length; i++) {
        // console.log( "selectionArr[i]",selectionArr[i] );
        var counter = i + 1;
        var menuOrder = numExistingDocs + 1;
        var currentAttachment = selectionArr[i];
        // console.log(currentAttachment);
        var docFilename = currentAttachment.filename;
        var docTitle = currentAttachment.title;
        var docID = currentAttachment.id;
        // append name of doc to metabox
        $('#doc-uploader-div .inside #uploaded-docs-div').append(
          '<span class="doc-num">Document #' + counter + ':</span><br><br>' +
          '<span class="highlight"><b>Display text:  </b></span>' +
          '<input type="text" value="" ' +
                  'placeholder="Example: May 25, 2011 (Update)" ' +
                  'name="text-to-display[' + docID + '] "' +
                  'size=60 required value="init val"/><br>' +
          '<span><b>Document name:  </b></span>' +
          '<span>' + docFilename + '<span/><br><br>' +
          '<input size=5 type="text" value="' + menuOrder + '" name="updated-menu-order[' + docID + ']"> ' +
          '<br><br><hr>'
        );
        numExistingDocs++;
      };
      // alert("Don't forget to fill out 'Text to Display' after uploading the files.");
    });
    // Open the uploader dialog
    mediaUploader.open();
  }); // end mediaUploader


  //**** Attached Docs box ****//

  // 'delete attachment' btn
  $('.delete-doc-btn').on('click', function() {
    var deletePopup = confirm('Are you sure you want to delete this document?');
    if (deletePopup == true) {
      $(this).closest('li.individual-doc-li').hide();
      $(this).closest('li.individual-doc-li').find(".hidden_delete_val" ).prop( "checked", true );
    }
  });

  // sortable fxn to order Attached Docs box - menu order
  // make sure this el is on pg first 
  if ( $('.draggable-list').length > 0 ) {
    $('.draggable-list').sortable({
      opacity: '0.5',
      revert: 500,
      zIndex: 9999,
      refreshPositions: true,
      stop: function() { // when sorting stops
        // create IDs array
        var idArray = [];
        $('li.individual-doc-li').each(function(index) {
          idArray.push( $(this).attr('id') );
        });
        // set menu order of li
        $('li.individual-doc-li').each(function(index) {
          var index = idArray.indexOf( $(this).attr('id') );
          $(this).find('input.hidden-menu-order').val(index + 1);
        });
      }
    });
  }

}); // end jQ fxn
