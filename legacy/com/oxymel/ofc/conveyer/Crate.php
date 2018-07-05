<?php
namespace com\oxymel\ofcconveyer;

class Crate extends ConveyerTemplate 
{

    // [int unity, double he, double wi, short productShape]
    public static function constructor__I_D_D_S ($unity, $he, $wi, $productShape)
    {
        $me = parent::constructor__D_D_S($he, $wi, $productShape);
        $me->setCoordinateLegend(parent::$NAME_LEGENDS[$unity]);
        return $me;
    }

    // [int unity, double height, double width, double productHeight, double productWidth, short productShape]
    public static function constructor__I_D_D_D_D_S ($unity, $height, $width, $productHeight, $productWidth, $productShape)
    {
        $me = parent::constructor__D_D_D_D_S($height, $width, $productHeight, $productWidth, $productShape);
        $me->setCoordinateLegend(parent::$NAME_LEGENDS[$unity]);
        return $me;
    }

    // [int unity, double height, double width, double productHeight, double productWidth, short productShape, double heightInterval, double widthInterval]
    public static function constructor__I_D_D_D_D_S_D_D ($unity, $height, $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval)
    {
        $me = parent::constructor__D_D_D_D_S_D_D($height, $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval);
        
        $me->setCoordinateLegend(parent::$NAME_LEGENDS[$unity]);
        return $me;
    }

    // [int unity, double height, double width, double productHeight, double productWidth, short productShape, double heightInterval, double widthInterval, double heightEdge, double widthEdge]
    public static function constructor__I_D_D_D_D_S_D_D_D_D ($unity, $height, $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval, $heightEdge, $widthEdge)
    {
        $me = parent::constructor__D_D_D_D_S_D_D_D_D($height, $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval, $heightEdge, $widthEdge);
        return $me;
    }

    public function getHeight() 
    {
        return $this->_height;
    }

    // [double height]
    public function setHeight ($height)
    {
        $this->_height = $height;
    }

    public function getUnity () 
    {
        return $this->getCoordinateLegend();
    }
}

