<?php

namespace com\oxymel\ofcconveyer;
use java\lang\StringBuffer;

class Product {
    protected $_height;	// double
    protected $_width;	// double
    protected $_svgHeight;	// double
    protected $_svgWidth;	// double
    protected $_shape;	// short
    public static $SLAB;	// short
    public static $PARALLELEPIPED_STANDING;	// short
    public static $PARALLELEPIPED_LAYING;	// short
    public static $CYLINDER_STANDING;	// short
    public static $CYLINDER_LAYING;	// short
    public static $SPHERE;	// short
    public static $CYLINDER_CONCENTRIC_STANDING;	// short
    public static $CYLINDER_CONCENTRIC_LAYING;	// short
    public static $PARALLELEPIPED_BREADED;	// short

    private function __init() { // default class members
        $this->_svgHeight = 0;
        $this->_svgWidth = 0;
    }
    public static function __staticinit() { // static class members
        self::$SLAB = 1;
        self::$PARALLELEPIPED_STANDING = 2;
        self::$PARALLELEPIPED_LAYING = 3;
        self::$CYLINDER_STANDING = 4;
        self::$CYLINDER_LAYING = 5;
        self::$SPHERE = 6;
        self::$CYLINDER_CONCENTRIC_STANDING = 7;
        self::$CYLINDER_CONCENTRIC_LAYING = 8;
        self::$PARALLELEPIPED_BREADED = 9;
    }
    public static function constructor__D_D_S ($height, $width, $shape) // [double height, double width, short shape]
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

    public function scale ($containerHeight, $containerWidth, $realHeight, $realWidth, $isParallel) // [double containerHeight, double containerWidth, double realHeight, double realWidth, boolean isParallel]
    {
        $tmpy = null;
        $tmpx = null;
        if ($isParallel)
        {
            $tmpy = $this->_height;
            $tmpx = $this->_width;
        }
        else
        {
            $tmpx = $this->_height;
            $tmpy = $this->_width;
        }
        $this->_svgHeight = ((($containerHeight * $tmpy)) / $realHeight);
        $this->_svgWidth = ((($containerWidth * $tmpx)) / $realWidth);
        // var_dump($this); die('asdasda');
    }

    public function getSVG ($x, $y) // [double x, double y]
    {
        $tmp = new StringBuffer();
        $r = null;
        switch ($this->_shape) {
            case self::$SLAB:
            case self::$PARALLELEPIPED_STANDING:
            case self::$PARALLELEPIPED_LAYING:
            case self::$PARALLELEPIPED_BREADED:
            case self::$CYLINDER_LAYING:
            case self::$CYLINDER_CONCENTRIC_LAYING:
                // var_dump($this); die('asw');
                $tmp->append((((((((("\t<rect x=\"" . $x) . "\" y=\"") . $y) . "\" width=\"") . $this->_svgWidth) . "\" height=\"") . $this->_svgHeight) . "\" fill=\"gray\" stroke=\"black\" stroke-width=\"1\"/>\n"));
                break;
            case self::$CYLINDER_STANDING:
            case self::$CYLINDER_CONCENTRIC_STANDING:
            case self::$SPHERE:
                $x += ($this->_svgWidth / 2);
                $y += ($this->_svgHeight / 2);
                $r = ($this->_svgWidth / 2);
                $tmp->append((((((("\t<circle cx=\"" . $x) . "\" cy=\"") . $y) . "\" r=\"") . $r) . "\" fill=\"gray\" stroke=\"black\" stroke-width=\"1\"/>\n"));
                break;
            default:
                $tmp->append((((((((("\t<rect x=\"" . $x) . "\" y=\"") . $y) . "\" width=\"") . $this->_svgWidth) . "\" height=\"") . $this->_svgHeight) . "\" fill=\"gray\" stroke=\"black\" stroke-width=\"1\"/>\n"));
        }
        return $tmp;
    }

    public function get_shape () 
    {
        return $this->_shape;
    }

    public function set_shape ($s) // [short s]
    {
        $this->_shape = $s;
    }
}
Product::__staticinit(); // initialize static vars for this class on load

