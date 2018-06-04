const CopyWebpackPlugin = require('copy-webpack-plugin')
const HtmlWebPackPlugin = require('html-webpack-plugin');
const CleanWebPackPlugin = require('clean-webpack-plugin');
const CleanObsoleteChunks = require('webpack-clean-obsolete-chunks');
const path = require('path');

const publicPath = path.resolve('../public');

module.exports = {
    entry: {
        main: './src/index.js'
    },
    output: {
        filename: '[name].js',
        path: publicPath
    },
    plugins: [
        new CleanWebPackPlugin([publicPath], { allowExternal: true }),
        new HtmlWebPackPlugin({ template: 'index.html' }),
        new CleanObsoleteChunks(),
        new CopyWebpackPlugin([
            {
                from: './node_modules/jquery/dist/jquery.min.js',
                to: publicPath + '/js/'
            },
            {
                from: './node_modules/popper.js/dist/umd/popper.min.js',
                to: publicPath + '/js/'
            },
            {
                from: './node_modules/bootstrap/dist/js/bootstrap.min.js',
                to: publicPath + '/js/'
            },
            {
                from: './node_modules/bootstrap/dist/css/bootstrap.min.css',
                to: publicPath + '/css/'
            },
            {
                from: './node_modules/font-awesome/css/font-awesome.min.css',
                to: publicPath + '/css/'
            },
            {
                from: './node_modules/font-awesome/fonts',
                to: publicPath + '/fonts'
            },
            {
                from: './bower_components/toastr/toastr.min.js',
                to: publicPath + '/js/'
            },
            {
                from: './bower_components/toastr/toastr.min.css',
                to: publicPath + '/css/'
            },
            {
                from: './node_modules/nprogress/nprogress.js',
                to: publicPath + '/js/'
            },
            {
                from: './node_modules/nprogress/nprogress.css',
                to: publicPath + '/css/'
            }
        ])
    ],
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: [ 'node_modules' ],
                use: [ { loader: 'babel-loader' } ]
            },
            {
                test: /\.css$/,
                use: [
                    { loader: 'style-loader' },
                    { loader: 'css-loader' }
                ]
            }
        ]
    }
};
