<?php

namespace com\oxymel\ofcconveyer;

use java\lang\StringBuffer;

class SVGGenerator 
{
    protected static $EQUAL_DEF;    // double[]
    protected static $SUP_DEF;    // double[]
    protected static $INF_DEF;    // double[]

    // static class members
    public static function __staticinit() 
    {
        self::$EQUAL_DEF = [100, 100, 800, 800, 100, 950, 430, 950, 100, 950, 115, 935, 100, 950, 115, 965, 580, 950, 900, 950, 900, 950, 885, 935, 900, 950, 885, 965, 950, 100, 950, 400, 950, 100, 935, 115, 950, 100, 965, 115, 950, 600, 950, 900, 950, 900, 935, 885, 950, 900, 965, 885];
        self::$SUP_DEF = [250, 100, 500, 800, 250, 950, 430, 950, 250, 950, 265, 935, 250, 950, 265, 965, 580, 950, 750, 950, 750, 950, 735, 935, 750, 950, 735, 965, 800, 100, 800, 430, 800, 100, 785, 115, 800, 100, 815, 115, 800, 580, 800, 900, 800, 900, 785, 885, 800, 900, 815, 885];
        self::$INF_DEF = [100, 250, 800, 500, 100, 800, 430, 800, 100, 800, 115, 785, 100, 800, 115, 815, 580, 800, 900, 800, 900, 800, 885, 785, 900, 800, 885, 815, 950, 250, 950, 430, 950, 250, 935, 265, 950, 250, 965, 265, 950, 580, 950, 750, 950, 750, 935, 735, 950, 750, 965, 735];
    }

    // [int imageHeight, int imageWidth, boolean usepx]
    public static function getHeader ($imageHeight, $imageWidth, $usepx)
    {
        $tmp = new StringBuffer();
        $sunit = "";
        if (!$usepx) {
            $sunit = "cm";
        }
        $tmp->append("<?xml version=\"1.0\" standalone=\"no\"?>\n");
        $tmp->append("<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.0//EN\" \"http://www.w3.org/TR/2001/REC-SVG-20010904/DTD/svg10.dtd\">\n");
        $tmp->append((((((("<svg width=\"" . $imageWidth) . $sunit) . "\" height=\"") . $imageHeight) . $sunit) . "\" viewBox=\"0 0 1000 1000\" preserveAspectRatio=\"xMidYMid meet\"\n"));
        $tmp->append("\txml:lang=\"fr\"\n");
        $tmp->append("\txmlns=\"http://www.w3.org/2000/svg\"\n");
        $tmp->append("\txmlns:xlink=\"http://www.w3.org/1999/xlink\" >\n");
        $tmp->append("\t<rect id=\"fond\" x=\"0%\" y=\"0%\" width=\"100%\" height=\"100%\" fill=\"rgb(202,225,247)\" stroke=\"rgb(149, 193, 238)\" stroke-width=\"1\"/>\n");
        return $tmp;
    }

    // [double[] type]
    public static function getMethods ($type)
    {
        $tmp = new StringBuffer();
        $tmp->append((((((((("\t<rect id=\"Tapis\" x=\"" . $type[0]) . "\" y=\"") . $type[1]) . "\" width=\"") . $type[2]) . "\" height=\"") . $type[3]) . "\" fill=\"white\" stroke=\"black\" stroke-width=\"1\"/>\n"));
        for ($i = 4; ($i < 49); $i = ($i + 4)) {
            $tmp->append((((((((("\t<line x1=\"" . $type[$i]) . "\" y1=\"") . $type[($i + 1)]) . "\" x2=\"") . $type[($i + 2)]) . "\" y2=\"") . $type[($i + 3)]) . "\" stroke=\"black\" stroke-width=\"1\" />\n"));
        }
        return $tmp;
    }

    // [double[] type, double height, double width, String coordinate]
    public static function getLegends ($type, $height, $width, $coordinate)
    {
        $tmp = new StringBuffer();
        $tmp->append(((((((("\t<text x=\"500\" y=\"" . ($type[25] + 15))) . "\" font-size=\"25\"  text-anchor=\"middle\" >") . $width) . " ") . $coordinate) . "</text>\n"));
        $tmp->append((("\t<g transform=\"translate(" . ($type[48])) . " 500)\">\n"));
        $tmp->append((((("\t\t<text x=\"0\" y=\"0\" text-anchor=\"middle\" font-size=\"25\" transform=\"rotate(90)\">" . $height) . " ") . $coordinate) . "</text>\n"));
        $tmp->append("\t</g>\n");
        return $tmp;
    }

    public static function getFooter () 
    {
        return new StringBuffer("</svg>\n");
    }

    // [double height, double width]
    public static function getScale ($height, $width)
    {
        if (($height == $width)) {
            return self::$EQUAL_DEF;
        }

        $type = array();
        if (($height > $width)) {
            foreach (range(0, (51 + 0)) as $_upto) $type[$_upto] = self::$SUP_DEF[$_upto - (0) + 0]; /* from: System.arraycopy(SUP_DEF, 0, type, 0, 52) */;
            $type[2] = (((800 * $width)) / $height);
            $type[0] = (((1000 - $type[2])) / 2);
            for ($i = 4; ($i < 13); $i = ($i + 4)) {
                $type[$i] = $type[0];
                if (($i > 4)) {
                    $type[($i + 2)] = ($type[0] + 15);
                }
            }
            $type[18] = ($type[2] + $type[0]);
            $type[20] = $type[18];
            $type[24] = $type[18];
            $type[22] = ($type[18] - 15);
            $type[26] = $type[22];

            for ($i = 28; ($i < 49); $i = ($i + 4)) {
                $type[$i] = (($type[0] + $type[2]) + 50);
            }
            $type[30] = $type[28];
            $type[42] = $type[28];
            $type[34] = ($type[28] - 15);
            $type[46] = $type[34];
            $type[38] = ($type[28] + 15);
            $type[50] = $type[38];
        } else {
            foreach (range(0, 51) as $_upto) $type[$_upto] = self::$INF_DEF[$_upto]; /* from: System.arraycopy(INF_DEF, 0, type, 0, 52) */;
            $type[3] = (((800 * $height)) / $width);
            $type[1] = (((1000 - $type[3])) / 2);
            for ($i = 5; ($i < 26); $i = ($i + 4)) {
                $type[$i] = (($type[1] + $type[3]) + 50);
            }
            $type[7] = $type[5];
            $type[19] = $type[5];
            $type[11] = ($type[5] - 15);
            $type[23] = $type[11];
            $type[15] = ($type[5] + 15);
            $type[27] = $type[15];

            for ($i = 29; ($i < 38); $i = ($i + 4)) {
                $type[$i] = $type[1];
                if (($i > 29)) {
                    $type[($i + 2)] = ($type[1] + 15);
                }
            }
            $type[43] = ($type[3] + $type[1]);
            $type[45] = $type[43];
            $type[49] = $type[43];
            $type[47] = ($type[43] - 15);
            $type[51] = $type[47];
        }
        return $type;
    }
}

SVGGenerator::__staticinit(); // initialize static vars for this class on load

