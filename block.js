/*
colomoat/expand block
version: 0.3
*/

const {registerBlockType} = wp.blocks; //Blocks API
const {__} = wp.i18n; //translation functions
const {InspectorControls} = wp.editor; //Block inspector wrapper
const {TextControl,SelectControl,ServerSideRender} = wp.components; //WordPress form inputs and server-side renderer
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
	title: __( 'Expand' ),
	category:  __( 'common' ),
	icon: iconEl,
	attributes:  {
		title : {
			default: '',
		},
		swaptitle : {
			default: '',
		},
		tag: {
			default: 'div',
		},
		id: {
			default: '',
		}
	},

	//display the post title
	edit(props){
		const attributes =  props.attributes;
		const setAttributes =  props.setAttributes;

		//Functions to update attributes
		function changeTitle(title){
			setAttributes({title});
		}

		function changeSwapTitle(swaptitle){
			setAttributes({swaptitle});
		}

		function changeTag(tag){
			setAttributes({tag});
		}

		function changeId(id){
			setAttributes({id});
		}

		//Display block preview and UI
		return el('div', {}, [

			//Preview Placeholder
			//el('div', {}, 'Hello from block edit callback' ),

			//Preview a block with a PHP render callback
			el( ServerSideRender, {
			    block: 'colomat/expand',
			    attributes: attributes
			} ),

			//Block Inspector
			el( InspectorControls, {},
				[
					el(TextControl, {
						value: attributes.title,
						label: __( 'Trigger Text', 'jquery-collapse-o-matic'),
						onChange: changeTitle,
					}),

					el(TextControl, {
						value: attributes.swaptitle,
						label: __( 'Swap Title', 'jquery-collapse-o-matic'),
						onChange: changeSwapTitle,
					}),

					el(TextControl, {
						value: attributes.tag,
						label: __( 'Tag', 'jquery-collapse-o-matic'),
						onChange: changeTag,
					}),

					el(TextControl, {
						value: attributes.id,
						label: __( 'ID' ),
						onChange: changeId,
					}),
				]
			)
		] )
	},
	save(){
		return null;//save has to exist. This all we need
	}
});
