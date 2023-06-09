'use strict';

const pkg = require('./package.json');
const uglify = require("gulp-uglify-es").default;
// const concat = require("gulp-concat");
const sassLint = require("gulp-sass-lint");
const jsYaml = require('js-yaml');
const fs = require('fs');

const {dest, series, src, task, watch} = require('gulp');

require('require-dir')('./gulp-tasks');

// Load all plugins in devDependencies.
const $ = require('gulp-load-plugins')({
  pattern: ['*'],
  scope: ['devDependencies'],
  rename: {
    'plumber': 'gulp-plumber',
    'glob': 'gulp-glob',
    'sourcemaps': 'gulp-sourcemaps',
    'imagemin': 'gulp-imagemin',
    'gulp-postcss': 'postcss',
    'gulp-sass-glob': 'glob',
    'gulp-shell': 'shell',
    postcss: 'postcss-lib',
  },
});
$.sass.compiler = require('node-sass');

// Logs error messages.
const onError = (err) => {
  console.log(err);
};

// Clean CSS and style guide files.
task('clean', () => {
  return $.del(['./css/*', './styleguide/*', '.js/*']);
});

// Compile Sass files.
task('scss', () => {
  return $.pipe(src(pkg.paths.scss), [
    $.plumber(),
    $.glob(),
    $.sourcemaps.init({loadMaps: true}),
    $.sass.sync({
      includePaths: [pkg.paths.scss],
    }).on('error', $.sass.logError),
    $.cached('sass_compiled'),
    $.postcss([$.autoprefixer()]),
    $.sourcemaps.write('./'),
    dest(pkg.paths.dist.css),
  ]);
});

// Lint Sass files.
task('scss-lint', () => {
  const configFile = jsYaml.load(fs.readFileSync('.sass-lint.yml', 'utf-8'));
  return $.pipe(src(pkg.paths.scss), [
    $.plumber({errorHandler: onError}),
    sassLint(configFile),
    sassLint.format(),
  ]);
});

// Generate living style guide.
task('styleguide', $.shell.task(['./node_modules/kss/bin/kss --config ./kss-config.json']));

// Build CSS files.
task(
  'css',
  series('scss', () => {
    return $.pipe(src(pkg.paths.dist.css + '**/*.css'), [
      $.plumber({ errorHandler: onError }),
      $.sourcemaps.init({ loadMaps: true }),
      $.postcss([$.cssnano({ preset: 'default' })]),
      $.rename({ suffix: '.min' }),
      $.sourcemaps.write('./'),
      dest(pkg.paths.dist.css),
    ]);
  })
);

// Lint JavaScript files.
task('js-lint', () => {
  return $.pipe(src(pkg.paths.js), [$.plumber({errorHandler: onError}), $.eslint(), $.eslint.format('table')]);
});

// Build JS files.
task("js", () => {
  return $.pipe(src(pkg.paths.js), [
    $.sourcemaps.init({largeFile: true}),
    uglify(),
    $.sourcemaps.write(),
    dest(pkg.paths.dist.js)
  ]);
});

// Minify image assets.
task('image-min', () => {
  return $.pipe(src(pkg.paths.dist.img + '**/*.{png,jpg,jpeg,gif,svg}'), [
    $.imagemin({
      progressive: true,
      interlaced: true,
      optimizationLevel: 7,
      svgoPlugins: [{removeViewBox: false}],
      verbose: true,
      use: [],
    }),
    dest(pkg.paths.dist.img),
  ]);
});

// Default build task.
task('default', series(['clean', 'scss-lint', 'css', 'js-lint', 'js']));

// Watch task.
task(
  'watch',
  series(['clean', 'scss-lint', 'css', 'js-lint', 'js'], () => {
    watch(pkg.paths.scss, series(['clean', 'scss-lint', 'css']));
    watch(pkg.paths.js, series(['js-lint', 'js']));
  })
);

// Lint task.
task('lint', series(['js-lint', 'scss-lint']));

