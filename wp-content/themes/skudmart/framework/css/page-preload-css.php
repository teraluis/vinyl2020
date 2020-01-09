<?php
/**
 * This file includes dynamic css
 *
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>
.lds-ripple {
    display: inline-block;
    position: relative;
    width: 64px;
    height: 64px;
}
.lds-ripple div {
    position: absolute;
    border: 4px solid #fff;
    opacity: 1;
    border-radius: 50%;
    animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
}
.lds-ripple div:nth-child(2) {
    animation-delay: -0.5s;
}
@keyframes lds-ripple {
    0% {
        top: 28px;
        left: 28px;
        width: 0;
        height: 0;
        opacity: 1;
    }
    100% {
        top: -1px;
        left: -1px;
        width: 58px;
        height: 58px;
        opacity: 0;
    }
}

.site-loading .la-image-loading {
    opacity: 1;
    visibility: visible
}
.la-image-loading.spinner-custom .content {
    width: 100px;
    margin-top: -50px;
    height: 100px;
    margin-left: -50px;
    text-align: center
}
.la-image-loading.spinner-custom .content img {
    width: auto;
    margin: 0 auto
}
.site-loading #outer-wrap.site {
    opacity: 0;
    transition: all .3s ease-in-out
}
#outer-wrap.site {
    opacity: 1
}
.la-image-loading {
    opacity: 0;
    position: fixed;
    z-index: 999999;
    left: 0;
    top: 0;
    right: 0;
    bottom: 0;
    background: #fff;
    overflow: hidden;
    transition: all .3s ease-in-out;
    -webkit-transition: all .3s ease-in-out;
    visibility: hidden;
}
.la-image-loading .content {
    position: absolute;
    width: 50px;
    height: 50px;
    top: 50%;
    left: 50%;
    margin-left: -25px;
    margin-top: -25px;
}
.la-loader.spinner1 {
    width: 40px;
    height: 40px;
    margin: 5px;
    display: block;
    box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.15);
    -webkit-box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.15);
    -webkit-animation: la-rotateplane 1.2s infinite ease-in-out;
    animation: la-rotateplane 1.2s infinite ease-in-out;
    border-radius: 3px;
    -moz-border-radius: 3px;
    -webkit-border-radius: 3px;
}
.la-loader.spinner2 {
    width: 40px;
    height: 40px;
    margin: 5px;
    box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.15);
    -webkit-box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.15);
    border-radius: 100%;
    -webkit-animation: la-scaleout 1.0s infinite ease-in-out;
    animation: la-scaleout 1.0s infinite ease-in-out;
}
.la-loader.spinner3 {
    margin: 15px 0 0 -10px;
    width: 70px;
    text-align: center;
}
.la-loader.spinner3 [class*="bounce"] {
    width: 18px;
    height: 18px;
    box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.15);
    -webkit-box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.15);
    border-radius: 100%;
    display: inline-block;
    -webkit-animation: la-bouncedelay 1.4s infinite ease-in-out;
    animation: la-bouncedelay 1.4s infinite ease-in-out;
    -webkit-animation-fill-mode: both;
    animation-fill-mode: both;
}
.la-loader.spinner3 .bounce1 {
    -webkit-animation-delay: -.32s;
    animation-delay: -.32s;
}
.la-loader.spinner3 .bounce2 {
    -webkit-animation-delay: -.16s;
    animation-delay: -.16s;
}
.la-loader.spinner4 {
    margin: 5px;
    width: 40px;
    height: 40px;
    text-align: center;
    -webkit-animation: la-rotate 2.0s infinite linear;
    animation: la-rotate 2.0s infinite linear;
}
.la-loader.spinner4 [class*="dot"] {
    width: 60%;
    height: 60%;
    display: inline-block;
    position: absolute;
    top: 0;
    border-radius: 100%;
    -webkit-animation: la-bounce 2.0s infinite ease-in-out;
    animation: la-bounce 2.0s infinite ease-in-out;
    box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.15);
    -webkit-box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.15);
}
.la-loader.spinner4 .dot2 {
    top: auto;
    bottom: 0;
    -webkit-animation-delay: -1.0s;
    animation-delay: -1.0s;
}
.la-loader.spinner5 {
    margin: 5px;
    width: 40px;
    height: 40px;
}
.la-loader.spinner5 div {
    width: 33%;
    height: 33%;
    float: left;
    -webkit-animation: la-cubeGridScaleDelay 1.3s infinite ease-in-out;
    animation: la-cubeGridScaleDelay 1.3s infinite ease-in-out;
}
.la-loader.spinner5 div:nth-child(1), .la-loader.spinner5 div:nth-child(5), .la-loader.spinner5 div:nth-child(9) {
    -webkit-animation-delay: .2s;
    animation-delay: .2s;
}
.la-loader.spinner5 div:nth-child(2), .la-loader.spinner5 div:nth-child(6) {
    -webkit-animation-delay: .3s;
    animation-delay: .3s;
}
.la-loader.spinner5 div:nth-child(3) {
    -webkit-animation-delay: .4s;
    animation-delay: .4s;
}
.la-loader.spinner5 div:nth-child(4), .la-loader.spinner5 div:nth-child(8) {
    -webkit-animation-delay: .1s;
    animation-delay: .1s;
}
.la-loader.spinner5 div:nth-child(7) {
    -webkit-animation-delay: 0s;
    animation-delay: 0s;
}
@-webkit-keyframes la-rotateplane {
    0% {
        -webkit-transform: perspective(120px);
    }
    50% {
        -webkit-transform: perspective(120px) rotateY(180deg);
    }
    100% {
        -webkit-transform: perspective(120px) rotateY(180deg) rotateX(180deg);
    }
}
@keyframes la-rotateplane {
    0% {
        transform: perspective(120px) rotateX(0deg) rotateY(0deg);
    }
    50% {
        transform: perspective(120px) rotateX(-180.1deg) rotateY(0deg);
    }
    100% {
        transform: perspective(120px) rotateX(-180deg) rotateY(-179.9deg);
    }
}
@-webkit-keyframes la-scaleout {
    0% {
        -webkit-transform: scale(0);
    }
    100% {
        -webkit-transform: scale(1);
        opacity: 0;
    }
}
@keyframes la-scaleout {
    0% {
        transform: scale(0);
        -webkit-transform: scale(0);
    }
    100% {
        transform: scale(1);
        -webkit-transform: scale(1);
        opacity: 0;
    }
}
@-webkit-keyframes la-bouncedelay {
    0%, 80%, 100% {
        -webkit-transform: scale(0);
    }
    40% {
        -webkit-transform: scale(1);
    }
}
@keyframes la-bouncedelay {
    0%, 80%, 100% {
        transform: scale(0);
    }
    40% {
        transform: scale(1);
    }
}
@-webkit-keyframes la-rotate {
    100% {
        -webkit-transform: rotate(360deg);
    }
}
@keyframes la-rotate {
    100% {
        transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
    }
}

@-webkit-keyframes la-bounce {
    0%, 100% {
        -webkit-transform: scale(0);
    }
    50% {
        -webkit-transform: scale(1);
    }
}

@keyframes la-bounce {
    0%, 100% {
        transform: scale(0);
    }
    50% {
        transform: scale(1);
    }
}

@-webkit-keyframes la-cubeGridScaleDelay {
    0% {
        -webkit-transform: scale3d(1, 1, 1);
    }
    35% {
        -webkit-transform: scale3d(0, 0, 1);
    }
    70% {
        -webkit-transform: scale3d(1, 1, 1);
    }
    100% {
        -webkit-transform: scale3d(1, 1, 1);
    }
}

@keyframes la-cubeGridScaleDelay {
    0% {
        transform: scale3d(1, 1, 1);
    }
    35% {
        transform: scale3d(0, 0, 1);
    }
    70% {
        transform: scale3d(1, 1, 1);
    }
    100% {
        transform: scale3d(1, 1, 1);
    }
}
<?php $primary_color = skudmart_get_option("primary_color", "#D2A35C"); ?>
<?php if(!empty($primary_color)): ?>
.la-loader.spinner1,
.la-loader.spinner2,
.la-loader.spinner3 [class*="bounce"],
.la-loader.spinner4 [class*="dot"],
.la-loader.spinner5 div {
    background-color: <?php echo esc_attr($primary_color) ?>;
}
<?php endif; ?>