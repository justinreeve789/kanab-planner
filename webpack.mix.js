const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

// Explicit, so the build doesn't depend on finding ./artisan (the Docker assets stage has no PHP files).
mix.setPublicPath('public');

mix.options({
    uglify: {
        uglifyOptions: {
            compress: true,
            mangle: true,
            output: {
                comments: false,
                beautify: false
            }
        }
    }
});

mix.js('resources/js/app.js', 'public/js').version().vue();

mix.sass('resources/scss/app.scss', 'public/css').version();

//mix.copyDirectory('resources/fonts', 'public/fonts');
//mix.copyDirectory('resources/images', 'public/images');
