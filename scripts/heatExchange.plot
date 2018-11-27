reset
DATAFILE = '/tmp/heatExchange.inp'
OUTPUT = ARG3."/".ARG4.".png"
TITLE = ARG5
set terminal png size 1575,700 font ",20" background rgb "white"
set output OUTPUT
set tics nomirror out scale 2
set xtics rotate by -30

#set border 3 lw 3
set rmargin at screen 0.77;
set tmargin at screen 0.9;
set label  ARG1 at graph 1.06, first 0
set label  ARG2 center at graph 0, graph 1.06
set arrow 1 from graph 0,first 0 to graph 1.04,first 0 filled lw 3
set arrow 2 from first 0,graph 0 to first 0,graph 1.04 filled lw 3
set grid xtics ytics lt 1.5 lc rgb "#9B9999"
set key rmargin box lw 1 opaque height 1.5
plot DATAFILE using 1:2 w l lc rgb "blue" lw 3 title TITLE
