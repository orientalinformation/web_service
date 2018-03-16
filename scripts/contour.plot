reset

#Get/Set arguments
DATAFILE = '/tmp/contour.inp'
XLABLE = ARG1
YLABLE = ARG2
UNIT = ARG3
ZMIN = ARG4
ZMAX = ARG5
TSTEP  = ARG6
OUTPUT = ARG7."/".ARG8.".png"

#Find Min/Max of data
#set term unknown
#splot DATAFILE using 1:2:3
#XMIN = GPVAL_DATA_X_MIN
#XMAX = GPVAL_DATA_X_MAX
#YMIN = GPVAL_DATA_Y_MIN
#YMAX = GPVAL_DATA_Y_MAX
#ZMIN = floor(GPVAL_DATA_Z_MIN)
#ZMAX = ceil(GPVAL_DATA_Z_MAX)

#Calculate number of contour
NCONTOUR = (ZMAX - ZMIN)/TSTEP + 1

#Calculate Step legend
LEGSTEP = (ZMAX - ZMIN)/(NCONTOUR-1)

#Calculate Legend colors
#array COLOR[9]
#COLOR[1] = ZMIN
#do for [i=2:9] {
#	COLOR[i] = COLOR[i - 1] + 0.125*(ZMAX - ZMIN)
#}

COLOR = ""
INC = 0
do for [i=0:8] {
	INCR = i*0.125*(ZMAX - ZMIN)
	COLOR = COLOR.sprintf(" %.3f",INCR)
}

#setting for plotting

reset
set term png size 640,600
set output OUTPUT
set size square
set xl "".XLABLE." (".UNIT.")"  offset char 0, char 1 tc rgb "red"
set yl "".YLABLE." (".UNIT.")"  offset char 1, char 0 tc rgb "red"
set autoscale xfix
set autoscale yfix
#set xr[XMIN:XMAX]
#set yr[YMIN:YMAX]
set cbr[ZMIN:ZMAX]

set tics nomirror
set xtics out offset 0,0.5
set ytics out offset 0.5,0
set cbtics LEGSTEP 
set cbtics in scale 3.9
set view map
set dgrid3d 40,40 gauss
set pm3d interpolate 0,0
#set palette model RGB
#set palette defined ( 0 "blue", 3 "green", 6 "yellow", 10 "red" )
# set palette defined (	COLOR[1] "#0019FF",\
						COLOR[2] "#006BFF",\
						COLOR[3] "#0098FF",\
						COLOR[4] "#25E86D",\
						COLOR[5] "#2CFF96",\
						COLOR[6] "#97FF00",\
						COLOR[7] "#FFEA00",\
						COLOR[8] "#FF6F00",\
						COLOR[9] "#FF0000")
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

splot DATAFILE using 1:2:3 with pm3d
#replot
#pause 20

#End scrip!



 
