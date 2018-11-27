DATAFILE = ARG1
OUTPUT = ARG2."/".ARG3.".png"

rowi = 1
rowf = 7

# obtain sum(column(2)) from rows `rowi` to `rowf`
#set datafile separator ','
stats DATAFILE u 2 every ::rowi::rowf noout prefix "A"

# rowf should not be greater than length of file
rowf = (rowf-rowi > A_records - 1 ? A_records + rowi - 1 : rowf)

angle(x)=x*360/100

# circumference dimensions for pie-chart
centerX=0
centerY=0
radius=1

# label positions
yposmax = 0.8
xpos = 1.2
ypos(i) = yposmax - 0.5*i

set terminal png size 1000,670 font ",14" background rgb "white"
set output OUTPUT

#-------------------------------------------------------------------
# now we can configure the canvas
set style fill solid 1     # filled pie-chart
unset key                  # no automatic labels
unset tics                 # remove tics
unset border               # remove borders; if some label is missing, comment to see what is happening

set size ratio -1              # equal scale length
set xrange [-1.05:xpos + 0.8]  
set yrange [-radius:radius] 


#-------------------------------------------------------------------
pos = 0             # init angle
Bi = 0.0
colour = 3       # init colour

set multiplot
# 1st line: plot pie-chart
# 2nd line: draw colored boxes at (xpos):(ypos)
# 3rd line: place labels at (xpos+offset):(ypos)
plot DATAFILE u (centerX):(centerY):(radius):(pos):(pos=pos+angle($2)):(colour=colour+1) every ::rowi::rowf w circle lc var,\
     for [i=0:rowf-rowi] '+' u (xpos):(ypos(i)) w p pt 5 ps 4 lc i+4,\
     for [i=0:rowf-rowi] DATAFILE u (xpos):(ypos(i)):(sprintf('%s %.1f%%', stringcolumn(1), $2)) every ::i+rowi::i+rowi w labels left offset 3,0
     
plot DATAFILE u (mid=Bi+angle($2)*pi/360.0, Bi=2.0*mid-Bi, 0.5*cos(mid)):(0.5*sin(mid)):2 every ::1 w labels     

#pause -1  	  