reset
DATAFILE = '/tmp/timeBased.inp'
OUTPUT = ARG3."/".ARG4.".png"
set terminal png size 1366,768 font ",12" background rgb "white"
set output OUTPUT
#set terminal windows size 1366,768 font ",12"
set border 3 lw 2
set xtics scale 2 nomirror rotate by -45 offset 0,-0.5 font ",12"
set ytics scale 2 nomirror font ",12"
set rmargin at screen 0.72;
set tmargin at screen 0.9;
set bmargin at screen 0.1
set label  ARG1 at graph 1.07, graph 0
set label  ARG2 center at graph 0, graph 1.07
set arrow from graph 0,0 to graph 1.05,0 filled lw 3
set arrow from graph 0,0 to graph 0,1.05 filled lw 3
set grid xtics ytics lt 1.5 lc rgb "#9B9999"
set key rmargin box lw 1 opaque height 1.5 reverse vertical Left spacing  1.3
set palette model RGB rgbformulae 35,13,10 #rainbow (blue-green-yellow-red)
#set palette model HSV functions gray,1,1 # HSV color space
unset colorbox
plot for[i=2:5]DATAFILE using 1:i t column(i) w l lw 3 lc palette frac (i-2.0)/3.0
#pause -1