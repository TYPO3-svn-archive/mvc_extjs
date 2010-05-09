Ext.ns('Ext.ux.TYPO3.MvcExtjs');
/**
 * 
 */
Ext.ux.TYPO3.MvcExtjs.DataWriter = Ext.extend(Ext.data.DataWriter, {
    
	/**
	 * the name of the variable on php side
	 */
	objectName : 'genre',
	/**
	 * the key of the module
	 */
	moduleName : 'user_MvcExtjsSamplesViewbased',
	/**
	 * name of the extension in format, that extbase sets in from of the module key
	 * (for the expected parameter prefix)
	 */
	extKey: 'tx_mvcextjssamples',
	
	/**
     * Builds up the object
     * @param {object} config
     */
	initComponent: function(config) {
			// apply configuration for the object itself
		Ext.apply(this, config);
			// call superclass constructor
		Ext.ux.TYPO3.MvcExtjs.DataWriter.superclass.initComponent.call(this);
	},

	
	/**
	 * Final action of a write event.  Apply the written data-object to params.
	 * 
	 * TODO: support request hash generation as expected by extbase
	 * 
     * @param {String} action [Ext.data.Api.actions.create|read|update|destroy]
     * @param {Record[]} rs
     * @param {Object} http params
     * @param {Object} data object populated according to DataReader meta-data "root" and "idProperty"
     */
	render : function(action, rs, params, data) {
	 	Ext.iterate(data.data, function(key, value) {
	 		if (key === 'uid') {
	 			params[this.createParameterPrefix() + '[' + this.objectName + '][__identity]'] = value;
	 		} else {
	 			params[this.createParameterPrefix() + '[' + this.objectName + '][' + key + ']'] = value;
	 		}
 		}, this);
	},
	    
	/**
	  * Implements abstract Ext.data.DataWriter#createRecord
	  * @protected
	  * @param {Ext.data.Record} rec
	  * @return {Object}
	  */
	createRecord : function(rec) {
	   	var data = this.toHash(rec);
	    delete data[this.meta.idProperty];
	    return data;
	},
	    
	/**
	 * Implements abstract Ext.data.DataWriter#updateRecord
	 * @protected
	 * @param {Ext.data.Record} rec
	 * @return {Object}
	 */
	updateRecord : function(rec) {
		return this.toHash(rec);
	},
	    
	/**
	 * Implements abstract Ext.data.DataWriter#destroyRecord
	 * @protected
	 * @param {Ext.data.Record} rec
	 * @return {Object}
	 */
	destroyRecord : function(rec){
		return this.toHash(rec);
	},
	
	createParameterPrefix : function() {
		return this.extKey + '_' + (this.moduleName+'').toLowerCase();
	}
});