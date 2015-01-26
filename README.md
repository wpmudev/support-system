#Starting with the project

Install nodejs
Install NPM
Install Git
Install Sass (3.3)
Install Compass (1.0.1)



`sudo npm install bower gulp gulp-bower gulp-compass gulp-minify-css del gulp-uglify gulp-rename`

First install: Will donwload Foundation framework and init
gulp install
gulp init

Next:
`gulp watch`

Now just change sass rules in assets/scss/_settings.scss (see Foundation docs)
or assets/scss/incsub-support.scss. The styles will be compiled in assets/css

## Before releasing
This will minify the CSS and JS

`gulp release`

Use git-archive-all to make the zip file.




