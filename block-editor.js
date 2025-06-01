( function( wp ) {
    // Wait for dependencies to be available
    if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components || !wp.data || !wp.serverSideRender) {
        console.error('GoalieTron Block: Required WordPress dependencies not loaded');
        return;
    }
    
    const { blocks, element, blockEditor, components, data, serverSideRender } = wp;
    const el = element.createElement;
    const { registerBlockType } = blocks;
    const { InspectorControls, useBlockProps } = blockEditor;
    const { PanelBody, TextControl, SelectControl, ToggleControl } = components;
    const { useState, useEffect } = element;
    const { useSelect } = data;
    const ServerSideRender = serverSideRender;

    // Get custom goals from the global variable if available
    const customGoals = window.goalietronCustomGoals || [];

    registerBlockType( 'goalietron/goalietron-block', {
        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();

            // Convert string booleans to actual booleans for ToggleControl
            const showGoalTextBool = attributes.showgoaltext === 'true';
            const showButtonBool = attributes.showbutton === 'true';

            // Build custom goals options
            const customGoalOptions = [
                { label: 'Select a custom goal...', value: '' }
            ];
            
            if ( Array.isArray( customGoals ) ) {
                customGoals.forEach( function( goal ) {
                    customGoalOptions.push({
                        label: goal.title + ' (' + goal.type + ': ' + goal.target + ')',
                        value: goal.id
                    });
                });
            }

            return el( 'div', blockProps,
                el( InspectorControls, {},
                    el( PanelBody, { title: 'GoalieTron Settings', initialOpen: true },
                        el( TextControl, {
                            label: 'Widget Title',
                            value: attributes.title,
                            onChange: function( value ) {
                                setAttributes( { title: value } );
                            }
                        }),
                        el( TextControl, {
                            label: 'Text Top',
                            value: attributes.toptext,
                            onChange: function( value ) {
                                setAttributes( { toptext: value } );
                            }
                        }),
                        el( TextControl, {
                            label: 'Text Bottom',
                            value: attributes.bottomtext,
                            onChange: function( value ) {
                                setAttributes( { bottomtext: value } );
                            }
                        }),
                        el( SelectControl, {
                            label: 'Design',
                            value: attributes.design,
                            options: [
                                { label: 'Default', value: 'default' },
                                { label: 'Fancy', value: 'fancy' },
                                { label: 'Minimal', value: 'minimal' },
                                { label: 'Streamlined', value: 'streamlined' },
                                { label: 'Reversed', value: 'reversed' },
                                { label: 'Swapped', value: 'swapped' }
                            ],
                            onChange: function( value ) {
                                setAttributes( { design: value } );
                            }
                        }),
                        el( SelectControl, {
                            label: 'Color',
                            value: attributes.metercolor,
                            options: [
                                { label: 'Red', value: 'red' },
                                { label: 'Green', value: 'green' },
                                { label: 'Orange', value: 'orange' },
                                { label: 'Blue', value: 'blue' },
                                { label: 'Red without stripes', value: 'red nostripes' },
                                { label: 'Green without stripes', value: 'green nostripes' },
                                { label: 'Orange without stripes', value: 'orange nostripes' },
                                { label: 'Blue without stripes', value: 'blue nostripes' }
                            ],
                            onChange: function( value ) {
                                setAttributes( { metercolor: value } );
                            }
                        }),
                        el( ToggleControl, {
                            label: 'Show goal description',
                            checked: showGoalTextBool,
                            onChange: function( value ) {
                                setAttributes( { showgoaltext: value ? 'true' : 'false' } );
                            }
                        }),
                        el( ToggleControl, {
                            label: '"Become a patron" button',
                            checked: showButtonBool,
                            onChange: function( value ) {
                                setAttributes( { showbutton: value ? 'true' : 'false' } );
                            }
                        })
                    ),
                    el( PanelBody, { title: 'Patreon Configuration', initialOpen: false },
                        el( SelectControl, {
                            label: 'Goal Mode',
                            value: attributes.goal_mode,
                            options: [
                                { label: 'Legacy API (requires user ID)', value: 'legacy' },
                                { label: 'Custom Goals (public data)', value: 'custom' }
                            ],
                            onChange: function( value ) {
                                setAttributes( { goal_mode: value } );
                            }
                        }),
                        attributes.goal_mode === 'legacy' && el( TextControl, {
                            label: 'Patreon User ID',
                            value: attributes.patreon_userid,
                            onChange: function( value ) {
                                setAttributes( { patreon_userid: value } );
                            },
                            help: 'Enter a Patreon user ID or username'
                        }),
                        attributes.goal_mode === 'custom' && el( 'div', {},
                            el( TextControl, {
                                label: 'Patreon Username',
                                value: attributes.patreon_username,
                                onChange: function( value ) {
                                    setAttributes( { patreon_username: value } );
                                },
                                placeholder: 'e.g. scishow'
                            }),
                            el( SelectControl, {
                                label: 'Custom Goal',
                                value: attributes.custom_goal_id,
                                options: customGoalOptions,
                                onChange: function( value ) {
                                    setAttributes( { custom_goal_id: value } );
                                },
                                help: customGoalOptions.length <= 1 ? 'Create custom goals using the CLI: php patreon-cli.php goal-add' : ''
                            })
                        )
                    )
                ),
                el( ServerSideRender, {
                    block: 'goalietron/goalietron-block',
                    attributes: attributes
                })
            );
        },
        save: function() {
            // Server-side rendered block
            return null;
        }
    });
}( window.wp ));