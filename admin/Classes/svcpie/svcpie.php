<?php

/* 
From: http://brankocollin.nl/sites/default/files/uploads/piechart.php.txt

A small PHP program and function that draws a pie chart in SVG format. 

Written by Branko Collin in 2008.

This code is hereby released into the public domain. In case this is not legally possible: I, Branko Collin, hereby grant anyone the right to use this work for any purpose, without any conditions, unless such conditions are required by law.*/ 

/* Working with relative values confused me, so I worked with absolute ones 
instead. Generally this should not be a problem, as the only relative values you 
need are the chart's centre coordinates and its radius, and these are a linear
function of the bounding box size or canvas size. See the sample values for how 
this could work out. */

/* 
	The piechart function
	
	Arguments are an aray of values, the centre coordinates x and y, and 
	the radius of the piechart.	
*/

function piechart($data, $cx, $cy, $radius) {
	$chartelem = "";

	$max = count($data);
	
	$colours = array('red','orange','yellow','green','blue');
	
	$sum = 0;
	foreach ($data as $key=>$val) {
		$sum += $val;
	}
	$deg = $sum/360; // one degree
	$jung = $sum/2; // necessary to test for arc type
	
	/* Data for grid, circle, and slices */ 
	
	$dx = $radius; // Starting point: 
	$dy = 0; // first slice starts in the East
	$oldangle = 0;
	
	/* Loop through the slices */
	for ($i = 0; $i<$max; $i++) {
		$angle = $oldangle + $data[$i]/$deg; // cumulative angle
		$x = cos(deg2rad($angle)) * $radius; // x of arc's end point
		$y = sin(deg2rad($angle)) * $radius; // y of arc's end point
	
		$colour = $colours[$i];
	
		if ($data[$i] > $jung) {
			// arc spans more than 180 degrees
			$laf = 1;
		}
		else {
			$laf = 0;
		}
	
		$ax = $cx + $x; // absolute $x
		$ay = $cy + $y; // absolute $y
		$adx = $cx + $dx; // absolute $dx
		$ady = $cy + $dy; // absolute $dy
		$chartelem .= "\n";
		$chartelem .= "<path d=\"M$cx,$cy "; // move cursor to center
		$chartelem .= " L$adx,$ady "; // draw line away away from cursor
		$chartelem .= " A$radius,$radius 0 $laf,1 $ax,$ay "; // draw arc
		$chartelem .= " z\" "; // z = close path
		$chartelem .= " fill=\"$colour\" stroke=\"black\" stroke-width=\"2\" ";
		$chartelem .= " fill-opacity=\"0.5\" stroke-linejoin=\"round\" />";
		$dx = $x; // old end points become new starting point
		$dy = $y; // id.
		$oldangle = $angle;
	}
	
	return $chartelem; 
}

?>
