'use strict';

const { series } = require('gulp');
const { task } = require('gulp');

const gulp = require('gulp');
const sass = require('gulp-sass');
const scsslint = require('gulp-scss-lint');

sass.compiler = require('node-sass');

gulp.task('sass', function () {
  return gulp.src('./sass/**/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest('./css'));
});

gulp.task('scss-lint', function() {
  return gulp.src('/scss/*.scss')
    .pipe(scsslint());
});

gulp.task('sass:watch', function () {
  gulp.watch('./sass/**/*.scss', ['sass', 'scss-lint']);
});
