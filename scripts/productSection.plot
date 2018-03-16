reset
DATAFILE = '/tmp/productSection.inp'
OUTPUT = ARG3."/".ARG4.".png"
set key autotitle columnheader
stats DATAFILE nooutput
NCOL = STATS_columns
STEP = NCOL - 1.0
set term unknown
plot for[i=2:NCOL]DATAFILE using i:1 w l
set terminal png size 1366,768 background rgb "white"
set output OUTPUT
#set terminal windows size 1575,700 font ",12"

set border 1 lw 2
unset xtics
unset ytics
set x2tics nomirror offset 0,0.5 font ",12"
set y2tics nomirror scale 2 font ",12"
set x2range[:GPVAL_DATA_X_MAX + 1]
set rmargin at screen 0.7;
set tmargin at screen 0.9;
set label  ARG1 at graph 1.05, graph 1 
set label  ARG2 center at graph 1, graph 1.06
set arrow from graph 0,1 to graph 1.04,1 filled lw 3
set arrow from graph 1,0 to graph 1,1.04 filled lw 3
set grid x2tics y2tics lt 1.5 lc rgb "#9B9999"
set key rmargin box lw 1 opaque height 1.5 reverse vertical Left
set palette model RGB rgbformulae 35,13,10 #rainbow (blue-green-yellow-red)
#set palette model HSV functions gray,1,1 # HSV color space
unset colorbox
plot for[i=2:NCOL]DATAFILE using i:1 t column(i) w l lw 3 lc palette frac (i-2)/STEP axis x2y2
#pause -1