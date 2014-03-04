jquery-maxsubmit
================

jQuery plugin to detect if too many form items will be submitte for the server to cope with.

## Introduction

Some appications, such as ecommerce sites, can have administration forms that submit well
over a thousand parameters. PHP, by default, is set to accept only one thousand parameters
and so some of the submitted data can get lost.

Most applications don't check whether they received everything, and so data can get broken
easily and silently. A WooCommerce product with 40 variations can have over 1300 submitted
form items, and when saving the product you have no idea that much of that data is being
discarded.

Luckily [the maximum number of accepted parameters can be changed in php.ini](http://docs.woothemes.com/document/problems-with-large-amounts-of-data-not-saving-variations-rates-etc/)
The problem is,
many site owners have no idea this needs to be done until it is too late and their
WooCommerce store has lost half its product variations.

## Plugin Purpose

What this jQuery plugin attempts to do, is warn the site administrator before a form is
submitted, on the client (browser) side, and give the administrator a chance to cancel the
submit and change the settings on the server. It does this by counting how many items
will be submitted in a form (it does this, hopefully, intellidently by taking into account
all the form item types and selected values). The plugin is given the maximum number of
items the server will accept when the page is generated, so it has a number to compare to.

## Implementation

### Client-side JavaScript

The simplest way to implement the check is to use this JavaScript in your jQuery ready()
function:

    $('form').maxSubmit({max_count: 1000});
    
That will trigger on all forms, and warn the user if more than 1000 values are about to
be POSTed by the form. Additional settings allow you to modify the confirm box text,
or replace the standard confirm box with something more ambitious, such as a jquery.ui
dialog. You can target specific forms with different settings if you wish.

### Server-side Code

The server limit (1000 in the above example) needs to be calculated dynamically on the
server. It can be found with a simple PHP function like this:

    /**
     * Get the submission limit.
     * Returns the lowest limit or false if no limit can be found.
     * An alternate default can be provided if required.
     * CHECKME: do we need to separate GET and POST limits, as they may apply
     * to different forms. The larger number of parameters is like to only
     * apply to POST forms, so POST is important. The REQUEST max vars is 
     * another thing to consider, as it will be the sum of GET and POST parameters.
     */
    /* public */ function getFormSubmissionLimit($default = false)
    {
        // All these ini settings will affect the number of parameters that can be
        // processed. Check them all to find the lowest.
        $ini = array();
        $ini[] = ini_get('max_input_vars');
        $ini[] = ini_get('suhosin.get.max_vars');
        $ini[] = ini_get('suhosin.post.max_vars');
        $ini[] = ini_get('suhosin.request.max_vars');

        // Strip out the blanks - ini options not set.
        $ini = array_filter($ini, 'is_numeric');

        // Find the smallest of them all.
        $lowest_limit = ( ! empty($ini) ? min($ini) : false);

        return ($lowest_limit === false ? $default : $lowest_limit);
    }

That runs on the server and provides the server settings to insert into the JavaScript
initialisation, and will return 1000 by default on most PHP servers.

## Demo

A simple demo (index.php in this project) is running here: [http://www.acadweb.co.uk/maxsubmit/]

## WordPress Plugin

This submit checker is wrapped into a WordPress plugin here:
https://github.com/academe/wp-max-submit-protect
Just install the plugin (binary zips are available under the releases) and it will protect
all admin forms from being submitted if the server will not accept the number of form items
being POSTed.

## jQuery Plugins

The latest version is available here:
http://plugins.jquery.com/jquery-maxsubmit/

## History

1.2.1 Correction to download link in metadata  
1.2.0 Bumped up version as interface has been extended  
1.1.4 Rewrite to support testing; listing of the items that will be submitted  
1.1.3 Fixed count of HTML5 input elements  
1.1.2 Updated metadata for plugins.jquery.com  
1.1.1 Fixed syntax error messaing with Chrome  
1.1.0 Issue #2 reported by @Bubbendorf Update to demo to demonstrate fixes.  
1.0.2 Fixed manifest; first release to plugins.jquery.com  
1.0.1 First attempt to get it into plugins.jquery.com  
1.0.0 First release, used in WP plugin.  
