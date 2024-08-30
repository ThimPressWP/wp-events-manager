const path = require( 'path' );
const entries = require( 'webpack-glob-entries' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );
const { BundleAnalyzerPlugin } = require( 'webpack-bundle-analyzer' );
const DependencyExtractionWebpackPlugin = require( '@wordpress/dependency-extraction-webpack-plugin' );

const isProduction = process.env.NODE_ENV === 'production';

const webpack = require( 'webpack' );

module.exports = {
	...defaultConfig,
	entry: {
		//'course-review-v2': './assets/src/js/course-review-v2.js',
	},
	output: {
		filename: '[name]' + ( isProduction ? '.min.js' : '.js' ),
		path: path.resolve( __dirname, './assets/js' ),
	},
	plugins: [
		process.env.WP_BUNDLE_ANALYZER && new BundleAnalyzerPlugin(),

		// WP_NO_EXTERNALS global variable controls whether scripts' assets get
		// generated, and the default externals set.
		! process.env.WP_NO_EXTERNALS && new DependencyExtractionWebpackPlugin(),
	].filter( Boolean ),
};
