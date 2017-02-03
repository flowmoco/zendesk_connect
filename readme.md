![Zendesk Connect Logo](/resources/git-header.jpg)

#zendesk_connect

A drupal 8 module to get reequests for a zendesk end user.
Go to settings and insert your zendesk api token.


Endpoints are:

* "/zendesk/requests" - lists all requests for the user
* "/zendesk/requests/{{id}}" = displays a specific request by id (use to link from above)
* "/zendesk/requests/new" - starting a new request


Currently this works by taking a Zendesk api token and combining it with the logged in users email address, hopefully a way using jwt will be used in the near future.

This module currently depends on the auth0 Drupal module here : https://packagist.org/packages/auth0/auth0_drupal

It would be nice to make this more independent from auth0 module for reuse
