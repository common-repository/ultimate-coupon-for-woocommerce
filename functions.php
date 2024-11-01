<?php
function ucfw_esc_js( $string, $textdomain )
{
    return esc_js( __( $string, $textdomain ) );
}
