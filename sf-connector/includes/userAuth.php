<?php
/*--------------------------------------------------------\
|  Assign your USER ID & PASSWORD with TOKEN
\--------------------------------------------------------*/

if ( function_exists( 'is_kt_dev' ) ){
    $env = ( is_kt_dev() ) ? 'dev' : 'live';
}else{
    $env = 'dev';
}


if ( $env == 'dev' ){
    $this->USERNAME = "";
    $this->PASSWORD = "";
} else {
    $this->USERNAME = "";
    $this->PASSWORD = "";
}