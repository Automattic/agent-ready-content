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
 * Convert a WordPress REST API `rendered` field to text without inserting its HTML into the page.
 *
 * @param html The rendered HTML returned by the WordPress REST API.
 */
export function renderedHtmlToText( html: string ): string {
	const parsedDocument = new DOMParser().parseFromString( html, 'text/html' );
	return parsedDocument.body.textContent?.trim() ?? '';
}

export const newId = () => `item-${ Date.now() }-${ Math.random().toString( 36 ).slice( 2, 8 ) }`;
