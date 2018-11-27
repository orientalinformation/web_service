<?php
namespace com\oxymel\ofcconveyer;

class ConveyerBelt extends ConveyerTemplate 
{
    protected static $HEIGHT_LEGENDS;	
    public static function __staticinit() 
    { // static class members
        self::$HEIGHT_LEGENDS = [1000, 1, 100, doubleval(3.2808), doubleval(1.0936), doubleval(39.37)];
    }

    public static function constructor__I_D_S ($unity, $width, $productShape) // [int unity, double width, short productShape]
    {
        $me = parent::constructor__D_D_S(self::$HEIGHT_LEGENDS[$unity], $width, $productShape);
        $me->setCoordinateLegend(parent::$NAME_LEGENDS[$unity]);
        return $me;
    }

    // [int unity, double width, double productHeigth, 
    public static function constructor__I_D_D_D_S ($unity, $width, $productHeigth, $productWidth, $productShape)
    {
        $me = parent::constructor__D_D_D_D_S(self::$HEIGHT_LEGENDS[$unity], $width, $productHeigth, $productWidth, $productShape);
        $me->setCoordinateLegend(parent::$NAME_LEGENDS[$unity]);
        return $me;
    }

    // [int unity, double width, double productHeight, double productWidth, short productShape, double heightInterval, double widthInterval]
    public static function constructor__I_D_D_D_S_D_D ($unity, $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval)
    {
        $me = parent::constructor__D_D_D_D_S_D_D(self::$HEIGHT_LEGENDS[$unity], $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval);
        $me->setCoordinateLegend(parent::$NAME_LEGENDS[$unity]);
        return $me;
    }

    // [int unity, double width, double productHeight, double productWidth, short productShape, double heightInterval, double widthInterval, double heightEdge, double widthEdge]
    public static function constructor__I_D_D_D_S_D_D_D_D ($unity, $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval, $heightEdge, $widthEdge) 
    {
        $me = parent::constructor__D_D_D_D_S_D_D_D_D(self::$HEIGHT_LEGENDS[$unity], $width, $productHeight, $productWidth, $productShape, $heightInterval, $widthInterval, $heightEdge, $widthEdge);
        $me->setCoordinateLegend(parent::$NAME_LEGENDS[$unity]);
        return $me;
    }

    // [double height, double width, short productShape, String coordLegend]
    public static function constructor__D_D_S_String ($height, $width, $productShape, $coordLegend)
    {
        $me = parent::constructor__D_D_S($height, $width, $productShape);
        $me->setCoordinateLegend($coordLegend);
        return $me;
    }
}

ConveyerBelt::__staticinit(); // initialize static vars for this class on load
