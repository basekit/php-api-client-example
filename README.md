# PHP API Client Example

This example script uses the [BaseKit PHP API Client](https://github.com/basekit/php-api-client) to create a fully populated website.

It creates the account holder, sets the account holder's package and then creates a site with content.

Please note that in most cases you will only need to create the account holder, set the package and create the site. The calls to add, delete and update sections won't be needed as our interactive customer onboarding process will create a default site with content.

One other thing to note is in the create account holder call a property called `entryFlowComplete` is set to 1, this has the effect of skipping the onboarding process so if you want to use our interactive onboarding process then this property should be removed.


(Sample image courtesy of [Goran Ivos, Unsplash](https://unsplash.com/photos/kQIaF3iPLS4))
