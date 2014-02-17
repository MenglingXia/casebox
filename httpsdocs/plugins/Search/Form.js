Ext.namespace('CB.plugins.Search');

CB.plugins.Search.Form = Ext.extend(Ext.Panel, {
    title: L.Search
    ,id: 'SearchTab'
    ,iconCls: 'icon-search'
    ,closable: true
    ,autoScroll: true
    ,tbarCssClass: 'x-panel-white'
    ,params: {}

    ,initComponent: function(){

        this.actions = {
            search: new Ext.Button({
                text: L.Search
                ,iconAlign:'top'
                ,iconCls: 'ib-search'
                ,scale: 'large'
                ,scope: this
                ,handler: this.onSearchButtonClick
            })
        };

        /* objectsStore used to keep selected values from the grid for rendering after edit*/
        this.objectsStore = new CB.DB.ObjectsStore();

        // Properties grid
        this.grid = Ext.create({
            refOwner: this
            ,autoHeight: true
        }, 'CBVerticalEditGrid');

        // Init
        Ext.apply(this,{
            data: { template_id: this.data.template_id}
            ,tbar: [ this.actions.search]
            ,items:[this.grid]
            ,listeners: {
                scope: this
                ,beforerender: this.onAfterRender
            }
        });

        CB.plugins.Search.Form.superclass.initComponent.apply(this, arguments);
    },

    onSearchButtonClick: function(){

        // App.mainViewPort.openDefaultExplorer();
        // App.explorer.setParams(this.data);

        this.grid.readValues();
        var t = App.openUniqueTabbedWidget(
            'CBPluginsSearchResultForm'
            ,null
            ,{data: this.data}
        );
        if(t) {
            t.setParams(this.data);
        }
    },

    onAfterRender: function(){
        for(var i in this.grid.colModel.config){
            var el = this.grid.colModel.config[i];
            if(el.dataIndex == 'info'){
                el.editor = new Ext.form.ComboBox({
                    typeAhead: true
                    ,triggerAction: 'all'
                    ,editable: true
                    ,selectOnTab: true
                    ,store: ['', 'AND', 'OR', 'NOT']
                    ,lazyRender: true
                    ,listClass: 'x-combo-list-small'
                });
                el.header = L.SearchLogic;
            }
        }
        this.grid.reload();
    }
});

Ext.reg('CBPluginsSearchForm', CB.plugins.Search.Form);
