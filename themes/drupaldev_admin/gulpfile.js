'use strict';

const { gulp, src, dest, watch, parallel, series } = require("gulp");
const sass = require('gulp-sass')(require('sass'));
const sourcemaps = require('gulp-sourcemaps');
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');

function generateCss(callback) {
  src(['src/scss/**/*.scss' ])
    .pipe(sass().on('error', sass.logError))
    .pipe(postcss())
    .pipe(sourcemaps.write('.'))
    .pipe(dest(['dist/css']));
  callback();
};

function watchFiles(callback) {
  watch('src/scss/**/*.scss',parallel( generateCss));
  watch('templates/**/*.html.twig',parallel( generateCss));
  callback();
}


exports.css = parallel(generateCss);
exports.watch = watchFiles;
exports.default = parallel(watchFiles, generateCss);
