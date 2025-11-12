# Julender

![Code Coverage on PHP 8.3](https://gist.githubusercontent.com/manuelmaurer/803f34206bacd4ff0fd78c2e7a820226/raw/5bf8ed05a50b8f3973385b7f0f9003826e75051d/ci-php8.3.svg)
![Code Coverage on PHP 8.4](https://gist.githubusercontent.com/manuelmaurer/dd8853a6f8af0b6b2e88ab392ba359b2/raw/0c4c34b6b374d594d30ccecd99b25f8b98f045de/ci-php8.4.svg)


PHP Advent Calendar based on slim4


## Installation

### Local method
### Requirements
- Webserver
- PHP 8.3+
- Composer
#### Steps
- Clone this repository
- Run `composer install`
- Point your webserver to the public folder

Apache Webserver:
Make sure mod_rewrite is enabled.

Nginx example:
```
    index		index.php;
    root		/var/www/julender/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param SCRIPT_NAME $fastcgi_script_name;
        fastcgi_index index.php;
        fastcgi_pass   unix:/run/php/php-fpm.sock;
    }
```


### Docker
#### Requirements
- Docker
#### Steps
No docker images are built at the moment, keep posted

## Configuration & set up

To get started, you need to placed 24 images into the [media/](media/) folder named 01.png throught 24.png.

You can customize the calendar by editing either setting environment variables or by creating a configuration file at `config/config.local.php`.

| Env Variable     | config file | Description                           | Default Value |
|------------------|-------------|---------------------------------------|---------------|
| JUL_TITLE        | title       | Title of the calendar                 | Julender      |
| JUL_TIMEZONE     | timezone    | Timezone the calendar runs in         | Europe/Berlin |
| JUL_LANGUAGES    | languages   | Languages available for the calendar  | en,de         |
| JUL_ADVENT_MONTH | adventMonth | Month of the advent calendar          | 12            |
| JUL_PASSWORD     | password    | Password for the admin panel          | <null>        |
| JUL_DEBUG        | debug       | Enable debug mode                     | false         |

To enable DI container compilation you need to set the environment variable `CONTAINER_CACHE` to `1`.
This is not configurable via the config file, because it's parsed before the configuration is parsed.



## Development
There is a preconfigured docker-compose.yml file available, to start the development environment run:
```bash
docker compose up -d
```

Then visit http://localhost:8080/
