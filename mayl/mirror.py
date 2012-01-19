#!/usr/bin/env python

from optparse import OptionParser
import datetime
from urlparse import urlparse

# http://stackoverflow.com/questions/35817/how-to-escape-os-system-calls-in-python
from pipes import quote
import os
import os.path
import sys
import re

usage = """usage: %prog [options] mirror_dir url

creates a mirror of url in mirror_dir.  Prepends a google-cache like header to the file after
mirroring is done.  Uses wget to do the actual mirroring.  url is the only parameter that is assumed to be coming from a potentially untrusted user, and is quoted.
"""


def get_wget_cmd(opt):
    ua_str = ''
    if opt.user_agent != None:
        ua_str = '--user-agent=%s' % opt.user_agent

    # --no-directories 
    cmd = "%s --quota=%s --append-output=%s --convert-links --directory-prefix=%s --no-host-directories --cut-dirs=99 --no-host-directories --page-requisites --adjust-extension --span-hosts %s %s" % (
        opt.wget_path,
        opt.quota,
        opt.log_path,
        opt.mirror_dir,
        quote(ua_str),
        quote(opt.url))
    return cmd

def adjust_extension(filename):
    """ If filename doesn't end in .html, make it so """
    pattern = '\.[Hh][Tt][Mm][Ll]?$'
    if filename == '':
        return 'index.html'
    if re.search(pattern, filename):
        return filename
    return filename + '.html'


def prepend_header(opt, file_path, log):
    """Prepend a "this is a mirror" header to file_path """
    now = datetime.datetime.now()
    now_str = now.strftime("%Y-%m-%d %H:%M")
    # modelled after the google cache header, but without the base tag
    header = """
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<div style="background:#fff;border:1px solid #999;margin:-1px -1px 0;padding:0;">
<div style="background:#ddd;border:1px solid #999;color:#000;font:13px arial,sans-serif;font-weight:normal;margin:12px;padding:8px;text-align:left">
This is a Mirror As You Link copy of <a href="%s" style="text-decoration:underline;color:#00c">
%s</a>. It is a snapshot of the page as it appeared on %s. The <a href="%s" style="text-decoration:underline;color:#00c">
current page</a> could have changed in the meantime.
<a href="http://mirrorasyoulink.org" style="text-decoration:underline;color:#00c">Learn more</a>.
<br><br>
<div>&nbsp;</div>
</div>
</div>
<div style="position:relative">
""" % (opt.url, opt.url,
       now_str,
       opt.url)

    log.write("Writing header to %s\n" % file_path)
    with open(file_path, "r+") as f:
        old = f.read() # read everything in the file
        f.seek(0) # rewind
        f.write(header + old)


def do_path_magic(opt, log):
    """
    # Ugh.  Wget has some magic to pick filename
    """
    o = urlparse(opt.url)
    # Not actually taking just the filename--want everything to the right of the slash
    log.write("urlparse(%s) = %s\n" % (opt.url, o))
    
    filename = os.path.basename(o.path)+o.params
    if o.query:
        if o.path.endswith('/'):
            filename += "index.html?"+o.query
        else:
            filename += "?" + o.query

    # wget doesn't actually use the fragment name in the filename (makes sense,
    # since it gets the whole file)
    #if o.fragment:
    #        filename += "#" + o.fragment 
            
    if filename == '':
        filename = 'index.html'
    
    return adjust_extension(filename)



def main(args):
    parser = OptionParser(usage)
    parser.add_option("--log", dest="log_path",
                      help="log output to FILE", metavar="FILE")
    parser.add_option("--ua", dest="user_agent",
                      help="Pass UA as the user agent", metavar="UA")
    parser.add_option("--wget_path", dest="wget_path",
                      help="Path to wget", default="/usr/local/bin/wget")
    parser.add_option("--quota", dest="quota",
                      help="quota (in bytes, append 'k' for KB, 'm', for MB)",
                      default="5m")
    parser.add_option("--skip", dest="skip", action="store_true",
                      help="skip the wget", default=False)
    
    (opt, args) = parser.parse_args()
    if len(args) != 2:
        parser.error("expecting exactly two arguments")
        
    (mirror_dir, url) = args
    setattr(opt, 'mirror_dir', mirror_dir)
    setattr(opt, 'url', url)
    if opt.log_path == None:
        opt.log_path = opt.mirror_dir + '.log'

    if not opt.skip:
        wget = get_wget_cmd(opt)
        print 'Running wget...'
        os.system(wget)
        print '...wget done'

    with open(opt.log_path, 'a') as log:
        filename = do_path_magic(opt, log)
        log.write("filename = %s\n" % (filename))

        file_path = opt.mirror_dir + '/' + filename
        prepend_header(opt, file_path, log)

        # always insist the file ends up in index.html
        if filename != 'index.html':
            os.rename(file_path, opt.mirror_dir + '/' + 'index.html')
        
if __name__=='__main__':
    main(sys.argv)
