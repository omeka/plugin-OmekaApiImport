OmekaApiImport
=====================

Use Omeka 2.1's API to do an Omeka site-to-site import

Background
----------

Omeka 2.1 introduced an API onto records, including Items, Collections, Elements, and more. Plugins can 
also add their records to the API. The API is located at `youromekasite.org/api`, and can be turned on under 
`Admin->Settings->Api`.

This plugin uses the API to import data from one Omeka site with an active API into another Omeka site.

Usage
-----

Install the plugin in the usual way. Click the `Omeka Api Import` tab in the admin screen. Enter the API URL of the
site from which you want to import data.

Optionally, enter the API you have for the external Omeka site. This will have to be provided by an administrator
of that site. If your key provides sufficient permissions, this will allow Users and non-public Items and
Collections to be imported

Element Sets will be imported. If the external site has edited the comments for Elements, you can check the box to
override the comments that exist in your site. This is only recommended if you are importing into an empty Omeka site.

Examples
--------

For converting data from the API to CSV, look at [OmekaApiToCsv](https://github.com/patrickmj/OmekaApiToCsv) and [Omekadd](https://github.com/wcaleb/omekadd)
