Ext.ns('Ext.ux.TYPO3.MvcExtjs');
/**
 * 
 */
Ext.ux.TYPO3.MvcExtjs.FlashMessagesTabPanel = function(){
	
	var oldMessages;
	var newMessages;
	var tabPabel;
	
	var addMessages = function(msgs) {
		flushMessages();
		newMessages.addAll(msgs);
		newMessages.each(function(message,index,length) {
			iconCls = Ext.ux.TDGi.iconMgr.getT3Icon(message.type+'.png'),
			tmpTab = new Ext.Panel({
				title: message.type,
				tstamp: message.tstamp,
				id: message.message + '-' + message.tstamp,
				iconCls: iconCls,
				html: message.message,
				closable: true
			});
			tabPanel.add(tmpTab);
		});
	};
	/**
	 * Makes all currently new Messages become old.
	 * The tabs are removed and the message object move from newMessages to oldMessages
	 */
	var flushMessages = function(message) {
		newMessages.each(function(message,index,length) {
			panelToRemove = tabPanel.findById(message.message + '-' + message.tstamp);
			if (panelToRemove) tabPanel.remove(panelToRemove);
			oldMessages.add(message);
			newMessages.remove(message);
		});
	}

	var getTabPanel = function() {
		return tabPanel;
	}
	
	var createTabPanel = function(config) {
		tabConfig = Ext.apply({
			region: 'north',
			height: 120,
			tabHeight: 10,
			plugins: [ new Ext.ux.TabScrollerMenu({
	    		maxText  : 30,
	    		pageSize : 10,
	    		menuPrefixText: 'Tab'
	    	})],
	    	listeners: {
				add: function(tabPanel, addedComponent, index) {
					tabPanel.activate(addedComponent);
				}
			}
		},config);
		tmpTabs = new Ext.TabPanel(tabConfig);
		tmpTabs.add({
			xtype: 'panel',
			title: 'Nachrichten',
			iconCls: Ext.ux.TDGi.iconMgr.getT3Icon('notice.png'),
			html: 'FlashMessage Anzeige ...',
			closable: false
		});
		return tmpTabs;
	}
	
	var initialize = function(config) {
		oldMessages = new Ext.util.MixedCollection();
		newMessages = new Ext.util.MixedCollection();
		tabPanel = createTabPanel(config);
		Ext.ux.TYPO3.MvcExtjs.DirectFlashMessageDispatcher.on('new',addMessages);
	};
	
    return Ext.apply(new Ext.util.Observable, {
    	getTabPanel: getTabPanel,
    	initialize: initialize
    })
}();