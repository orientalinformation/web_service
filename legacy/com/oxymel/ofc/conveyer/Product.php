<?php

namespace com\oxymel\ofcconveyer;
use java\lang\StringBuffer;

class Product 
{
    protected $_height; // double
    protected $_width;  // double
    protected $_svgHeight;  // double
    protected $_svgWidth;   // double
    protected $_shape;
    public static $SLAB;
    public static $PARALLELEPIPED_STANDING;
    public static $PARALLELEPIPED_LAYING;
    public static $CYLINDER_STANDING;
    public static $CYLINDER_LAYING;
    public static $SPHERE;
    public static $CYLINDER_CONCENTRIC_STANDING;
    public static $CYLINDER_CONCENTRIC_LAYING;
    public static $PARALLELEPIPED_BREADE;
    public static $PARALLELEPIPED_BREADED;
    public static $D_RECTANGULAR_BLOCK_V;
    public static $D_RECTANGULAR_BLOCK_H;
    public static $D_STANDING_CYLINDER;
    public static $D_LYLING_CYLINDER;
    public static $D_SPHERE;
    public static $D_STANDING_CONCENTRIC_CYLINDER;
    public static $D_LYLING_CONCENTRIC_CYLINDER;
    public static $D_RECTANGULAR_BLOCK_B;
    public static $D_TRAPEZOIND_3D;
    public static $D_STANDING_OVAL;
    public static $D_LYLING_OVAL;

    // default class members
    private function __init()
    { 
        $this->_svgHeight = 0;
        $this->_svgWidth = 0;
    }

    // static class members
    public static function __staticinit()
    {
        self::$SLAB = 1;
        self::$PARALLELEPIPED_STANDING = 2;
        self::$PARALLELEPIPED_LAYING = 3;
        self::$CYLINDER_STANDING = 4;
        self::$CYLINDER_LAYING = 5;
        self::$SPHERE = 6;
        self::$CYLINDER_CONCENTRIC_STANDING = 7;
        self::$CYLINDER_CONCENTRIC_LAYING = 8;
        self::$PARALLELEPIPED_BREADED = 9;
        self::$D_RECTANGULAR_BLOCK_V = 10;
        self::$D_RECTANGULAR_BLOCK_H = 11;
        self::$D_STANDING_CYLINDER = 12;
        self::$D_LYLING_OVAL = 13;
        self::$D_SPHERE = 14;
        self::$D_STANDING_CONCENTRIC_CYLINDER = 15;
        self::$D_LYLING_CONCENTRIC_CYLINDER = 16;
        self::$D_RECTANGULAR_BLOCK_B = 17;
        self::$D_TRAPEZOIND_3D = 18;
        self::$D_STANDING_OVAL = 19;
        self::$D_LYLING_OVAL = 20;
    }

    // [double height, double width, short shape]
    public static function constructor__D_D_S ($height, $width, $shape)
    {
        $me = new self();
        $me->__init();
        $me->_height = $height;
        $me->_width = $width;
        $me->_shape = $shape;
        return $me;
    }

    public function getHeight () 
    {
        return $this->_svgHeight;
    }

    public function getWidth () 
    {
        return $this->_svgWidth;
    }

    // [double containerHeight, double containerWidth, double realHeight, double realWidth, boolean isParallel]
    public function scale ($containerHeight, $containerWidth, $realHeight, $realWidth, $isParallel)
    {
        $tmpy = null;
        $tmpx = null;
        if ($isParallel) {
            $tmpy = $this->_height;
            $tmpx = $this->_width;
        } else {
            $tmpx = $this->_height;
            $tmpy = $this->_width;
        }
        $this->_svgHeight = ((($containerHeight * $tmpy)) / $realHeight);
        $this->_svgWidth = ((($containerWidth * $tmpx)) / $realWidth);
    }

    // [double x, double y]
    public function getSVG ($x, $y, $_parallel)
    {
        $tmp = new StringBuffer();
        $r = $rx = $ry = null;
        switch ($this->_shape) {
            case self::$SLAB:
            case self::$PARALLELEPIPED_STANDING:
            case self::$PARALLELEPIPED_LAYING:
            case self::$PARALLELEPIPED_BREADED:
            case self::$CYLINDER_LAYING:
            case self::$CYLINDER_CONCENTRIC_LAYING:
                $tmp->append((((((((("\t<rect x=\"" . $x) . "\" y=\"") . $y) . "\" width=\"") . $this->_svgWidth) . "\" height=\"") . $this->_svgHeight) . "\" fill=\"gray\" stroke=\"black\" stroke-width=\"1\"/>\n"));
                break;

            case self::$D_LYLING_OVAL:
                $tmp->append((((((((("\t<rect x=\"" . $x) . "\" y=\"") . $y) . "\" width=\"") . $this->_svgWidth) . "\" height=\"") . $this->_svgHeight) . "\" fill=\"gray\" stroke=\"black\" stroke-width=\"1\"/>\n"));
                break;

            case self::$CYLINDER_STANDING:
            case self::$CYLINDER_CONCENTRIC_STANDING:
            case self::$D_STANDING_CONCENTRIC_CYLINDER:
            case self::$D_LYLING_CONCENTRIC_CYLINDER:
            case self::$SPHERE:
            case self::$D_SPHERE:
                $x += ($this->_svgWidth / 2);
                $y += ($this->_svgHeight / 2);
                $r = ($this->_svgWidth / 2);
                $tmp->append((((((("\t<circle cx=\"" . $x) . "\" cy=\"") . $y) . "\" r=\"") . $r) . "\" fill=\"gray\" stroke=\"black\" stroke-width=\"1\"/>\n"));
                break;

            case self::$D_STANDING_OVAL:
                $x += ($this->_svgWidth / 2);
                $y += ($this->_svgHeight / 2);
                $r = ($this->_svgWidth / 2);
                
                if ($_parallel == 1) {
                    $rx = $r;
                    $ry = $r / 2;
                } else {
                    $rx = $r / 2;
                    $ry = $r;
                }
                $tmp->append((((((((("\t<ellipse cx=\"" . $x) . "\" cy=\"") . $y) . "\" rx=\"") . $rx) . "\" ry=\"") . $ry). "\" fill=\"gray\" stroke=\"black\" stroke-width=\"1\"/>\n"));
                break;

            default:
                $tmp->append((((((((("\t<rect x=\"" . $x) . "\" y=\"") . $y) . "\" width=\"") . $this->_svgWidth) . "\" height=\"") . $this->_svgHeight) . "\" fill=\"gray\" stroke=\"black\" stroke-width=\"1\"/>\n"));
                break;
        }
        return $tmp;
    }

    public function get_shape () 
    {
        return $this->_shape;
    }

    // [short s]
    public function set_shape ($s)
    {
        $this->_shape = $s;
    }
}

Product::__staticinit(); // initialize static vars for this class on load

