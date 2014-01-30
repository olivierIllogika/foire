Foire
=====

Local config
------------

### config/database.php
Copy from config/database.php.default and configure to you needs. public/index.php expects a config $poly, $ets or $localhost, see virtual hosts section for details.

### modules/localconfig.php
Copy from modules/localconfig.php.default. This is only required for sending emails (ex. user registration, error reports).

Virtual hosts
-------------

Because the same code is used by Ets and Poly, site configuration is based on hostname (see public/index.php for database config selection). This can be easily configured using virtual hosts on localhost.

Here's my apache virtual host config section as reference:

    <VirtualHost *:8888>
      DocumentRoot "<path to repo>/foire.git"
      ServerName poly.localhost
      ServerAlias ets.localhost

      <Directory <path to repo>foire.git>
         Options Indexes MultiViews FollowSymLinks
          AllowOverride All
          Order allow,deny
          Allow from all
      </Directory>
    </VirtualHost>

Also, I added the following entries in my etc/hosts:

    127.0.0.1 poly.localhost
    127.0.0.1 ets.localhost

  
