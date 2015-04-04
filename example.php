<?php
if ( file_exists( 'sparkline.php' ))
	require_once( 'sparkline.php' );
	
$phpSparklines = new phpSparklines();
$phpSparklines->setExtension( '.png' )->generate()->render();
?>	