<?php
namespace com\oxymel\ofcconveyer;

class Crate extends ConveyerTemplate {
    public static function constructor__I_D_D_S ($unity, $he, $wi, $productShape) // [int unity, double he, double wi, short productShape]
    {
        $me = parent::constructor__D_D_S($he, $wi, $productShape);
        $me->setCoordinateLegend(parent::$NAME_LEGENDS[$unity]);
        return $me;
    }
    public static function constructor__I_D_D_D_D_S ($unity, $height, $width, $productHeight, $productWidth, $productShape) // [int unity, double height, double width, double productHeight, double productWidth, short productShape]
    {
        $me = parent::constructor__D_D_D_D_S($height, $width, $productHeight, $productWidth, $productShape);
        $me->setCoordinateLegend(parent::$NAME_LEGENDS[$unity]);
        return $me;
    }
    public static function constructor__I_D_D_D_D_S_D_D ($unity, $height, $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval) // [int unity, double height, double width, double productHeight, double productWidth, short productShape, double heightInterval, double widthInterval]
    {
        $me = parent::constructor__D_D_D_D_S_D_D($height, $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval);
        
        $me->setCoordinateLegend(parent::$NAME_LEGENDS[$unity]);
        return $me;
    }
    public static function constructor__I_D_D_D_D_S_D_D_D_D ($unity, $height, $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval, $heightEdge, $widthEdge) // [int unity, double height, double width, double productHeight, double productWidth, short productShape, double heightInterval, double widthInterval, double heightEdge, double widthEdge]
    {
        $me = parent::constructor__D_D_D_D_S_D_D_D_D($height, $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval, $heightEdge, $widthEdge);
        return $me;
    }
    public function getHeight () 
    {
        return $this->_height;
    }
    public function setHeight ($height) // [double height]
    {
        $this->_height = $height;
    }
    public function getUnity () 
    {
        return $this->getCoordinateLegend();
    }
}

