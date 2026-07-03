declare module '@wordpress/blocks' {
	export function registerBlockType( name: string, settings: Record< string, unknown > ): void;
}

declare module '@wordpress/block-editor' {
	export const InspectorControls: React.ComponentType< { children?: React.ReactNode } >;
}

declare module '@wordpress/components' {
	export const PanelBody: React.ComponentType< {
		title: string;
		children?: React.ReactNode;
	}>;
	export const ToggleControl: React.ComponentType< {
		label: string;
		checked: boolean;
		onChange: ( value: boolean ) => void;
	}>;
	export const RangeControl: React.ComponentType< {
		label: string;
		value: number;
		onChange: ( value: number ) => void;
		min: number;
		max: number;
	}>;
}

declare module '@wordpress/i18n' {
	export function __( text: string, domain?: string ): string;
}

declare module '*.json' {
	const value: Record< string, unknown >;
	export default value;
}
