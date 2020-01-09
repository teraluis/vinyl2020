/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var gulp = require('gulp');
var sass = require('gulp-sass');
var minifyCSS = require('gulp-csso');
var rename = require('gulp-rename');
gulp.task('sass', function () {
    return gulp.src('wp-content/themes/skudmart/sass/style.scss')
        .pipe(sass())
        .pipe(gulp.dest('wp-content/themes/skudmart'));
});
gulp.task('minify',function(){
   return gulp.src('wp-content/themes/skudmart/style.css')
           .pipe(minifyCSS())
            .pipe(rename({
                suffix: '.min'
            }))   
           .pipe(gulp.dest('wp-content/themes/skudmart/'));
});
gulp.task('default', function(){
   gulp.watch('wp-content/themes/skudmart/sass/style.scss',['sass']);
});