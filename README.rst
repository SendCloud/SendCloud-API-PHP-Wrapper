SendCloud API PHP Wrapper
=========================

This wrapper helps you to easily connect to the SendCloud API when you use PHP as your main programming language.

The wrapper is officially supported by SendCloud.
Feel free to improve the wrapper by sending us a pull request.

Supported API versions
----------------------
This wrapper is designed for API v2. Which is currently the most recent version of the SendCloud API.

Example
-------
Download ``SendCloudApi.php``::

   require_once('SendCloudApi.php');

   $api = new SendCloudApi('API_PUBLIC', 'API_SECRET');

   // Get shipping methods of this user
   $shipping_methods = $api->shipping_methods->get();



Documentation
----------------------
Documentation is available here:
`SendCloud API v2 Documentation (incl. PHP wrapper examples) <https://docs.sendcloud.sc/api/v2/index.html>`_

Be aware that you need a SendCloud account before you can use the SendCloud API.


Tests
-----
Use Composer_ to install the test dependencies and run the tests::

    composer install
    vendor/bin/phpunit tests

You may use the built-in Docker_ image for development purposes::

    docker build . -t sc-php-api
    docker run --rm -it -v "$PWD":/code sc-php-api bash
    composer install
    vendor/bin/phpunit tests/



Alternatives
----------------------
`Picqer unofficial PHP API wrapper <https://github.com/picqer/sendcloud-php-client>`_


Support
-------
If you need support please contact us through the form in the SendCloud Panel.


Copyright (c) 2014 SendCloud_ BV released under the MIT License

.. _SendCloud: https://www.sendcloud.sc
.. _Composer: https://getcomposer.org
.. _Docker: https://www.docker.com
