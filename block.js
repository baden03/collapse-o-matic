/*
colomoat/expand block
version: 0.4
*/

const {registerBlockType} = wp.blocks; //Blocks API
const {__} = wp.i18n; //translation functions
const {RichText,InnerBlocks,InspectorControls} = wp.editor;
const {PanelBody,TextControl,SelectControl,ToggleControl} = wp.components;
const el = wp.element.createElement;

const iconEl = el('svg', { width: 20, height: 20 },
  el('polygon', { points: "9.417,16.53 0,16.199 0,20 9.417,20" } ),
  el('polygon', { points: "16.53,9.334 16.2,0 20,0 20,9.334" } ),
  el('rect', {x: "2.634", y: "9.071", transform: "matrix(0.8016 0.5978 -0.5978 0.8016 7.1821 -2.5368)", width: "9.559", height: "0.964"} ),
  el('rect', {x: "0.923", y: "12.493", transform: "matrix(0.9466 0.3225 -0.3225 0.9466 4.4894 -1.146)", width: "9.559", height: "0.964"} ),
  el('rect', {x: "8.786", y: "4.592", transform: "matrix(0.3169 0.9485 -0.9485 0.3169 14.0799 -9.4015)", width: "9.559", height: "0.964"} ),
  el('rect', {x: "5.328", y: "6.333", transform: "matrix(0.5746 0.8185 -0.8185 0.5746 9.8786 -5.3739)", width: "9.559", height: "0.964"} ),
);

