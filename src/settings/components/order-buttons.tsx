import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';

export default function OrderButtons< T >( {
	items,
	index,
	onChange,
	label,
}: {
	items: T[];
	index: number;
	onChange: ( items: T[] ) => void;
	label: string;
} ) {
	const move = ( offset: number ) => {
		const next = [ ...items ];
		[ next[ index ], next[ index + offset ] ] = [ next[ index + offset ], next[ index ] ];
		onChange( next );
	};
	return (
		<div className="arc-actions">
			<Button
				variant="secondary"
				disabled={ index === 0 }
				onClick={ () => move( -1 ) }
				aria-label={ sprintf(
					/* translators: %s: Item title. */
					__( 'Move %s up', 'agent-ready-content' ),
					label
				) }
			>
				{ __( 'Move up', 'agent-ready-content' ) }
			</Button>
			<Button
				variant="secondary"
				disabled={ index === items.length - 1 }
				onClick={ () => move( 1 ) }
				aria-label={ sprintf(
					/* translators: %s: Item title. */
					__( 'Move %s down', 'agent-ready-content' ),
					label
				) }
			>
				{ __( 'Move down', 'agent-ready-content' ) }
			</Button>
			<Button
				variant="tertiary"
				isDestructive
				onClick={ () => onChange( items.filter( ( _, i ) => i !== index ) ) }
				aria-label={ sprintf(
					/* translators: %s: Item title. */
					__( 'Remove %s', 'agent-ready-content' ),
					label
				) }
			>
				{ __( 'Remove', 'agent-ready-content' ) }
			</Button>
		</div>
	);
}
