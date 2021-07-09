/**
 * Internal block libraries
 */

import throttle from 'lodash.throttle';

const { Component } = wp.element;
const {
    InspectorControls,
    ColorPalette,
} = wp.editor;
const {
    Button,
    ButtonGroup,
    CheckboxControl,
    PanelBody,
    PanelRow,
    PanelColor,
    RadioControl,
    RangeControl,
    TextControl,
    TextareaControl,
    ToggleControl,
    Toolbar,
    SelectControl
} = wp.components;

/**
 * Create an Inspector Controls wrapper Component
 */
export default class Inspector extends Component {

    constructor() {
        super( ...arguments );
        this.updateApiKey = this.updateApiKey.bind(this);  
        this.updateApiKeyThrottled = throttle(this.updateApiKey, 3000);
        this.updateListThrottled = throttle(this.updateList, 3000);
    }
    
    updateApiKey(key) {
        wf_mailchimp_block.api_key = key;
        jQuery('.wf-mailchimp-lists').parent().append('<div class="wf-mailchimp-block-loader-wrapper"><div class="wf-mailchimp-block-loader"></div></div>');
        jQuery('.wf-mailchimp-lists').hide();
        fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body:"action=wf_mailchimp_block_save_key&api_key="+key
        }).then(function(response) {
            return response.json();
        }).then(function(json) { 
            if(json.success){                   
                jQuery('.wf-mailchimp-lists').empty();
                var lists_count = 0;                
                jQuery.each(json.data, function(value,key) {            
                    jQuery('.wf-mailchimp-lists').append($("<option></option>").attr("value", value).text(key));
                    lists_count++;
                });
                if(lists_count == 0){
                  jQuery('.wf-mailchimp-lists').append($("<option></option>").attr("value", '0').text('No Lists Found - Check API Key')); 
                }
                jQuery('.wf-mailchimp-block-loader-wrapper').remove();
                jQuery('.wf-mailchimp-lists').show();
            } else {
                jQuery('.wf-mailchimp-lists').empty();
                jQuery('.wf-mailchimp-lists').append($("<option></option>").attr("value", '0').text('No Lists Found - Check API Key'));
                jQuery('.wf-mailchimp-block-loader-wrapper').remove();
                jQuery('.wf-mailchimp-lists').show();
            }
        });
    }

    updateList(mc_list) {
        wf_mailchimp_block.mc_list = mc_list;        
        fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body:"action=wf_mailchimp_block_save_list&list="+mc_list
        });
    }

    render() {
        const { attributes: { form_style, mc_list, email_field_label, name_field_label, submit_field_label, success_message, error_message, submit_message, duplicate_message, api_key }, setAttributes } = this.props;

        return (
            <InspectorControls>
                <PanelBody>
                    <TextControl
                        label={ wf_mailchimp_block._api_key }
                        help={ <p>{wf_mailchimp_block._api_info_start} <a href="https://us2.admin.mailchimp.com/account/api/" target="_blank">{wf_mailchimp_block._api_info_console}</a>. {wf_mailchimp_block._api_info_end}</p> }
                        value={ api_key }
                        onChange={ api_key => { 
                            if(!api_key){
                                api_key = '';
                            }
                            setAttributes( { api_key } ); 
                            this.updateApiKeyThrottled( api_key ); 
                        } }
                    />
                </PanelBody>
                
                <PanelBody>
                    <SelectControl class="wf-mailchimp-lists"
                        label={ wf_mailchimp_block._mc_list }
                        value={ wf_mailchimp_block.mc_list }
                        options={ wf_mailchimp_block.mc_lists }
                        onChange={ mc_list => { 
                            if(!mc_list){
                                mc_list = '';
                            }
                            setAttributes( { mc_list } ); 
                            this.updateListThrottled( mc_list ); 
                        } }
                    />
                </PanelBody>
                
                <PanelBody>
                    <SelectControl class="wf-mailchimp-form-style"
                        label={ wf_mailchimp_block._form_css }
                        value={ 0 }
                        options={ [{value:0,label:'Theme default'},{value:-1,label:'Minimal - coming soon'},{value:-1,label:'Material Design - coming soon'},{value:-1,label:'Round - coming soon'},{value:-1,label:'Square - coming soon'}] }
                        onChange={ form_css => { } }
                    />
                </PanelBody>

                <PanelBody>
                    <TextControl
                        label={ wf_mailchimp_block._email_field_label }
                        value={ email_field_label }
                        onChange={ email_field_label => setAttributes( { email_field_label } ) }
                    />
                </PanelBody>

                <PanelBody>
                    <TextControl
                        label={ wf_mailchimp_block._name_field_label }
                        value={ name_field_label }
                        onChange={ name_field_label => setAttributes( { name_field_label } ) }
                    />
                </PanelBody>

                <PanelBody>
                    <TextControl
                        label={ wf_mailchimp_block._submit_field_label }
                        value={ submit_field_label }
                        onChange={ submit_field_label => setAttributes( { submit_field_label } ) }
                    />
                </PanelBody>

                <PanelBody>
                    <TextControl
                        label={ wf_mailchimp_block._success_message }
                        value={ success_message }
                        onChange={ success_message => setAttributes( { success_message } ) }
                    />
                </PanelBody>
                
                <PanelBody>
                    <TextControl
                        label={ wf_mailchimp_block._error_message }
                        value={ error_message }
                        onChange={ error_message => setAttributes( { error_message } ) }
                    />
                </PanelBody>
                        
                <PanelBody>
                    <TextControl
                        label={ wf_mailchimp_block._submit_message }
                        value={ submit_message }
                        onChange={ submit_message => setAttributes( { submit_message } ) }
                    />
                </PanelBody>

                <PanelBody>
                    <TextControl
                        label={ wf_mailchimp_block._duplicate_message }
                        value={ duplicate_message }
                        onChange={ duplicate_message => setAttributes( { duplicate_message } ) }
                    />
                </PanelBody>
                
                
            </InspectorControls>
        );
    }
}
