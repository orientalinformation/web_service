reset 

#Get/Set arguments
DATAFILE = ARG9
XLABLE = ARG1
YLABLE = ARG2
UNIT = ARG3
ZMIN = ARG4
ZMAX = ARG5
TSTEP  = ARG6
OUTPUT = ARG7."/".ARG8.".png"

#Calculate number of contour
NCONTOUR = (ZMAX - ZMIN)/TSTEP + 1

#Calculate Step legend
LEGSTEP = (ZMAX - ZMIN)/(NCONTOUR-1)

#Calculate Legend colors
COLOR = ""
INC = 0
do for [i=0:8] {
	INCR = i*0.125*(ZMAX - ZMIN)
	COLOR = COLOR.sprintf(" %.3f",INCR)
}


set term png
set output OUTPUT
#set size square
set dgrid3d 500,500 spline
set table $dat
splot DATAFILE
RADIUS1 = GPVAL_DATA_X_MAX + 0.0
RADIUS2 = GPVAL_DATA_Y_MAX + 0.0
unset table
unset dgrid3d

set tics nomirror
set xtics out offset 0,0.5
set ytics out offset 0.5,0
set cbtics LEGSTEP 
set cbtics in scale 3.9
set view map
set pm3d interpolate 0,0

#set palette model RGB
set palette defined (	word(COLOR,1) "#0019FF",\
						word(COLOR,2) "#006BFF",\
						word(COLOR,3) "#0098FF",\
						word(COLOR,4) "#25E86D",\
						word(COLOR,5) "#2CFF96",\
						word(COLOR,6) "#97FF00",\
						word(COLOR,7) "#FFEA00",\
						word(COLOR,8) "#FF6F00",\
						word(COLOR,9) "#FF0000")
set contour	surface 			
set cntrparam levels NCONTOUR	
unset clabel	
unset key	
set lmargin at screen 0.1;
set rmargin at screen 0.88;
set bmargin at screen 0.05;
set tmargin at screen 0.98;

# plot the heatmap
splot $dat using 1:2:(($1**2/RADIUS1**2 + $2**2/RADIUS2**2 > 1.0)?1/0:$3) with pm3d

unset output