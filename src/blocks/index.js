/**
 * Block dependencies
 */


import classnames from 'classnames';
import Inspector from './inspector';
import Controls from './controls';

const { __ } = wp.i18n;
const {
    registerBlockType,
} = wp.blocks;
const {
    RichText,
} = wp.editor;


function getSettings( attributes ) {
    let settings = [];
    for( let attribute in attributes ) {
        let value = attributes[ attribute ];
        if( 'boolean' === typeof attributes[ attribute ] ) {
            value = value.toString();
        }
        settings.push( <li>{ attribute }: { value }</li> );
    }
    return settings;
}

function buildMailChimpForm( attributes ) {
    return <div class="wp-block-webfactory-mailchimp" data-success={attributes.success_message} data-error={attributes.error_message} data-submit={attributes.submit_message} data-duplicate={attributes.duplicate_message}>
        <input type="text" class="wf-mc-name" name="wf-mc-name" value="" placeholder={attributes.name_field_label} />
        <input type="text" class="wf-mc-email" name="wf-mc-email" value="" placeholder={attributes.email_field_label} />
        <input type="submit" class="wf-mc-submit" name="wf-mc-submit" value={attributes.submit_field_label} />
    </div>
} // buildMapIframe

/**
 * Register static block example block
 */
export default registerBlockType(
    'webfactory/mailchimp',
    {
        title: wf_mailchimp_block._mailchimp,
        description: wf_mailchimp_block._description,
        category: "common",
        icon: 'email-alt',
        keywords: [
            'mailchimp',
            'newsletter',
            'subscribe'
        ],
        attributes: {
            form_css: {
                type: 'string',
                default: '',
            },
            api_key: {
                type: 'string',
                default: wf_mailchimp_block.api_key,
            },
            mc_list: {
                type: 'string',
                default: '',
            },
            email_field_label: {
                type: 'string',
                default: 'Your best email address',
            },
            name_field_label: {
                type: 'string',
                default: 'Your name',
            },
            submit_field_label: {
                type: 'string',
                default: 'Subscribe',
            },
            success_message: {
                type: 'string',
                default: 'Thank you for subscribing',
            },
            error_message: {
                type: 'string',
                default: 'Sorry, something is not right. Reload the page and try again.',
            },
            submit_message: {
                type: 'string',
                default: 'Please fill out all fields with proper values',
            },
            duplicate_message: {
                type: 'string',
                default: 'You have already subscribed to our list',
            }
        },
        edit: props => {
            const { attributes: { message },
                attributes, className, setAttributes } = props;

            let maphtml = buildMailChimpForm( attributes );

            return [
                <Inspector { ...{ setAttributes, ...props} } />,
                <div>
                    { maphtml }
                </div>
            ];
        },
        save: props => {
            const { attributes } = props;

            let maphtml = buildMailChimpForm( attributes );

            return(
                <div>
                    { maphtml }
                </div>
            );
        },
    },
);
