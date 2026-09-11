const config = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...config,
	entry: { index: path.resolve( __dirname, 'src/settings/index.tsx' ) },
	output: { ...config.output, path: path.resolve( __dirname, 'build/settings' ) },
};
