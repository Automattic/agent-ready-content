export function errorMessage( error: unknown, fallback: string ): string {
	if (
		typeof error === 'object' &&
		error !== null &&
		'message' in error &&
		typeof error.message === 'string'
	) {
		return error.message;
	}
	return fallback;
}

/**
 * Read REST API titles and excerpts as text, removing tags and decoding entities.
 * @param html
 */
export function htmlToText( html: string ): string {
	const document = new DOMParser().parseFromString( html, 'text/html' );
	return document.body.textContent || '';
}

export const newId = () => `item-${ Date.now() }-${ Math.random().toString( 36 ).slice( 2, 8 ) }`;
