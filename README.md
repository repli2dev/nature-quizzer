Nature Quizzer
--------------

Simple web-based adaptive learning system for teaching users biological concepts such as plants, animals,...

Installing
==========

1. Install development dependencies via `node`:

```
$ node update

```

2. Install runtime (aka PHP) dependencies via `composer`:
 
```
$ composer update
```

3. Run `gulp` to build css, templates and javascript files:

```
$ gulp
```

4. To install basic database schema run prepared migrations:

```
# cd <project path>
$ php utils/updatedb.php
```

5. Import desired data into the system.

Running
=======

Use Apache or PHP embedded server to serve `www` directory with `index.php` as index file:

```
php -S localhost:8000 -t <project path>/www
```

Structure
=========

TBD

Developing
==========

Run `gulp in development mode (rebuilds everything and watch for changes):

```
$ gulp development
```