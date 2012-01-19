#!/usr/bin/env python

# This program is free software; you can redistribute it and/or modify 
# it under the terms of the GNU General Public License as published by 
# the Free Software Foundation; version 2 of the License.
#
# This program is distributed in the hope that it will be useful, 
# but WITHOUT ANY WARRANTY; without even the implied warranty of 
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
# GNU General Public License for more details. 

# You should have received a copy of the GNU General Public License 
# along with this program; if not, write to the Free Software 
# Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
#
# @author Victor Shnayder (shnayder seas.harvard.edu)

usage = """cleanup.py KEEPFILE SRC DST [--move]

This program will move all files that aren't on a "keep list".  It is meant for cleaning up unreferenced posts in mirror-as-you-link.  KEEPFILE should be a file containing one filename per line.

When run without the --move flag, the program will print a list of the files and directories it would move to DST.

With --move, it will actually perform the moves.  DST must already exist, and to avoid errors, it should not be inside SRC unless it's also listed in KEEPFILE.
"""

import sys
import os
import os.path
import time
from optparse import OptionParser


def main(argv):
    parser = OptionParser(usage)
    parser.add_option("--move", dest="move", action="store_true",
                      help="actually perform the moves", default=False)
    (opt, args) = parser.parse_args(argv)
    if len(args) != 3:
        parser.error("expecting exactly three arguments")

    keep = args[0]
    src = args[1]
    dst = args[2]

    if not os.path.isdir(dst):
       parser.error("dst '%s' must be an existing directory" % dst)

    with open(keep, 'r') as keepfile:
        keep = set([s.strip() for s in keepfile.readlines()])
        files = set(os.listdir(src))
        to_move = sorted(files - keep)
        if opt.move:
            print """
################################
About to move %d files!
Pausing for 5 seconds.  Hit ^C to stop.
################################
""" % len(to_move)
            time.sleep(5)
            
        for moveme in to_move:
            path = os.path.join(src, moveme)
            cmd = "mv %s %s" % (path, dst)
            if opt.move:
                print "running '%s'" % cmd
                os.system(cmd)
            else:
                print cmd
        if opt.move:
            print "done"

if __name__ == "__main__":
    main(sys.argv[1:])
