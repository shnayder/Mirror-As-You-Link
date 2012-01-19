Mirror as You Link FAQ

== Getting Started and Getting Involved ==

Q: Is Mirror As You Link ready for use?

A: This project is in an early stage and we would like it to evolve it rapidly. It works in some capacity now, and will work better in the future!  But please understand that it sometimes may break.  If it does, please tell us how!  Not breaking is our highest priority at this time.  We do not believe there is any way this could permanently damage your blog (but you do backup your blog, right? Please do!)

Q: How do I install Mirror As You Link?
A: Unzip and move the folder "mayl" into your "wp-content/plugins/" directory. Make sure the subfolder "mayl/mirrors" is readable and writeable by the WordPress/Apache user (usually "apache", "www", or "www-data").  If you would like to contribute, we would very much like for people to write installation scripts, etc. to streamline this process on various hosting platforms.  

Dependencies:
python (tested with 2.6 and 2.7, should work with earlier versions too)
wget (works with 1.13, not 1.11).  If wget is not in /usr/local/bin/, change the path on the Mirror As You Link settings page in WordPress

Q: How do I use Mirror As You Link?

A: When you author a post, simply wrap your link in the shortcode:
[mirror]http://mirror.me/[/mirror]
or
[mirror href=http://mirror.me/ ]I mirrored this link![/mirror]
At this time, the shortcode "cite" is another alias for "mirror".

KNOWN BUGS:
- For now, you must use an absolute URL (begins with "http://").
- If you use the "href=" syntax, do not wrap your URL in quotes.
- If you use the "href=" syntax, be careful to put a space between the URL and the closing bracket (]). If you type something like:
[mirror href=http://mirror.me/]I am error![/mirror]
The URL's closing slash (/) will confuse the Wordpress shortcode parser.
If you would like to contribute, these are good bugs to fix!

Q: That's too much work!

A: You can enable "automatic mirroring" mode from the MAYL Settings menu.  This will mirror all links in posts you save after enabling this feature.

Q: What is MAYL doing? How does it work? Will it break my server?

A: MAYL is just a simple user interface wrapped around existing software (at this time, wget) that, on its own, is sometimes cryptic and frustrating to use.  We are trying to make using this technology as effortless as possible, so that people will actually do so on a regular basis.

The principle security concern is that you will mirror a Web site that hosts malicious code, either server-side (threatening your server) or client-side (threatening readers who visit the mirror). To protect your server, you should configure your webserver not to run code from the "mayl/mirrors" directory.  To do this in Apache, put the following 3 lines mirrors/.htaccess (this is included in the install)

Options -Indexes
Options -ExecCGI
AddHandler cgi-script .php .pl .py .jsp .asp .htm .shtml .sh .cgi

To protect your users, wget should be configured to not download JavaScript, etc, though this may break some functionality on the mirrored page.  If you would like to contribute, it could be good to replace wget with mirroring software that can sanitize mirrored files (PHP native would be a plus as that would remove a dependency).

Q: I think you should add feature XYZZY!

A: Here are some high-priority features we are adding soon:
- Periodically updating mirrors.
- Storage management (both global and per-mirror).
- Off-site archiving (to Internet Archive, etc.)
- robots.txt directives so that mirror-ees can control policy (e.g. "don't mirror me", "update every N hours".)

If you have other ideas, or ideas about how existing features should behave, look, and feel, please contact us or post to our github! We will set up a forum for public discussion ASAP.

Q: This is cool! How can I contribute?

A: Here are some things we would really like people to look at:
- Installation: If you are familiar with WordPress hosting environments, please help us make it as easy as possible for server administrators to install and make available our plugin.
- There are some known parser errors, and probably many unknown. In particular, the WordPress shortcode parser has bugs related to quotes and slashes, and we also do some annoying parsing to figure out where wget saves the target page. Take a look and debug this!
- Ultimately wget could be replaced with PHP native mirroring software. If you have a preferred alternative, please build in support!
- Do a security review; in particular, we need to make sure malicious mirror-ees have no avenues of attack.
- We would really like to build the mirror storage into an existing WordPress file manager, such as WP-Filebase.
- Any of the features suggested above would be great to develop.
- If you'd like to port Mirror As You Link to other content platforms, a great way to start would be to separate the code into generic mirroring and WordPress-specific code.  It should really only be the mirroring invocation (what causes a mirror to be made?) and mirror access (where is the mirror link made available?) and the configuration page that are platform-specific.  mirror.py is a start at this.

== Configuration ==

Q: Is there a way to limit the amount of storage used overall?  Per-mirror?

A: We currently ask wget to limit the space for each mirror to 5 MB including all page requisites (images, etc).  Note that this limit is not respected if the linked file itself is larger than 5 MB.  We plan to fix this soon.


Q: What permissions does MAYL need to work properly?

A: The webserver user (usually 'apache') needs to have read and write access to the mirrors directory.  You may also want the sgid bit set so that subdirectories inherit the group id.  (chmod g+s mirrors).


== Functionality / Security ==

Q: What kind of sites will get mirrored properly?

A: publicly accessible sites with standard html, css, and images.

Q: What if a login is required to get to the linked page?

A: Won't work.

Q: What if the page has dynamic content that requires interaction with the server?

A: The dynamic content won't work unless the original server is up.  (We only mirror the html, css, and images)

Q: What about robots.txt?

A: wget respects robots.txt

Q: What user agent does MAYL use?

A: 'Mozilla/5.0' by default.  You can change this on the Mirror As You Link settings page in WordPress.

Q: What kind of load does using citations add to my server?

A1: We start a background wget process every time you create a citation.  If the site being mirrored is slow or unresponsive, wget will time after several minutes.

A2: The cost of hosting mirrored content depends on whether you pay for storage space, and the cost of serving depends on how frequently users click on the mirrors.
