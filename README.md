# Starting with the project for the first time

1. Install [nodejs](https://nodejs.org/download/)
2. Install [npm](https://github.com/npm/npm)
3. Install [Sass](http://sass-lang.com/install) (3.2 is recommended)
4. Install [Compass](http://compass-style.org/install/) (1.0.1 is recommended)
5. `npm install` (in the plugin root folder) will download all Dev dependencies to node_modules folder
6. npm should have installed bins in node_modules/.bin but sometimes `bower --version` or `gulp --version` will not work. You have several options and I don't like any of them but you can use the following command to add the .bin folder to your $PATH global variable (in the plugin root folder):
`PATH=$(npm bin):$PATH` **This will be reset when you close your terminal so you'll need to do it again when you reopen it**.
7. Execute `gulp install`. This will execute Bower and download Foundation dependencies in bower_components and move some files to assets.


# Working on the project
## Working with Sass files
Use `compass watch` when you want to change Sass files (in assets/scss). This will compile CSS into assets/css/incsub-support.css (unminified)

You might only need to change **settings.scss** and **incsub-support.scss** files.

## Working with JS files
**assets/js/foundation.js** is the main JS file. You can play with it but bear in mind that by default only support-system.min.js will be enqueued.
To avoid this while developing set `define('SCRIPT_DEBUG', true);` in your wp-config.php file while you're developing. Unminifed JS files will be loaded instead.


# Before releasing
1. `gulp release` will minify the CSS and JS.
2. `npm run build` will create a package in build folder. Make sure that your package.json `pluginVersion` matches with the plugin version in main file.





