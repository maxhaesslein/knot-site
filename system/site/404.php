<?php

if( ! $eigenheim ) exit;

http_response_code(404);

snippet( 'header' );

snippet( '404' );

snippet( 'footer' );