registerBlockType( 'colomat/expand', {
	title: __( 'Collapse-O-Matic' ),
    description: __( 'Advanced accordion block.', 'collapse-o-matic' ),
	category:  __( 'common' ),
    keywords: [
		__( 'accordion', 'collapse-o-matic' ),
		__( 'expand', 'collapse-o-matic' ),
		__( 'collapse', 'collapse-o-matic' ),
	],
	icon: iconEl,
	attributes:  {
        title: {
    		type: 'array',
    		selector: '.collapseomatic',
    		source: 'children',
    	},
        content: {
    		type: 'array',
    		selector: '.collapseomatic_content',
    		source: 'children',
    	},
		swaptitle : {
			type: 'string',
		},
        trigclass : {
			type: 'string',
            default: colomat['trigclass']
		},
		tag: {
			type: 'string',
            default: colomat['tag']
		},
		id: {
			type: 'string',
		},
        expanded: {
            type: 'boolean',
    		default: false
        },
        alt: {
			type: 'string',
		},
        swapalt: {
			type: 'string',
		},
        targtag: {
			type: 'string',
            default: colomat['targtag']
		},
        targclass: {
			type: 'string',
            default: colomat['targclass']
		},
        trigpos: {
			type: 'string',
            default: colomat['trigpos']
		},
        targpos: {
			type: 'string',
            default: ''
		},
        group: {
			type: 'string',
		},
        highlander: {
            type: 'boolean',
    		default: false
        },
        togglegroup: {
			type: 'string',
		},
        /*
        excerpt: {
			type: 'string',
		},
        swapexcerpt: {
			type: 'string',
		},
        excerptpos: {
			type: 'string',
            default: 'below-trigger'
		},
        excerpttag: {
			type: 'string',
            default: colomat['excerpttag']
		},
        excerptclass: {
			type: 'string',
            default: colomat['excerptclass']
		},
        findme: {
			type: 'string',
		},
        scrollonclose: {
			type: 'string',
		},
        content: children( 'p' ),
        */
	},

	edit(props){
		const attributes =  props.attributes;
		const setAttributes =  props.setAttributes;

		//Functions to update attributes
        function changeId(id){
			setAttributes({id});
		}

        function changeExpanded(expanded){
            setAttributes({expanded});
        }

        function changeGroup(group){
            setAttributes({group});
        }

        function changeHighlander(highlander){
            setAttributes({highlander});
        }

        function changeExpanded(expanded){
            setAttributes({expanded});
        }

		function changeTitle(title){
			setAttributes({title});
		}

		function changeSwapTitle(swaptitle){
			setAttributes({swaptitle});
		}

        function changeTrigClass(trigclass){
			setAttributes({trigclass});
		}

		function changeTag(tag){
			setAttributes({tag});
		}

        function changeContent(content){
			setAttributes({content});
		}

        function changeTrigPos(trigpos){
            setAttributes({trigpos});
        }

        function changeTargTag(targtag){
            setAttributes({targtag});
        }

        function changeTargPos(targpos){
            setAttributes({targpos});
        }

		//Display block preview and UI
		return el('div', {}, [

			//Preview a block with a PHP render callback
            /*
			el( ServerSideRender, {
			    block: 'colomat/expand',
			    attributes: attributes
			} ),
            */

            //el('div', {}, 'This is a placeholder from block edit callback' ),

            el(RichText, {
                value: attributes.title,
                tagName: attributes.tag,
                placeholder: __( 'Trigger Text', 'collapse-o-matic' ),
                className: 'collapseomatic',
                onChange: changeTitle
            }),

            el(InnerBlocks, {
                value: attributes.content,
                tagName: attributes.targtag,
                placeholder: __( 'Target Content', 'collapse-o-matic' ),
                className: 'collapseomatic_content',
                onChange: changeContent
            }),

			//Block Inspector
			el( InspectorControls, {},
				[

                    el(ToggleControl, {
						checked: attributes.expanded,
						label: __( 'Open by default', 'collapse-o-matic' ),
						onChange: changeExpanded,
					}),

                    el(TextControl, {
						value: attributes.group,
						label: __( 'Group ID' ),
						onChange: changeGroup,
					}),

                    el(ToggleControl, {
						checked: attributes.highlander,
						label: __( 'Use Highlander Grouping', 'collapse-o-matic' ),
                        help: __( 'There can only be one group element expanded at a time.', 'collapse-o-matic' ),
						onChange: changeHighlander,
					}),

                    //Trigger Attributes
                    el(PanelBody, {
                        title: __('Trigger Attributes'),
                        initialOpen: true,
                    },
                        [
                            el(TextControl, {
        						value: attributes.id,
        						label: __( 'Expand ID' ),
        						onChange: changeId,
        					}),

                            el(TextControl, {
        						value: attributes.tag,
        						label: __( 'Trigger Element', 'collapse-o-matic'),
                                help: __('HTML tag to use for the trigger.'),
        						onChange: changeTag,
        					}),

                            el(TextControl, {
        						value: attributes.swaptitle,
        						label: __( 'Swap Title', 'collapse-o-matic'),
                                //help: __( 'Trigger text to display while expanded.', 'collapse-o-matic'),
        						onChange: changeSwapTitle,
        					}),

                            el(TextControl, {
        						value: attributes.trigclass,
        						label: __( 'Trigger Class' ),
        						onChange: changeTrigClass,
        					}),

                            el(SelectControl, {
        						value: attributes.trigpos,
        						label: __( 'Trigger Position' ),
        						onChange: changeTrigPos,
        						options: [
        							{value: 'above-target', label: __('Above Target') },
        							{value: 'below-target', label: __('Below Target') },
        						]
        					})
                        ]
                    ),

                    el(PanelBody, {
                        title: __('Target Attributes'),
                        initialOpen: false,
                    },
                        [
                            el(TextControl, {
        						value: attributes.targtag,
        						label: __( 'Target Element', 'collapse-o-matic'),
                                help: __('HTML tag to use for the target.'),
        						onChange: changeTargTag,
        					}),

                            el(SelectControl, {
        						value: attributes.targpos,
        						label: __( 'Target Position' ),
        						onChange: changeTargPos,
        						options: [
        							{value: '', label: __('Block') },
        							{value: 'inline', label: __('Inline') },
        						]
        					}),

                        ]
                    ),
				]
			)
		] )
	},
    /*
	save(){
		return null;//save has to exist. This all we need
	}
    */
    save: function( props ) {
			var attributes = props.attributes;

            if(!attributes.id){
                var S4 = function() {
                   return (((1+Math.random())*0x10000)|0).toString(16).substring(1);
                };
                attributes.id = 'id' + (S4()+S4()+S4()+S4()+S4());
            }

            var expanded = '';
            if(attributes.expanded){
                 expanded = ' colomat-close';
            }

            var groupname = '';
            if(attributes.group){
                groupname = attributes.group;
                if(attributes.highlander){
                    groupname = attributes.group + '-highlander';
                }
            }

			return (
				el( 'div', { className: props.className },
					el( RichText.Content, {
						tagName: attributes.tag,
                        id: attributes.id,
                        rel: groupname,
                        swaptitle: attributes.swaptitle,
                        className: 'collapseomatic ' + attributes.trigclass + expanded,
                        value: attributes.title
					} ),
                    el( attributes.targtag, {
                            className: 'collapseomatic_content ' + attributes.targclass,
                            id: 'target-' + attributes.id
                        },
                        el( InnerBlocks.Content, {
                            value: attributes.content
    					} ),
                    )
				)
			);
	},
});
