<?php

namespace com\oxymel\ofcconveyer;

use java\io\Writer;
use java\io\File;
use java\io\FileWriter;
use java\lang\StringBuffer;

class ConveyerTemplate
{
    public static $MM;	// int
    public static $M;	// int
    public static $CM;	// int
    public static $FT;	// int
    public static $YARD;	// int
    public static $INCH;	// int
    protected static $NAME_LEGENDS;	// String[]
    protected $_height;	// double
    protected $_width;	// double
    protected $_shape;	// short
    protected $_product;	// Product
    protected $_heightBetweenProducts;	// double
    protected $_widthBetweenProducts;	// double
    protected $_widthBetweenEdgeAndProducts;	// double
    protected $_heightBetweenEdgeAndProducts;	// double
    protected $_nbPInHeight;	// int
    protected $_nbPInWidth;	// int
    protected $_parallel;	// boolean
    protected $_coordinateLegends;	// String

    // default class members
    private function __init() 
    {
        $this->_parallel =  TRUE ;
        $this->_coordinateLegends = "mm";
    }

    // static class members
    public static function __staticinit() 
    {
        self::$MM = 0;
        self::$M = 1;
        self::$CM = 2;
        self::$FT = 3;
        self::$YARD = 4;
        self::$INCH = 5;
        self::$NAME_LEGENDS = ["mm", "m", "cm", "ft", "yard", "inch"];
    }

    // [double he, double wi, short productShape]
    public static function constructor__D_D_S ($he, $wi, $productShape)
    {
        $me = new self();
        $me->__init();
        $me->_height = $he;
        $me->_width = $wi;
        $me->_shape = $productShape;
        return $me;
    }

    // [double he, double wi, double productHeight, double productWidth, short productShape]
    public static function constructor__D_D_D_D_S ($he, $wi, $productHeight, $productWidth, $productShape)
    {
        $me = self::constructor__D_D_S($he, $wi, $productShape);
        $me->_product = Product::constructor__D_D_S($productHeight, $productWidth, $productShape);
        return $me;
    }

    // [double he, double wi, double productHeight, double productWidth, short productShape, double heightInterval, double widthInterval]
    public static function constructor__D_D_D_D_S_D_D ($he, $wi, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval)
    {
        $me = self::constructor__D_D_D_D_S($he, $wi, $productHeight, $productWidth, $productShape);
        $me->_heightBetweenProducts = $heightInterval;
        $me->_widthBetweenProducts = $widthInterval;
        return $me;
    }

    // [double he, double wi, double productHeight, double productWidth, short productShape, double heightInterval, double widthInterval, double heightEdge, double widthEdge]
    public static function constructor__D_D_D_D_S_D_D_D_D ($he, $wi, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval, $heightEdge, $widthEdge)
    {
        $me = self::constructor__D_D_D_D_S_D_D($he, $wi, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval);
        $me->_widthBetweenEdgeAndProducts = $widthEdge;
        $me->_heightBetweenEdgeAndProducts = $heightEdge;
        return $me;
    }

    // [double he, double wi, double productHeight, double productWidth, short productShape, double heightInterval, double widthInterval, double heightEdge, double widthEdge, int nbProductsInHeight, int nbProductsInWidth]
    public static function constructor__D_D_D_D_S_D_D_D_D_I_I ($he, $wi, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval, $heightEdge, $widthEdge, $nbProductsInHeight, $nbProductsInWidth)
    {
        $me = self::constructor__D_D_D_D_S_D_D_D_D($he, $wi, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval, $heightEdge, $widthEdge);
        $me->_nbPInHeight = $nbProductsInHeight;
        $me->_nbPInWidth = $nbProductsInWidth;
        return $me;
    }

    // [double height, double width]
    public function setProductsInterval ($height, $width)
    {
        $this->_widthBetweenProducts = $width;
        $this->_heightBetweenProducts = $height;
    }

    // [double height, double width]
    public function setEdgeInterval ($height, $width)
    {
        $this->_heightBetweenEdgeAndProducts = $height;
        $this->_widthBetweenEdgeAndProducts = $width;
    }

    // [int nbHeight, int nbWidth]
    public function setNbElements ($nbHeight, $nbWidth)
    {
        $this->_nbPInHeight = $nbHeight;
        $this->_nbPInWidth = $nbWidth;
    }

    public function getWidth () 
    {
        return $this->_width;
    }

    // [double width]
    public function setWidth ($width)
    {
        $this->_width = $width;
    }

