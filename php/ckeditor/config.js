/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	
	config.toolbarCanCollapse = false;
	config.disableNativeTableHandles = true;
	config.entities = false;	
	config.fullPage = true;
	config.resize_enabled = false;
	config.browserContextMenuOnCtrl = true;
	config.forcePasteAsPlainText = true;
	config.enterMode = CKEDITOR.ENTER_P;
	config.resize_minHeight = 375; // min resize height
  	config.height = 375; // starting height	
	
	
	
};
