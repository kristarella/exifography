=== Plugin Name ===
Contributors: kristarella
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7439094
Tags: exif, iptc, photos, photographs, photoblog
Requires at least: 3.2
Tested up to: 6.0.1
Stable tag: 1.3

Exifography displays EXIF data for images and enables import of latitude and longitude EXIF to the database.

== Description ==

Exifography (formerly Thesography) displays EXIF data for images uploaded with WordPress. It utilises WordPress’ own feature of storing EXIF fields in the database, and also enables import of latitude and longitude, and flash fired EXIF to the database upon image upload.

The purpose of this plugin is to make dislaying EXIF data as convenient as possible, while using as much of WordPress' native image handling as possible.

EXIF can be displayed via a shortcode, via a function in your theme files and it can be inserted automatically for the first image attached to a post.

Features include:
* Location shown as coordinates or static Google map, and linked to Google maps (or not)
* Customisable HTML layout via plugin options
* All output can be manipulated via a filter
* Language localisation

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Visit the Exifography Options page under the WordPress Settings menu.
1. See the [Exifography plugin page](http://www.kristarella.com/exifography/) for instructions on EXIF display.

== Frequently Asked Questions ==

= Can I use Exifography to display EXIF for my Flickr images? =

No. Exifography uses data imported to the WordPress database upon image upload. Only images uploaded with WordPress will have their EXIF data available to Exifography.

= Can automatic insertion be used with themes other than Thesis? =

Yes! Previously this plugin was called Thesography because I built it to use with Thesis, but that same functionality is available for all themes now.

= Why did you change the name? =

See the above answer.

== Screenshots ==

1. Options Page allows you to set default post options for EXIF display and customise the HTML output of EXIF for styling.
2. Each post can have its own EXIF items displayed.

== Changelog ==

= 1.3.2 = air_drummer@verizon.net
* code cleanup
* add filename option
* add uninstall to remove options
* make Separator for EXIF label not appear when item label is turned off
* remove &ll=<ll> from gmap url
* add manual location options in shortcode
* add nohtml,nolabels,labels,all output options
* change 'Turn off item label' option to no_item_label
* replace map image with embedded

= 1.3.1 =
* Added featured image as automatic exif source
* Refactored `display_geo` so it can be used independently
* Bug fixes

= 1.3 =
* Added Google Maps API field for high usage
* Added visual sorting to settings page
* Function to more easily retrieve different forms of geo info

= 1.2 =
* Fixed bug where shortcode with no show att doesn't display
* changed google maps link to protocol agnostic
* sanitise exif input
* rearranged some functions

= 1.1.3.8 =
* Bug fixes

= 1.1.3.7 =
* Fixed warnings on line 290, 452 and 561
* Updated output of exposure bias
* Added Russian translation files
* Changed URLs for CSS & JS files to fix https issue

= 1.1.3.6 =
* Fixed everything that broke in 1.1.3.5

= 1.1.3.5 =
* Fixed location display that broke in 1.1.3.4

= 1.1.3.4 =
* Fixed warnings when exif item doesn't exist (particularly 'exposure_bias')
* Fixed label removing checkbox not being saved
* Updated language files

= 1.1.3.3 =
* Fixed exposure bias output (to calculate out float value)
* Fixed map size error
* Updated code for checking options & other minor optimisations

= 1.1.3.2 =
* Fixed exposure bias output
* Fixed bug in post options saving/changing
* Included language files

= 1.1.3 =
* Fixed PHP warnings on admin page
* Added variables for IDs and classes in EXIF output
* Added option to control EXIF item label display

= 1.1.2 = Fixed show "all" parameter and minor spanish translation fix.

= 1.1.1 = Fixed broken language localisation

= 1.1 = Completely rewritten for efficiency and to use the WordPress Settings API. Added flash fired import and display, and map thumbnail display.

= 1.0.3.2 = Fixed one conditional error

= 1.0.3 =
* Removed a bit of debugging that wasn't removed before 1.0.3, was causing the word "Array" to appear after the list.

= 1.0.3 =
* Fixed language file location issue
* Added option to turn off autoinsert in Thesis

= 1.0.2 =
* Fixed bug for new installs on WP2.9.1
* Added languages directory with localisation files

= 1.0.1 =
* Fixed errors regarding foreach and string arguments when no image is attached.
* EXIF added automatically to syndication feeds when Thesis automatic insertion is used.

= 1.0 =
* Displays message to visit options page if trying to write a post before options have been saved, to avoid PHP errors.

= 1.0b =
* Fixed detection of image when no image ID is given
