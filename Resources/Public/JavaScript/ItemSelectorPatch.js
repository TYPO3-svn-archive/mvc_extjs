/**
 * fixes an issue, that causes the itemselector not to load the data comin from the
 * record, that is loaded into the form
 * 1 day wasted with searching - the solution:
 * http://www.extjs.com/forum/showthread.php?t=73302
 * big thanks to sormy ;)
 */
Ext.override( Ext.ux.ItemSelector, {
    setValue: function(val) {
        this.reset();
        if (!val) return;
        val = val instanceof Array ? val : val.split(this.delimiter);
        var rec, i, id;
        for (i = 0; i < val.length; i++) {
            var vf = this.fromMultiselect.valueField;
            id = val[i];
            idx = this.toMultiselect.view.store.findBy(function(record){
                return record.data[vf] == id;
            });
            if (idx != -1) continue;            
            idx = this.fromMultiselect.view.store.findBy(function(record){
                return record.data[vf] == id;
            });
            rec = this.fromMultiselect.view.store.getAt(idx);
            if (rec) {
                this.toMultiselect.view.store.add(rec);
                this.fromMultiselect.view.store.remove(rec);
            }
        }
    }
});