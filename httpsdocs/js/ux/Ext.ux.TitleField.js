Ext.ns('CB');

Ext.ux.TitleField = Ext.extend(Ext.form.TwinTriggerField, {
    initComponent : function(){
        Ext.ux.TitleField.superclass.initComponent.apply(this);
        this.on('focus', function(f){ if(this.hasCustomValue) this.triggers[0].show(); else this.triggers[1].show(); }, this);
        this.on('blur', function(f){ if(f.getEl().isVisible(true)){ this.triggers[0].hide(); this.triggers[1].hide();} }, this);
    }
    ,afterRender: function() {
        Ext.ux.TitleField.superclass.afterRender.apply(this, arguments);
    }/**/

    ,validationEvent: false
    ,validateOnBlur: false
    ,editable: false
    ,trigger1Class: 'trigger-auto'
    ,trigger2Class: 'trigger-pencil-small'
    ,hideTrigger1: true
    ,hideTrigger2: true
    ,hasCustomValue: false
    ,defaultValue: 'auto'
    ,customValue: 'manual'
    
    ,onTrigger1Click : function(){//clear trigger = switch manual mode to auto
        this.setEditable(false)
        this.customValue = this.getValue();
        this.setValue(this.defaultValue);
        if(this.triggers){
            this.triggers[0].hide();
            this.triggers[1].show();
        }else{
            this.hideTrigger1 = true;
            this.hideTrigger2 = false;
        }
        this.hasCustomValue = false;
        this.addClass('title-readonly'); 
        this.focus();
    }

    ,onTrigger2Click : function(){//this trigger = switch auto to manual
        this.setEditable(true)
        this.setValue(Ext.value(this.customValue, (this.defaultValue == L.AutoGenerated) ? '' : this.defaultValue));
        this.hasCustomValue = true;
        if(this.triggers){
            this.triggers[0].show();
            this.triggers[1].hide();
        }else{
            this.hideTrigger1 = false;
            this.hideTrigger2 = true;
        }
        this.removeClass('title-readonly');
        this.focus(true);
    }
    
    ,setValues: function(defaultValue, customValue){
        this.defaultValue = defaultValue
        this.customValue = customValue
        this.hasCustomValue = !Ext.isEmpty(customValue);
        this.setValue(this.hasCustomValue ? customValue : defaultValue);
        this.setEditable(this.hasCustomValue ? true : false)
        
        if(this.hasCustomValue) this.removeClass('title-readonly'); else this.addClass('title-readonly');
    }
});

Ext.reg('ExtuxTitleField', Ext.ux.TitleField);