    // [double productHeight, double productWidth, short productShape]
    public function setProduct ($productHeight, $productWidth, $productShape)
    {
        $this->_product = Product::constructor__D_D_S($productHeight, $productWidth, $productShape);
    }

    // [Writer w, int imageHeight, int imageWidth]
    public function getSVGImage_Writer_I_I ($w, $imageHeight, $imageWidth)
    {
        $w->write($this->createSVGImage($imageHeight, $imageWidth,  TRUE )->toString());
    }

    // [Writer w, int imageHeight, int imageWidth, boolean usepx]
    public function getSVGImage_Writer_I_I_b ($w, $imageHeight, $imageWidth, $usepx)
    {
        $w->write($this->createSVGImage($imageHeight, $imageWidth, $usepx)->toString());
    }

    // [String filePath, int imageHeight, int imageWidth]
    public function getSVGImage_String_I_I ($filePath, $imageHeight, $imageWidth)
    {
        $file = new File($filePath);
        if (!$file->exists()) {
            $file->createNewFile();
        }
        $fw = new FileWriter($file);
        $fw->write($this->createSVGImage($imageHeight, $imageWidth,  TRUE )->toString());
        $fw->flush();
        $fw->close();
    }

    // [String filePath, int imageHeight, int imageWidth, boolean usepx]
    public function getSVGImage_String_I_I_b ($filePath, $imageHeight, $imageWidth, $usepx)
    {
        $file = new File($filePath);
        if (!$file->exists()) {
            $file->createNewFile();
        }
        $fw = new FileWriter($file);
        $fw->write($this->createSVGImage($imageHeight, $imageWidth, $usepx)->toString());
        $fw->flush();
        $fw->close();
    }

    // [int imageHeight, int imageWidth]
    public function getSVGImage_I_I ($imageHeight, $imageWidth, $PROD_POSITION)
    {
        return $this->createSVGImage($imageHeight, $imageWidth, TRUE, $PROD_POSITION )->toString();
    }

    // [int imageHeight, int imageWidth, boolean usepx]
    public function getSVGImage_I_I_b ($imageHeight, $imageWidth, $usepx)
    {
        return $this->createSVGImage($imageHeight, $imageWidth, $usepx)->toString();
    }

    // [int imageHeight, int imageWidth, boolean usepx]
    protected function createSVGImage ($imageHeight, $imageWidth, $usepx, $PROD_POSITION = 1)
    {
        $out = new StringBuffer();
        $type = NULL;
        $out->append(SVGGenerator::getHeader($imageHeight, $imageWidth, $usepx));
        $type = SVGGenerator::getScale($this->_height, $this->_width);
        $out->append(SVGGenerator::getMethods($type));
        
        if (($this->_product != NULL)) {
            $this->_product->scale($type[3], $type[2], $this->_height, $this->_width, $this->_parallel);
            $this->_product->set_shape($this->_shape);
            
            $x = ($type[0] + (((($type[2] * $this->_widthBetweenEdgeAndProducts)) / $this->_width)));
            $y = ($type[1] + (((($type[3] * $this->_heightBetweenEdgeAndProducts)) / $this->_height)));
            $xInterval = (((($type[2] * $this->_widthBetweenProducts)) / $this->_width));
            $yInterval = (((($type[3] * $this->_heightBetweenProducts)) / $this->_height));
            for ($nby = 0; ($nby < $this->_nbPInHeight); ++$nby) {
                $tmpx = $x;
                for ($nbx = 0; ($nbx < $this->_nbPInWidth); ++$nbx) {
                    $out->append($this->_product->getSVG($tmpx, $y, $PROD_POSITION));
                    $tmpx += ($xInterval + $this->_product->getWidth());
                }

                $y += ($this->_product->getHeight() + $yInterval);
            }
        }
        $out->append(SVGGenerator::getLegends($type, $this->_height, $this->_width, $this->_coordinateLegends));
        $out->append(SVGGenerator::getFooter());
        return $out;
    }

    protected function getCoordinateLegend () 
    {
        return $this->_coordinateLegends;
    }

    // [String coordinate]
    public function setCoordinateLegend ($coordinate)
    {
        $this->_coordinateLegends = $coordinate;
    }

    // [boolean isparallel]
    public function setParallelePlacement ($isparallel)
    {
        $this->_parallel = $isparallel;
    }
}
ConveyerTemplate::__staticinit(); // initialize static vars for this class on load

