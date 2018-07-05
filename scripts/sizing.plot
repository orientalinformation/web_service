reset
DATAFILE = '/tmp/sizing.inp'
OUTPUT = ARG3."/".ARG4.".png"

set term unknown
plot DATAFILE using 2:xtic(1) ti col, '' using 3 ti col
YMAX = ceil(GPVAL_DATA_Y_MAX) + 0.0
if (YMAX == 0) YMAX = 1.0 
CUS_POS = ARG5/YMAX

set terminal png size 1575,700  background rgb "white"
set output OUTPUT
#set terminal windows size 1575,700

set label  ARG1 center at graph 0, graph 1.1
set label  ARG2 center at graph 1.0, graph 1.1
set yr[0:YMAX]
set tics nomirror scale 2
set y2tics
set xtics scale 0 offset 0,-0.5 font ",12"
set y2r[0:]
set border 1 lw 3
set offset -0.3,-0.3,0,0
set tmargin at screen 0.9;
set bmargin at screen 0.25
set style data histogram
set style histogram cluster gap 1.2
set style fill solid border -1
set boxwidth 1.2
set arrow from graph 1,0 to graph 1,1.04 filled lw 3
set arrow from graph 0,0 to graph 0,1.04 filled lw 3
set key left bmargin box lw 1 opaque height 1.5 font ",12" spacing 1.0 reverse vertical Left samplen 3
set arrow from graph 0,CUS_POS to graph 1,CUS_POS nohead lc rgb "red" lw 3 front
set label ARG6 at graph 0.5, CUS_POS + 0.05 font ",15" tc rgb "red" front
plot DATAFILE using 2:xtic(1) ti col lc rgb "blue", '' using 3 ti col lc rgb "#a0a8f2", '' using 4 ti col axis x1y2 lc rgb "green", '' using 5 ti col axis x1y2

#pause -1