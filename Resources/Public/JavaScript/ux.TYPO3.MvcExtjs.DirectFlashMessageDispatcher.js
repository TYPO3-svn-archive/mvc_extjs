Ext.ns('Ext.ux.TYPO3.MvcExtjs');
/**
 * This class fetches Ext.Direct events, fires a event if
 * new FlashMessages are available and removes the messages from the
 * Ext.Direct response event.
 * 
 * Register your FlashMessage-processing ExtJS component like this:
 * Ext.ux.TYPO3.MvcExtjs.DirectFlashMessages.on('new',function(flashMessages) {
 * 		//do something with incoming FlashMessages
 * });
 * 
 */
Ext.ux.TYPO3.MvcExtjs.DirectFlashMessageDispatcher = function(){
	/**
	 * @class Ext.util.Observable
	 */
	var directFlashMessages = new Ext.util.Observable;
	directFlashMessages.addEvents('new');
	
	var initialize = function() {
        Ext.Direct.on('event',fetchMessagesAndFire);
	};
	
	var fetchMessagesAndFire = function(event, provider) {
		if (event.flashMessages) {
			flashMessages = event.flashMessages;
			delete event.flashMessages;
			directFlashMessages.fireEvent('new',flashMessages);
		}
	};
	
    return Ext.apply(directFlashMessages, {
    	initialize: initialize
    });
}();